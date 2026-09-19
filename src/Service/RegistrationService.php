<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Database;
use Craw\Core\Settings;
use Craw\Domain\StructuredCommunication;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;

/**
 * Orchestration du cycle de vie d'une inscription.
 */
final class RegistrationService
{
    public function __construct(
        private readonly RegistrationRepository $registrations = new RegistrationRepository(),
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private readonly TrainingRepository $trainings = new TrainingRepository(),
    ) {
    }

    /**
     * Confirme une inscription : attribue reference et communication
     * structuree, cree la ligne de paiement et met en file le courriel de
     * confirmation.
     *
     * @param array<string, mixed> $training
     *
     * @return array<string, mixed> L'inscription mise a jour.
     */
    public function confirm(int $registrationId, array $training): array
    {
        return Database::transaction(function () use ($registrationId, $training): array {
            $registration = $this->registrations->find($registrationId);

            if ($registration === null) {
                throw new \RuntimeException('Inscription introuvable.');
            }

            // Idempotence : une confirmation rejouee ne doit rien dupliquer.
            if ((string) $registration['status'] !== 'draft') {
                return $registration;
            }

            $trainingId = (int) $training['id'];
            $priceCents = (int) $training['price_cents'];
            $isFree = $priceCents <= 0;

            $seatsLeft = $this->trainings->remainingSeats($trainingId);
            $status = $seatsLeft > 0 ? ($isFree ? 'confirmed' : 'pending_payment') : 'waitlist';

            $year = (int) date('Y', strtotime((string) $training['starts_on']));
            $communication = StructuredCommunication::forRegistration($registrationId, $year);

            $reference = sprintf('INS-%d-%04d', $year, $registrationId);

            $update = [
                'reference' => $reference,
                'status' => $status,
                'amount_due_cents' => $priceCents,
                'structured_communication' => $communication,
                'confirmed_at' => date('Y-m-d H:i:s'),
            ];

            if ($status === 'confirmed') {
                $update['confirmed_at'] = date('Y-m-d H:i:s');
            }

            $this->registrations->update($registrationId, $update);

            // Ligne de paiement, meme pour une formation gratuite : elle sert
            // de trace et simplifie les requetes de statistiques.
            if ($this->payments->findByRegistration($registrationId) === null) {
                $deadlineDays = max(1, (int) Settings::get('payment.deadline_days', 15));
                $dueDate = date('Y-m-d', time() + $deadlineDays * 86400);

                // Jamais d'echeance apres le debut de la formation.
                $startsOn = (string) $training['starts_on'];
                if (strtotime($dueDate) > strtotime($startsOn)) {
                    $dueDate = date('Y-m-d', max(time(), strtotime($startsOn . ' -1 day')));
                }

                $this->payments->create([
                    'registration_id' => $registrationId,
                    'status' => $isFree ? 'waived' : 'unpaid',
                    'method' => $isFree ? 'exempt' : 'transfer',
                    'amount_cents' => $priceCents,
                    'due_date' => $isFree ? null : $dueDate,
                ]);
            }

            $fresh = $this->registrations->find($registrationId);

            return $fresh ?? $registration;
        });
    }

    /**
     * Cree le brouillon d'inscription et retourne son identifiant.
     *
     * @param array<string, mixed> $data
     */
    public function createDraft(int $trainingId, array $data): int
    {
        $data['training_id'] = $trainingId;
        $data['status'] = 'draft';
        $data['token'] = bin2hex(random_bytes(32));
        // Reference provisoire : remplacee a la confirmation par INS-AAAA-XXXX.
        $data['reference'] = 'TMP-' . bin2hex(random_bytes(8));

        return $this->registrations->create($data);
    }

    public function cancel(int $registrationId, ?string $reason = null): void
    {
        Database::transaction(function () use ($registrationId, $reason): void {
            $this->registrations->update($registrationId, [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
            ]);

            $payment = $this->payments->findByRegistration($registrationId);

            if ($payment !== null && !in_array((string) $payment['status'], ['paid', 'refunded'], true)) {
                $this->payments->update((int) $payment['id'], [
                    'status' => 'cancelled',
                    'note' => $reason,
                ]);
            }
        });
    }

    /**
     * Promeut le premier inscrit de la liste d'attente quand une place se
     * libere.
     *
     * @return array<string, mixed>|null L'inscription promue, le cas echeant.
     */
    public function promoteFromWaitlist(int $trainingId): ?array
    {
        if ($this->trainings->remainingSeats($trainingId) <= 0) {
            return null;
        }

        $candidate = Database::selectOne(
            "SELECT * FROM registrations
             WHERE training_id = :training AND status = 'waitlist'
             ORDER BY created_at ASC LIMIT 1",
            ['training' => $trainingId]
        );

        if ($candidate === null) {
            return null;
        }

        $payment = $this->payments->findByRegistration((int) $candidate['id']);
        $isFree = (int) $candidate['amount_due_cents'] <= 0;

        $this->registrations->update((int) $candidate['id'], [
            'status' => $isFree ? 'confirmed' : 'pending_payment',
        ]);

        if ($payment !== null && (string) $payment['status'] === 'cancelled') {
            $this->payments->update((int) $payment['id'], ['status' => 'unpaid']);
        }

        return $this->registrations->find((int) $candidate['id']);
    }

    /** Nom complet formate, utilise partout (listes, diplome, courriels). */
    public static function fullName(array $registration): string
    {
        return trim(
            (string) ($registration['first_name'] ?? '') . ' ' . (string) ($registration['last_name'] ?? '')
        );
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Brouillon',
            'pending_payment' => 'En attente de paiement',
            'confirmed' => 'Confirmée',
            'waitlist' => 'Liste d\'attente',
            'cancelled' => 'Annulée',
            default => $status,
        };
    }
}

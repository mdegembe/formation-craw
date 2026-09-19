<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Audit;
use Craw\Core\Database;
use Craw\Core\Settings;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;

final class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private readonly RegistrationRepository $registrations = new RegistrationRepository(),
        private readonly TrainingRepository $trainings = new TrainingRepository(),
    ) {
    }

    /**
     * Confirme la reception d'un paiement (action reservee a l'administrateur,
     * RG-05). Un montant inferieur a l'attendu bascule en « partiel ».
     */
    public function confirm(
        int $registrationId,
        ?int $receivedCents,
        ?string $valueDate,
        ?string $note,
        ?int $adminUserId
    ): bool {
        $registration = $this->registrations->find($registrationId);
        $payment = $this->payments->findByRegistration($registrationId);

        if ($registration === null || $payment === null) {
            return false;
        }

        $expected = (int) $payment['amount_cents'];
        $received = $receivedCents ?? $expected;
        $isPartial = $received > 0 && $received < $expected;

        Database::transaction(function () use (
            $payment,
            $registration,
            $received,
            $valueDate,
            $note,
            $adminUserId,
            $isPartial
        ): void {
            $this->payments->update((int) $payment['id'], [
                'status' => $isPartial ? 'partial' : 'paid',
                'received_cents' => $received,
                'value_date' => $valueDate,
                'note' => $note,
                'confirmed_by' => $adminUserId,
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$isPartial && (string) $registration['status'] === 'pending_payment') {
                $this->registrations->update((int) $registration['id'], [
                    'status' => 'confirmed',
                    'confirmed_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });

        Audit::record('payment.confirm', 'registration', $registrationId, [
            'received_cents' => $received,
            'expected_cents' => $expected,
            'partial' => $isPartial,
        ]);

        if (!$isPartial) {
            $training = $this->trainings->find((int) $registration['training_id']);
            $fresh = $this->registrations->find($registrationId);

            if ($training !== null && $fresh !== null) {
                Notifier::paymentConfirmed($fresh, $training);
            }
        }

        return true;
    }

    /** Exonere une inscription (invite, partenaire, intervenant). */
    public function waive(int $registrationId, ?string $note, ?int $adminUserId): bool
    {
        $registration = $this->registrations->find($registrationId);
        $payment = $this->payments->findByRegistration($registrationId);

        if ($registration === null || $payment === null) {
            return false;
        }

        Database::transaction(function () use ($payment, $registration, $note, $adminUserId): void {
            $this->payments->update((int) $payment['id'], [
                'status' => 'waived',
                'method' => 'exempt',
                'note' => $note,
                'confirmed_by' => $adminUserId,
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]);

            if ((string) $registration['status'] === 'pending_payment') {
                $this->registrations->update((int) $registration['id'], [
                    'status' => 'confirmed',
                    'confirmed_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });

        Audit::record('payment.waive', 'registration', $registrationId, ['note' => $note]);

        return true;
    }

    /** Annule la confirmation d'un paiement (erreur de rapprochement). */
    public function revert(int $registrationId, ?int $adminUserId): bool
    {
        $registration = $this->registrations->find($registrationId);
        $payment = $this->payments->findByRegistration($registrationId);

        if ($registration === null || $payment === null) {
            return false;
        }

        Database::transaction(function () use ($payment, $registration): void {
            $this->payments->update((int) $payment['id'], [
                'status' => 'unpaid',
                'received_cents' => null,
                'value_date' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

            if ((string) $registration['status'] === 'confirmed') {
                $this->registrations->update((int) $registration['id'], ['status' => 'pending_payment']);
            }
        });

        Audit::record('payment.revert', 'registration', $registrationId);

        return true;
    }

    /**
     * Met en file les relances dues. Appele par le cron.
     *
     * @return array{reminders: int}
     */
    public function queueReminders(int $trainingId): array
    {
        $training = $this->trainings->find($trainingId);

        if ($training === null) {
            return ['reminders' => 0];
        }

        // Parametre « 7,3 » : relance 7 jours puis 3 jours avant l'echeance.
        $offsets = array_values(array_filter(array_map(
            static fn (string $value): int => (int) trim($value),
            explode(',', (string) Settings::get('payment.reminder_days', '7,3'))
        )));

        if ($offsets === []) {
            $offsets = [7, 3];
        }

        rsort($offsets);
        $maxReminders = count($offsets) + 1; // + le rappel final apres echeance

        $queued = 0;
        $alreadyQueued = [];

        // Du plus large au plus proche, puis les echeances depassees.
        foreach (array_merge($offsets, [0]) as $daysBefore) {
            $due = $this->payments->dueForReminder($trainingId, $daysBefore, $maxReminders);

            foreach ($due as $row) {
                $registrationId = (int) $row['registration_id'];

                if (isset($alreadyQueued[$registrationId])) {
                    continue;
                }

                $registration = $this->registrations->find($registrationId);

                if ($registration === null) {
                    continue;
                }

                $isFinal = strtotime((string) $row['due_date']) < strtotime(date('Y-m-d'));

                Notifier::paymentReminder($registration, $training, $isFinal);

                $this->payments->update((int) $row['id'], [
                    'status' => 'reminded',
                    'reminders_sent' => (int) $row['reminders_sent'] + 1,
                    'last_reminder_at' => date('Y-m-d H:i:s'),
                ]);

                $alreadyQueued[$registrationId] = true;
                $queued++;
            }
        }

        return ['reminders' => $queued];
    }
}

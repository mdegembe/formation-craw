<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

final class PaymentRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByRegistration(int $registrationId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM payments WHERE registration_id = :id',
            ['id' => $registrationId]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['updated_at'] ??= $data['created_at'];

        return Database::insert('payments', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('payments', $data, ['id' => $id]);
    }

    /**
     * Paiements en attente dont l'echeance approche ou est depassee.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dueForReminder(int $trainingId, int $daysBefore, int $maxReminders = 3): array
    {
        $target = date('Y-m-d', time() + $daysBefore * 86400);

        return Database::select(
            "SELECT p.*, r.email, r.first_name, r.last_name, r.token, r.reference,
                    r.structured_communication, r.amount_due_cents
             FROM payments p
             INNER JOIN registrations r ON r.id = p.registration_id
             WHERE r.training_id = :training
               AND r.status = 'pending_payment'
               AND p.status IN ('unpaid', 'reminded', 'partial')
               AND p.due_date IS NOT NULL
               AND p.due_date <= :target
               AND p.reminders_sent < :max
               AND (p.last_reminder_at IS NULL OR p.last_reminder_at < :cutoff)",
            [
                'training' => $trainingId,
                'target' => $target,
                'max' => $maxReminders,
                // Jamais deux relances en moins de 48 h.
                'cutoff' => date('Y-m-d H:i:s', time() - 2 * 86400),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function totals(int $trainingId): array
    {
        $row = Database::selectOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN p.status = 'paid' THEN 1 ELSE 0 END) AS paid,
                SUM(CASE WHEN p.status IN ('unpaid', 'reminded') THEN 1 ELSE 0 END) AS unpaid,
                SUM(CASE WHEN p.status = 'partial' THEN 1 ELSE 0 END) AS partial,
                SUM(CASE WHEN p.status = 'waived' THEN 1 ELSE 0 END) AS waived,
                SUM(CASE WHEN p.status = 'paid' THEN COALESCE(p.received_cents, p.amount_cents) ELSE 0 END) AS collected_cents,
                SUM(CASE WHEN p.status IN ('unpaid', 'reminded', 'partial') THEN p.amount_cents ELSE 0 END) AS outstanding_cents
             FROM payments p
             INNER JOIN registrations r ON r.id = p.registration_id
             WHERE r.training_id = :training AND r.status <> 'cancelled'",
            ['training' => $trainingId]
        );

        return [
            'total' => (int) ($row['total'] ?? 0),
            'paid' => (int) ($row['paid'] ?? 0),
            'unpaid' => (int) ($row['unpaid'] ?? 0),
            'partial' => (int) ($row['partial'] ?? 0),
            'waived' => (int) ($row['waived'] ?? 0),
            'collected_cents' => (int) ($row['collected_cents'] ?? 0),
            'outstanding_cents' => (int) ($row['outstanding_cents'] ?? 0),
        ];
    }

    /**
     * File d'attente de validation : ce que l'administrateur doit rapprocher.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pending(int $trainingId): array
    {
        return Database::select(
            "SELECT p.*, r.reference, r.first_name, r.last_name, r.email, r.organisation,
                    r.structured_communication, r.created_at AS registered_at
             FROM payments p
             INNER JOIN registrations r ON r.id = p.registration_id
             WHERE r.training_id = :training
               AND r.status IN ('pending_payment', 'confirmed')
               AND p.status IN ('unpaid', 'reminded', 'partial')
             ORDER BY p.due_date ASC, r.created_at ASC",
            ['training' => $trainingId]
        );
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'En attente',
            'reminded' => 'Relancé',
            'partial' => 'Partiel',
            'paid' => 'Payé',
            'refunded' => 'Remboursé',
            'waived' => 'Exonéré',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }
}

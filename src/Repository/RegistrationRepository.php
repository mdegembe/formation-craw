<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

final class RegistrationRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM registrations WHERE id = :id', ['id' => $id]);
    }

    /**
     * Recherche par jeton public, en comparaison a temps constant pour ne pas
     * permettre de deviner un jeton par mesure du temps de reponse.
     *
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        return Database::selectOne('SELECT * FROM registrations WHERE token = :token', ['token' => $token]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveByEmail(int $trainingId, string $email): ?array
    {
        return Database::selectOne(
            "SELECT * FROM registrations
             WHERE training_id = :training AND email = :email
               AND status IN ('pending_payment', 'confirmed', 'waitlist')
             LIMIT 1",
            ['training' => $trainingId, 'email' => mb_strtolower($email)]
        );
    }

    /**
     * Liste filtrable pour le back-office.
     *
     * @param array{status?: string, payment?: string, diet?: string, search?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(int $trainingId, array $filters = [], int $limit = 500, int $offset = 0): array
    {
        [$where, $bindings] = $this->buildFilters($trainingId, $filters);

        $sql = "SELECT r.*, p.status AS payment_status, p.received_cents, p.value_date,
                       p.due_date, p.reminders_sent, p.confirmed_at AS payment_confirmed_at
                FROM registrations r
                LEFT JOIN payments p ON p.registration_id = r.id
                WHERE {$where}
                ORDER BY r.last_name ASC, r.first_name ASC
                LIMIT {$limit} OFFSET {$offset}";

        return Database::select($sql, $bindings);
    }

    /**
     * @param array{status?: string, payment?: string, diet?: string, search?: string} $filters
     */
    public function countSearch(int $trainingId, array $filters = []): int
    {
        [$where, $bindings] = $this->buildFilters($trainingId, $filters);

        return (int) Database::scalar(
            "SELECT COUNT(*) FROM registrations r
             LEFT JOIN payments p ON p.registration_id = r.id
             WHERE {$where}",
            $bindings
        );
    }

    /**
     * @param array<string, string> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilters(int $trainingId, array $filters): array
    {
        $conditions = ['r.training_id = :training'];
        $bindings = ['training' => $trainingId];

        $status = $filters['status'] ?? '';
        if ($status === '') {
            $conditions[] = "r.status <> 'draft'";
        } elseif ($status !== 'all') {
            $conditions[] = 'r.status = :status';
            $bindings['status'] = $status;
        }

        if (($filters['payment'] ?? '') !== '') {
            $conditions[] = 'p.status = :payment';
            $bindings['payment'] = $filters['payment'];
        }

        if (($filters['diet'] ?? '') !== '') {
            $conditions[] = 'r.diet = :diet';
            $bindings['diet'] = $filters['diet'];
        }

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(LOWER(r.last_name) LIKE :search OR LOWER(r.first_name) LIKE :search
                              OR LOWER(r.email) LIKE :search OR LOWER(r.organisation) LIKE :search
                              OR LOWER(r.reference) LIKE :search)';
            $bindings['search'] = '%' . mb_strtolower(trim((string) $filters['search'])) . '%';
        }

        return [implode(' AND ', $conditions), $bindings];
    }

    /**
     * Participants retenus pour le pointage des presences et les diplomes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function confirmedParticipants(int $trainingId): array
    {
        return Database::select(
            "SELECT r.*, p.status AS payment_status
             FROM registrations r
             LEFT JOIN payments p ON p.registration_id = r.id
             WHERE r.training_id = :training AND r.status = 'confirmed'
             ORDER BY r.last_name ASC, r.first_name ASC",
            ['training' => $trainingId]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['updated_at'] ??= $data['created_at'];

        return Database::insert('registrations', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('registrations', $data, ['id' => $id]);
    }

    public function delete(int $id): void
    {
        Database::delete('registrations', ['id' => $id]);
    }

    /**
     * Statistiques du tableau de bord.
     *
     * @return array<string, int>
     */
    public function dashboardCounters(int $trainingId): array
    {
        $rows = Database::select(
            'SELECT r.status, COUNT(*) AS total FROM registrations r
             WHERE r.training_id = :training GROUP BY r.status',
            ['training' => $trainingId]
        );

        $counters = [
            'draft' => 0,
            'pending_payment' => 0,
            'confirmed' => 0,
            'waitlist' => 0,
            'cancelled' => 0,
        ];

        foreach ($rows as $row) {
            $counters[(string) $row['status']] = (int) $row['total'];
        }

        $counters['active'] = $counters['pending_payment'] + $counters['confirmed'];

        return $counters;
    }

    /** Purge des inscriptions abandonnees (RG-02). */
    public function purgeExpiredDrafts(int $hours = 48): int
    {
        $threshold = date('Y-m-d H:i:s', time() - $hours * 3600);

        return Database::run(
            "DELETE FROM registrations WHERE status = 'draft' AND created_at < :threshold",
            ['threshold' => $threshold]
        )->rowCount();
    }
}

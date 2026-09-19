<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

final class TrainingRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        return Database::selectOne(
            "SELECT * FROM training WHERE status IN ('open', 'closed') ORDER BY starts_on ASC LIMIT 1"
        ) ?? Database::selectOne('SELECT * FROM training ORDER BY starts_on ASC LIMIT 1');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM training WHERE id = :id', ['id' => $id]);
    }

    /**
     * Creneaux de la formation, ordonnes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function days(int $trainingId): array
    {
        return Database::select(
            'SELECT * FROM training_days WHERE training_id = :id ORDER BY position ASC, day_date ASC',
            ['id' => $trainingId]
        );
    }

    /**
     * Creneaux groupes par journee, pour l'affichage du programme.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function daysGrouped(int $trainingId): array
    {
        $grouped = [];

        foreach ($this->days($trainingId) as $day) {
            $grouped[(string) $day['day_date']][] = $day;
        }

        return $grouped;
    }

    /** Nombre de creneaux comptant pour le calcul de presence. */
    public function attendanceSlotCount(int $trainingId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM training_days WHERE training_id = :id AND counts_for_attendance = 1',
            ['id' => $trainingId]
        );
    }

    public function confirmedCount(int $trainingId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM registrations
             WHERE training_id = :id AND status IN ('pending_payment', 'confirmed')",
            ['id' => $trainingId]
        );
    }

    public function remainingSeats(int $trainingId): int
    {
        $training = $this->find($trainingId);

        if ($training === null) {
            return 0;
        }

        return max(0, (int) $training['capacity'] - $this->confirmedCount($trainingId));
    }

    public function isOpen(int $trainingId): bool
    {
        $training = $this->find($trainingId);

        if ($training === null || (string) $training['status'] !== 'open') {
            return false;
        }

        $deadline = $training['registration_deadline'] ?? null;

        if (is_string($deadline) && $deadline !== '' && strtotime($deadline . ' 23:59:59') < time()) {
            return false;
        }

        return strtotime((string) $training['starts_on']) >= strtotime(date('Y-m-d'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('training', $data, ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDay(int $dayId, array $data): void
    {
        Database::update('training_days', $data, ['id' => $dayId]);
    }
}

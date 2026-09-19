<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

/**
 * Presences stockees « a l'envers » : seules les absences sont enregistrees
 * (RG-06). Une ligne absente signifie « present ».
 */
final class AttendanceRepository
{
    /**
     * Absences d'une formation, indexees par inscription puis par creneau.
     *
     * @return array<int, array<int, array{is_justified: bool, reason: string|null}>>
     */
    public function absenceMap(int $trainingId): array
    {
        $rows = Database::select(
            'SELECT a.registration_id, a.training_day_id, a.is_justified, a.reason
             FROM absences a
             INNER JOIN registrations r ON r.id = a.registration_id
             WHERE r.training_id = :training',
            ['training' => $trainingId]
        );

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['registration_id']][(int) $row['training_day_id']] = [
                'is_justified' => (bool) $row['is_justified'],
                'reason' => $row['reason'] === null ? null : (string) $row['reason'],
            ];
        }

        return $map;
    }

    /**
     * Comptage des absences par inscription.
     *
     * @return array<int, array{absences: int, justified: int}>
     */
    public function absenceCounts(int $trainingId): array
    {
        $rows = Database::select(
            'SELECT a.registration_id,
                    SUM(CASE WHEN a.is_justified = 1 THEN 0 ELSE 1 END) AS unjustified,
                    SUM(CASE WHEN a.is_justified = 1 THEN 1 ELSE 0 END) AS justified
             FROM absences a
             INNER JOIN registrations r ON r.id = a.registration_id
             INNER JOIN training_days d ON d.id = a.training_day_id
             WHERE r.training_id = :training AND d.counts_for_attendance = 1
             GROUP BY a.registration_id',
            ['training' => $trainingId]
        );

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['registration_id']] = [
                'absences' => (int) $row['unjustified'],
                'justified' => (int) $row['justified'],
            ];
        }

        return $counts;
    }

    public function markAbsent(
        int $registrationId,
        int $trainingDayId,
        bool $justified = false,
        ?string $reason = null,
        ?int $recordedBy = null
    ): void {
        $existing = Database::selectOne(
            'SELECT id FROM absences WHERE registration_id = :registration AND training_day_id = :day',
            ['registration' => $registrationId, 'day' => $trainingDayId]
        );

        if ($existing !== null) {
            Database::update('absences', [
                'is_justified' => $justified ? 1 : 0,
                'reason' => $reason,
                'recorded_by' => $recordedBy,
                'recorded_at' => date('Y-m-d H:i:s'),
            ], ['id' => (int) $existing['id']]);

            return;
        }

        Database::insert('absences', [
            'registration_id' => $registrationId,
            'training_day_id' => $trainingDayId,
            'is_justified' => $justified ? 1 : 0,
            'reason' => $reason,
            'recorded_by' => $recordedBy,
            'recorded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markPresent(int $registrationId, int $trainingDayId): void
    {
        Database::run(
            'DELETE FROM absences WHERE registration_id = :registration AND training_day_id = :day',
            ['registration' => $registrationId, 'day' => $trainingDayId]
        );
    }

    /**
     * Remplace en une fois les absences d'un creneau.
     *
     * @param array<int, int> $absentRegistrationIds
     */
    public function replaceDayAbsences(int $trainingDayId, array $absentRegistrationIds, ?int $recordedBy = null): void
    {
        Database::transaction(function () use ($trainingDayId, $absentRegistrationIds, $recordedBy): void {
            $existing = Database::select(
                'SELECT registration_id, is_justified, reason FROM absences WHERE training_day_id = :day',
                ['day' => $trainingDayId]
            );

            $previous = [];
            foreach ($existing as $row) {
                $previous[(int) $row['registration_id']] = $row;
            }

            Database::run('DELETE FROM absences WHERE training_day_id = :day', ['day' => $trainingDayId]);

            foreach (array_unique($absentRegistrationIds) as $registrationId) {
                // On preserve le motif et le caractere justifie deja saisis.
                $former = $previous[$registrationId] ?? null;

                Database::insert('absences', [
                    'registration_id' => $registrationId,
                    'training_day_id' => $trainingDayId,
                    'is_justified' => $former === null ? 0 : (int) $former['is_justified'],
                    'reason' => $former === null ? null : ($former['reason'] ?? null),
                    'recorded_by' => $recordedBy,
                    'recorded_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    /** Nombre d'absents sur un creneau donne. */
    public function countAbsencesForDay(int $trainingDayId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM absences WHERE training_day_id = :day',
            ['day' => $trainingDayId]
        );
    }
}

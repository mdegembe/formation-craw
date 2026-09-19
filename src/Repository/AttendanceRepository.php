<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

/**
 * Presences stockees « a l'envers » : seules les absences sont enregistrees
 * (RG-06). Une ligne absente signifie « present ».
 *
 * Une absence est une absence : aucun motif n'est collecte.
 */
final class AttendanceRepository
{
    /**
     * Creneaux manques, indexes par inscription puis par creneau.
     *
     * @return array<int, array<int, true>>
     */
    public function absenceMap(int $trainingId): array
    {
        $rows = Database::select(
            'SELECT a.registration_id, a.training_day_id
             FROM absences a
             INNER JOIN registrations r ON r.id = a.registration_id
             WHERE r.training_id = :training',
            ['training' => $trainingId]
        );

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['registration_id']][(int) $row['training_day_id']] = true;
        }

        return $map;
    }

    /**
     * Nombre d'absences par inscription, sur les seuls creneaux comptant pour
     * le calcul de presence.
     *
     * @return array<int, int>
     */
    public function absenceCounts(int $trainingId): array
    {
        $rows = Database::select(
            'SELECT a.registration_id, COUNT(*) AS total
             FROM absences a
             INNER JOIN registrations r ON r.id = a.registration_id
             INNER JOIN training_days d ON d.id = a.training_day_id
             WHERE r.training_id = :training AND d.counts_for_attendance = 1
             GROUP BY a.registration_id',
            ['training' => $trainingId]
        );

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['registration_id']] = (int) $row['total'];
        }

        return $counts;
    }

    public function markAbsent(int $registrationId, int $trainingDayId, ?int $recordedBy = null): void
    {
        $existing = Database::selectOne(
            'SELECT id FROM absences WHERE registration_id = :registration AND training_day_id = :day',
            ['registration' => $registrationId, 'day' => $trainingDayId]
        );

        if ($existing !== null) {
            return;
        }

        Database::insert('absences', [
            'registration_id' => $registrationId,
            'training_day_id' => $trainingDayId,
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
        Database::transaction(static function () use ($trainingDayId, $absentRegistrationIds, $recordedBy): void {
            Database::run('DELETE FROM absences WHERE training_day_id = :day', ['day' => $trainingDayId]);

            $now = date('Y-m-d H:i:s');

            foreach (array_unique($absentRegistrationIds) as $registrationId) {
                Database::insert('absences', [
                    'registration_id' => $registrationId,
                    'training_day_id' => $trainingDayId,
                    'recorded_by' => $recordedBy,
                    'recorded_at' => $now,
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

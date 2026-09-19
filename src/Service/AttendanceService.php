<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Settings;
use Craw\Domain\AttendanceCalculator;
use Craw\Domain\DiplomaEligibility;
use Craw\Repository\AttendanceRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;

/**
 * Agrege presences et eligibilite au diplome pour l'ensemble d'une formation.
 */
final class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepository $attendance = new AttendanceRepository(),
        private readonly RegistrationRepository $registrations = new RegistrationRepository(),
        private readonly TrainingRepository $trainings = new TrainingRepository(),
    ) {
    }

    /**
     * Tableau de synthese : un participant par ligne, avec son taux de presence
     * et son eligibilite.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summary(int $trainingId): array
    {
        $totalSlots = $this->trainings->attendanceSlotCount($trainingId);
        $counts = $this->attendance->absenceCounts($trainingId);
        $justifiedCountsPresent = (bool) Settings::get('attendance.justified_counts_present', false);
        $eligibility = new DiplomaEligibility(Settings::minAttendanceRate());

        $training = $this->trainings->find($trainingId);
        $isFree = $training !== null && (int) $training['price_cents'] <= 0;

        $rows = [];

        foreach ($this->registrations->confirmedParticipants($trainingId) as $participant) {
            $id = (int) $participant['id'];
            $absences = $counts[$id]['absences'] ?? 0;
            $justified = $counts[$id]['justified'] ?? 0;

            $rate = AttendanceCalculator::rate($totalSlots, $absences, $justified, $justifiedCountsPresent);

            $verdict = $eligibility->evaluate(
                $rate,
                (string) $participant['status'],
                (string) ($participant['payment_status'] ?? 'unpaid'),
                $isFree
            );

            $rows[] = $participant + [
                'total_slots' => $totalSlots,
                'absences' => $absences,
                'justified_absences' => $justified,
                'present_slots' => AttendanceCalculator::presentSlots(
                    $totalSlots,
                    $absences,
                    $justified,
                    $justifiedCountsPresent
                ),
                'attendance_rate' => $rate,
                'attendance_label' => AttendanceCalculator::format($rate),
                'eligible' => $verdict['eligible'],
                'eligibility_reason' => $verdict['reason'],
                'eligibility_message' => $verdict['message'],
            ];
        }

        return $rows;
    }

    /**
     * Grille de pointage : participants x creneaux, avec l'etat courant.
     *
     * @return array{days: array<int, array<string, mixed>>, participants: array<int, array<string, mixed>>, absences: array<int, array<int, array{is_justified: bool, reason: string|null}>>}
     */
    public function grid(int $trainingId): array
    {
        return [
            'days' => $this->trainings->days($trainingId),
            'participants' => $this->registrations->confirmedParticipants($trainingId),
            'absences' => $this->attendance->absenceMap($trainingId),
        ];
    }

    /**
     * @return array{eligible: int, not_eligible: int, average_rate: float}
     */
    public function statistics(int $trainingId): array
    {
        $rows = $this->summary($trainingId);

        if ($rows === []) {
            return ['eligible' => 0, 'not_eligible' => 0, 'average_rate' => 0.0];
        }

        $eligible = 0;
        $total = 0.0;

        foreach ($rows as $row) {
            if ($row['eligible'] === true) {
                $eligible++;
            }
            $total += (float) $row['attendance_rate'];
        }

        return [
            'eligible' => $eligible,
            'not_eligible' => count($rows) - $eligible,
            'average_rate' => round($total / count($rows), 4),
        ];
    }
}

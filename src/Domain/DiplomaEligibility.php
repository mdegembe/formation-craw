<?php

declare(strict_types=1);

namespace Craw\Domain;

/**
 * Regle unique d'eligibilite au diplome (RG-07), centralisee et testee.
 *
 * Un participant obtient son diplome si :
 *  - son taux de presence atteint le seuil configure (80 % par defaut) ;
 *  - son inscription est confirmee ;
 *  - son paiement est regle ou l'inscription est exoneree (ou la formation gratuite).
 */
final class DiplomaEligibility
{
    public const REASON_OK = 'ok';
    public const REASON_ATTENDANCE = 'attendance';
    public const REASON_PAYMENT = 'payment';
    public const REASON_STATUS = 'status';

    public function __construct(
        private readonly float $minAttendanceRate,
    ) {
    }

    /**
     * @return array{eligible: bool, reason: string, message: string}
     */
    public function evaluate(
        float $attendanceRate,
        string $registrationStatus,
        string $paymentStatus,
        bool $isFree = false
    ): array {
        if ($registrationStatus !== 'confirmed') {
            return [
                'eligible' => false,
                'reason' => self::REASON_STATUS,
                'message' => 'Inscription non confirmée.',
            ];
        }

        if (!$isFree && !in_array($paymentStatus, ['paid', 'waived'], true)) {
            return [
                'eligible' => false,
                'reason' => self::REASON_PAYMENT,
                'message' => 'Paiement non réglé.',
            ];
        }

        if ($attendanceRate + 1e-9 < $this->minAttendanceRate) {
            return [
                'eligible' => false,
                'reason' => self::REASON_ATTENDANCE,
                'message' => sprintf(
                    'Taux de présence insuffisant (%s, minimum requis %s).',
                    AttendanceCalculator::format($attendanceRate),
                    AttendanceCalculator::format($this->minAttendanceRate)
                ),
            ];
        }

        return [
            'eligible' => true,
            'reason' => self::REASON_OK,
            'message' => 'Éligible au diplôme.',
        ];
    }

    public function minRate(): float
    {
        return $this->minAttendanceRate;
    }
}

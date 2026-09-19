<?php

declare(strict_types=1);

namespace Craw\Domain;

/**
 * Calcul du taux de presence selon le principe « presume present » (RG-06).
 *
 * Seules les absences sont enregistrees : le taux se deduit du nombre total de
 * creneaux moins les absences retenues.
 */
final class AttendanceCalculator
{
    /**
     * @param int  $totalSlots            Nombre total de creneaux de la formation.
     * @param int  $absences              Absences non justifiees.
     * @param int  $justifiedAbsences     Absences justifiees.
     * @param bool $justifiedCountsPresent Une absence justifiee compte-t-elle comme presence ?
     */
    public static function rate(
        int $totalSlots,
        int $absences,
        int $justifiedAbsences = 0,
        bool $justifiedCountsPresent = false
    ): float {
        if ($totalSlots <= 0) {
            return 0.0;
        }

        $missed = $absences + ($justifiedCountsPresent ? 0 : $justifiedAbsences);
        $present = max(0, $totalSlots - $missed);

        return round($present / $totalSlots, 4);
    }

    public static function presentSlots(
        int $totalSlots,
        int $absences,
        int $justifiedAbsences = 0,
        bool $justifiedCountsPresent = false
    ): int {
        $missed = $absences + ($justifiedCountsPresent ? 0 : $justifiedAbsences);

        return max(0, $totalSlots - $missed);
    }

    /** Taux formate pour l'affichage, ex. « 90 % ». */
    public static function format(float $rate): string
    {
        return number_format($rate * 100, $rate * 100 == (int) ($rate * 100) ? 0 : 1, ',', ' ') . "\u{202F}%";
    }
}

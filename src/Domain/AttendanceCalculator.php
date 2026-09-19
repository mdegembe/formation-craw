<?php

declare(strict_types=1);

namespace Craw\Domain;

/**
 * Calcul du taux de presence selon le principe « presume present » (RG-06).
 *
 * Seules les absences sont enregistrees : le taux se deduit du nombre total de
 * creneaux moins les absences constatees.
 *
 * Il n'existe pas d'absence justifiee : un participant est absent ou present.
 * Rien a justifier, rien a arbitrer, rien a collecter.
 */
final class AttendanceCalculator
{
    /**
     * @param int $totalSlots Nombre total de creneaux de la formation.
     * @param int $absences   Creneaux manques.
     */
    public static function rate(int $totalSlots, int $absences): float
    {
        if ($totalSlots <= 0) {
            return 0.0;
        }

        return round(self::presentSlots($totalSlots, $absences) / $totalSlots, 4);
    }

    public static function presentSlots(int $totalSlots, int $absences): int
    {
        return max(0, $totalSlots - max(0, $absences));
    }

    /** Taux formate pour l'affichage, ex. « 90 % ». */
    public static function format(float $rate): string
    {
        $percentage = $rate * 100;

        return number_format(
            $percentage,
            $percentage == (int) $percentage ? 0 : 1,
            ',',
            ' '
        ) . "\u{202F}%";
    }
}

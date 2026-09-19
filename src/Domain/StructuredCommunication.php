<?php

declare(strict_types=1);

namespace Craw\Domain;

use InvalidArgumentException;

/**
 * Communication structuree belge (VCS / OGM).
 *
 * Format : +++DDD/DDDD/DDDCC+++
 *  - 10 chiffres libres ;
 *  - 2 chiffres de controle = les 10 chiffres modulo 97, un reste nul valant 97.
 *
 * Elle est derivee de l'identifiant d'inscription : unique, stable et
 * rapprochable automatiquement d'un extrait bancaire (RG-04).
 */
final class StructuredCommunication
{
    /** Decalage evitant les communications trop courtes pour les premiers inscrits. */
    private const BASE = 1_000_000;

    public static function forRegistration(int $registrationId, int $year): string
    {
        if ($registrationId < 1) {
            throw new InvalidArgumentException('L\'identifiant d\'inscription doit etre positif.');
        }

        // AA (2 chiffres d'annee) + 8 chiffres derives de l'identifiant.
        $base = sprintf('%02d%08d', $year % 100, (self::BASE + $registrationId) % 100_000_000);

        return self::fromDigits($base);
    }

    /**
     * @param string $digits Dix chiffres.
     */
    public static function fromDigits(string $digits): string
    {
        if (preg_match('/^\d{10}$/', $digits) !== 1) {
            throw new InvalidArgumentException('La communication structuree exige exactement 10 chiffres.');
        }

        $checksum = self::checksum($digits);

        return sprintf(
            '+++%s/%s/%s%02d+++',
            substr($digits, 0, 3),
            substr($digits, 3, 4),
            substr($digits, 7, 3),
            $checksum
        );
    }

    public static function checksum(string $digits): int
    {
        // bcmod n'est pas garanti : on calcule le modulo chiffre par chiffre.
        $remainder = 0;

        foreach (str_split($digits) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 0 ? 97 : $remainder;
    }

    public static function isValid(string $communication): bool
    {
        $digits = preg_replace('/\D/', '', $communication) ?? '';

        if (strlen($digits) !== 12) {
            return false;
        }

        return self::checksum(substr($digits, 0, 10)) === (int) substr($digits, 10, 2);
    }

    /** Version sans separateurs, telle qu'attendue par certaines banques. */
    public static function digitsOnly(string $communication): string
    {
        return preg_replace('/\D/', '', $communication) ?? '';
    }
}

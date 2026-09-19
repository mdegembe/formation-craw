<?php

declare(strict_types=1);

namespace Craw\Domain;

use InvalidArgumentException;

/**
 * Charge utile d'un QR code de virement SEPA, norme EPC069-12 version 002.
 *
 * Lu par la majorite des applications bancaires belges et europeennes : le
 * participant scanne le code et son application pre-remplit le virement.
 *
 * Note : la norme EPC ne permet pas d'encoder de facon interoperable une
 * communication structuree belge dans le champ « reference ». On l'ecrit donc
 * dans le champ de communication libre, ce qui est la pratique courante ; la
 * communication structuree reste affichee a l'ecran pour la saisie manuelle.
 */
final class SepaQrPayload
{
    private const SERVICE_TAG = 'BCD';

    private const VERSION = '002';

    private const CHARACTER_SET = '1'; // UTF-8

    private const IDENTIFICATION = 'SCT'; // SEPA Credit Transfer

    public static function build(
        string $beneficiary,
        string $iban,
        int $amountCents,
        string $remittance,
        string $bic = '',
        string $currency = 'EUR'
    ): string {
        $iban = self::normaliseIban($iban);

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban) !== 1) {
            throw new InvalidArgumentException('IBAN invalide pour la generation du QR code SEPA.');
        }

        if ($amountCents < 1 || $amountCents > 99_999_999_9) {
            throw new InvalidArgumentException('Le montant doit etre compris entre 0,01 et 999 999 999,99 EUR.');
        }

        // La norme limite le nom du beneficiaire a 70 caracteres et la
        // communication libre a 140.
        $beneficiary = self::clean($beneficiary, 70);
        $remittance = self::clean($remittance, 140);

        $lines = [
            self::SERVICE_TAG,
            self::VERSION,
            self::CHARACTER_SET,
            self::IDENTIFICATION,
            strtoupper(trim($bic)),
            $beneficiary,
            $iban,
            $currency . number_format($amountCents / 100, 2, '.', ''),
            '',            // Purpose
            '',            // Structured remittance (non utilise, cf. note)
            $remittance,   // Unstructured remittance
            '',            // Information
        ];

        // La norme impose un separateur LF, pas CRLF.
        return implode("\n", $lines);
    }

    public static function normaliseIban(string $iban): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $iban));
    }

    /** IBAN formate par groupes de 4 caracteres, pour l'affichage. */
    public static function formatIban(string $iban): string
    {
        return trim(chunk_split(self::normaliseIban($iban), 4, ' '));
    }

    /** Validation IBAN complete : longueur par pays et modulo 97. */
    public static function isValidIban(string $iban): bool
    {
        $iban = self::normaliseIban($iban);

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban) !== 1) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        $numeric = '';
        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;
        }

        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    private static function clean(string $value, int $maxLength): string
    {
        // La norme n'accepte pas les sauts de ligne dans les champs.
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return mb_substr($value, 0, $maxLength);
    }
}

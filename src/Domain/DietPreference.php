<?php

declare(strict_types=1);

namespace Craw\Domain;

/**
 * Regimes alimentaires proposes a l'inscription.
 *
 * Les statistiques s'appuient sur cette donnee structuree ; les commentaires
 * libres (halal precise, allergies) restent a cote, pour le traiteur.
 */
enum DietPreference: string
{
    case Standard = 'standard';
    case Vegetarien = 'vegetarien';
    case Vegan = 'vegan';
    case SansPorc = 'sans_porc';
    case Halal = 'halal';
    case Casher = 'casher';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Sans restriction',
            self::Vegetarien => 'Végétarien',
            self::Vegan => 'Végétalien (vegan)',
            self::SansPorc => 'Sans porc',
            self::Halal => 'Halal',
            self::Casher => 'Casher',
            self::Autre => 'Autre (à préciser)',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Standard => 'Aucune adaptation particulière du repas.',
            self::Vegetarien => 'Sans viande ni poisson.',
            self::Vegan => 'Sans aucun produit d\'origine animale.',
            self::SansPorc => 'Repas excluant la viande de porc et ses dérivés.',
            self::Halal => 'Merci de préciser vos exigences dans le commentaire.',
            self::Casher => 'Merci de préciser vos exigences dans le commentaire.',
            self::Autre => 'Décrivez votre régime dans le commentaire.',
        };
    }

    /** Un commentaire est-il exige pour ce choix ? */
    public function requiresComment(): bool
    {
        return $this === self::Autre;
    }

    /** Couleur d'accent utilisee dans les graphiques de statistiques. */
    public function color(): string
    {
        return match ($this) {
            self::Standard => '#198754',
            self::Vegetarien => '#4c9a6a',
            self::Vegan => '#75b798',
            self::SansPorc => '#0d7b8a',
            self::Halal => '#1c7ea8',
            self::Casher => '#5a6c8c',
            self::Autre => '#8a7f52',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public static function tryLabel(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->label() ?? 'Non précisé';
    }

    public static function tryColor(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->color() ?? '#adb5bd';
    }
}

<?php

declare(strict_types=1);

namespace Craw\Domain;

use Craw\Core\Locale;

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

    /**
     * Libelle localise. Sans argument, suit la langue de la requete en cours
     * — correcte aussi bien dans le back-office (toujours francais) que dans
     * les pages publiques et les courriels (deja regles sur la bonne langue).
     */
    public function label(?string $locale = null): string
    {
        $locale ??= Locale::current();

        if ($locale === 'en') {
            return match ($this) {
                self::Standard => 'No restriction',
                self::Vegetarien => 'Vegetarian',
                self::Vegan => 'Vegan',
                self::SansPorc => 'No pork',
                self::Halal => 'Halal',
                self::Casher => 'Kosher',
                self::Autre => 'Other (please specify)',
            };
        }

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

    public function hint(?string $locale = null): string
    {
        $locale ??= Locale::current();

        if ($locale === 'en') {
            return match ($this) {
                self::Standard => 'No particular adjustment to the meal.',
                self::Vegetarien => 'No meat or fish.',
                self::Vegan => 'No animal products whatsoever.',
                self::SansPorc => 'Meal excluding pork and pork derivatives.',
                self::Halal => 'Please specify your requirements in the comment.',
                self::Casher => 'Please specify your requirements in the comment.',
                self::Autre => 'Describe your diet in the comment.',
            };
        }

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

    public static function tryLabel(?string $value, ?string $locale = null): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        if ($case !== null) {
            return $case->label($locale);
        }

        return ($locale ?? Locale::current()) === 'en' ? 'Not specified' : 'Non précisé';
    }

    public static function tryColor(?string $value): string
    {
        $case = $value === null ? null : self::tryFrom($value);

        return $case?->color() ?? '#adb5bd';
    }
}

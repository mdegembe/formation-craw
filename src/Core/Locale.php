<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Langue courante de la requete, deduite du prefixe d'URL (/fr, /en).
 *
 * Le site public et les courriels sont bilingues ; le back-office, les taches
 * planifiees et les autres routes techniques restent en francais et ne
 * portent jamais ce prefixe (voir requiresPrefix()).
 */
final class Locale
{
    public const DEFAULT = 'fr';

    /** @var array<int, string> */
    public const SUPPORTED = ['fr', 'en'];

    private static string $current = self::DEFAULT;

    private static ?string $rawPath = null;

    public static function set(string $locale): void
    {
        self::$current = in_array($locale, self::SUPPORTED, true) ? $locale : self::DEFAULT;
    }

    public static function current(): string
    {
        return self::$current;
    }

    /** Chemin brut de la requete en cours, pour construire le lien de bascule de langue. */
    public static function setRawPath(string $path): void
    {
        self::$rawPath = $path;
    }

    /**
     * Une route publique porte le prefixe de langue ; les routes techniques
     * (back-office, cron, medias, assets, sitemap, robots) n'en portent jamais.
     */
    public static function requiresPrefix(string $path): bool
    {
        if ($path === '/sitemap.xml' || $path === '/robots.txt') {
            return false;
        }

        foreach (['/admin', '/cron/', '/media/', '/assets/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    /** URL de la page courante dans une autre langue, pour le selecteur de langue. */
    public static function switchUrl(string $locale): string
    {
        $path = self::$rawPath ?? '/' . self::$current;
        $stripped = (string) preg_replace('#^/(fr|en)(?=/|$)#', '', $path);

        return url($stripped === '' ? '/' : $stripped, $locale);
    }
}

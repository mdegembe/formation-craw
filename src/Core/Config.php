<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Configuration d'infrastructure, lue une fois depuis le fichier .env.
 *
 * Les parametres metier (prix, capacite, seuil de presence, textes) vivent en
 * base de donnees et sont edites depuis le back-office : voir Settings.
 */
final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    private static bool $loaded = false;

    private static ?string $requestBaseUrl = null;

    public static function load(string $rootPath): void
    {
        if (self::$loaded) {
            return;
        }

        $file = rtrim($rootPath, '/') . '/.env';
        if (is_readable($file)) {
            self::$values = self::parse((string) file_get_contents($file));
        }

        self::$loaded = true;
    }

    /**
     * @return array<string, string>
     */
    private static function parse(string $contents): array
    {
        $values = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Retire un commentaire de fin de ligne, sauf s'il est entre guillemets.
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);
            }

            $length = strlen($value);
            if ($length >= 2
                && (($value[0] === '"' && $value[$length - 1] === '"')
                    || ($value[0] === "'" && $value[$length - 1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $values[$key] = $value;
        }

        return $values;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::$values[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return $value === null ? $default : (int) $value;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::get($key);

        return $value === null ? $default : (float) $value;
    }

    public static function isDebug(): bool
    {
        return self::bool('APP_DEBUG', false);
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }

    public static function appUrl(): string
    {
        return self::$requestBaseUrl ?? rtrim(self::get('APP_URL', 'http://localhost:8080') ?? '', '/');
    }

    /**
     * Permet aux liens generes pendant une requete de suivre l'hote reellement
     * utilise par le navigateur (utile en local avec plusieurs alias sur le
     * meme projet). Jamais active en production : on ne veut pas qu'un en-tete
     * Host falsifie puisse influencer les URLs generees (mails, canonical...).
     */
    public static function setRequestBaseUrl(?string $url): void
    {
        if ($url === null || $url === '' || self::isProduction()) {
            self::$requestBaseUrl = null;

            return;
        }

        self::$requestBaseUrl = rtrim($url, '/');
    }
}

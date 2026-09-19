<?php

declare(strict_types=1);

use Craw\Core\Config;
use Craw\Core\Settings;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__);

        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path === '' ? '' : '/' . ltrim($path, '/')));
    }
}

if (!function_exists('e')) {
    /** Echappement HTML — a utiliser sur toute sortie dans les gabarits. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Config::appUrl() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** URL d'un asset, suffixee d'une empreinte pour un cache long et sur. */
    function asset(string $path): string
    {
        $relative = 'assets/' . ltrim($path, '/');
        $file = base_path('public/' . $relative);
        $version = is_file($file) ? substr((string) md5_file($file), 0, 8) : '1';

        return url($relative) . '?v=' . $version;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (!function_exists('money')) {
    /** Formate un montant exprime en centimes. */
    function money(int $cents, string $currency = 'EUR'): string
    {
        $symbol = $currency === 'EUR' ? '€' : $currency;

        return number_format($cents / 100, 2, ',', "\u{202F}") . "\u{202F}" . $symbol;
    }
}

if (!function_exists('fr_date')) {
    /** Date longue en francais, sans dependre de l'extension intl. */
    function fr_date(string|DateTimeInterface|null $date, bool $withWeekday = false): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $value = $date instanceof DateTimeInterface
            ? $date
            : new DateTimeImmutable($date, new DateTimeZone(Config::get('APP_TIMEZONE', 'Europe/Brussels') ?? 'UTC'));

        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $months = [
            1 => 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre',
        ];
        $accented = [
            'fevrier' => 'février',
            'aout' => 'août',
            'decembre' => 'décembre',
        ];

        $month = $months[(int) $value->format('n')];
        $month = $accented[$month] ?? $month;

        $formatted = sprintf('%d %s %s', (int) $value->format('j'), $month, $value->format('Y'));

        if ($withWeekday) {
            $formatted = $days[(int) $value->format('w')] . ' ' . $formatted;
        }

        return $formatted;
    }
}

if (!function_exists('fr_datetime')) {
    function fr_datetime(string|DateTimeInterface|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $value = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date);

        return fr_date($value) . ' à ' . $value->format('H\hi');
    }
}

if (!function_exists('str_excerpt')) {
    function str_excerpt(string $text, int $length = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut, ",;:.- ") . '…';
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }
}

<?php

declare(strict_types=1);

use Craw\Core\Config;
use Craw\Core\Locale;
use Craw\Core\Settings;
use Craw\Core\Translator;

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
    /**
     * @param string|null $locale Force une langue precise (utile hors requete,
     *                            par exemple au moment de composer un courriel).
     *                            Par defaut, la langue de la requete en cours.
     */
    function url(string $path = '/', ?string $locale = null): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = '/' . ltrim($path, '/');

        if (Locale::requiresPrefix($path)) {
            $suffix = $path === '/' ? '' : $path;
            $path = '/' . ($locale ?? Locale::current()) . $suffix;
        }

        return Config::appUrl() . $path;
    }
}

if (!function_exists('t')) {
    /**
     * Chaine d'interface traduite (validation, messages flash, erreurs).
     *
     * @param array<string, string|int> $replace
     */
    function t(string $key, array $replace = [], ?string $locale = null): string
    {
        return Translator::get($key, $replace, $locale);
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
    /** Formate un montant exprime en centimes, a la francaise (ex. « 450,00 € »). */
    function money(int $cents, string $currency = 'EUR'): string
    {
        $symbol = $currency === 'EUR' ? '€' : $currency;

        return number_format($cents / 100, 2, ',', "\u{202F}") . "\u{202F}" . $symbol;
    }
}

if (!function_exists('money_en')) {
    /** Formate un montant exprime en centimes, a l'anglaise (ex. « €450.00 »). */
    function money_en(int $cents, string $currency = 'EUR'): string
    {
        $symbol = $currency === 'EUR' ? '€' : $currency;

        return $symbol . number_format($cents / 100, 2, '.', ',');
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

if (!function_exists('en_date')) {
    /** Date longue en anglais, sans dependre de l'extension intl. */
    function en_date(string|DateTimeInterface|null $date, bool $withWeekday = false): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $value = $date instanceof DateTimeInterface
            ? $date
            : new DateTimeImmutable($date, new DateTimeZone(Config::get('APP_TIMEZONE', 'Europe/Brussels') ?? 'UTC'));

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $months = [
            1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $formatted = sprintf('%d %s %s', (int) $value->format('j'), $months[(int) $value->format('n')], $value->format('Y'));

        if ($withWeekday) {
            $formatted = $days[(int) $value->format('w')] . ' ' . $formatted;
        }

        return $formatted;
    }
}

if (!function_exists('en_datetime')) {
    function en_datetime(string|DateTimeInterface|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $value = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date);

        return en_date($value) . ' at ' . $value->format('H:i');
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

if (!function_exists('localized_field')) {
    /**
     * Valeur d'un champ traduisible (formation, actualite) : la colonne
     * {field}_en si la langue courante est l'anglais et qu'elle est
     * renseignee, sinon la colonne francaise de reference.
     *
     * @param array<string, mixed> $record
     */
    function localized_field(array $record, string $field): mixed
    {
        if (Locale::current() === 'en') {
            $value = $record[$field . '_en'] ?? null;

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $record[$field] ?? null;
    }
}

if (!function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }
}

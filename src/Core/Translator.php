<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Chaines d'interface traduites, chargees depuis lang/{locale}.php.
 *
 * Reserve aux textes decides par le code (messages de validation, flash,
 * erreurs) : le contenu des pages et des courriels vit dans des gabarits
 * dedies par langue (voir View::evaluate()).
 */
final class Translator
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    /**
     * @param array<string, string|int> $replace
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= Locale::current();

        $line = self::load($locale)[$key] ?? self::load(Locale::DEFAULT)[$key] ?? $key;

        foreach ($replace as $name => $value) {
            $line = str_replace(':' . $name, (string) $value, $line);
        }

        return $line;
    }

    /**
     * @return array<string, string>
     */
    private static function load(string $locale): array
    {
        if (!isset(self::$cache[$locale])) {
            $file = base_path('lang/' . $locale . '.php');

            /** @var array<string, string> $lines */
            $lines = is_file($file) ? require $file : [];
            self::$cache[$locale] = $lines;
        }

        return self::$cache[$locale];
    }
}

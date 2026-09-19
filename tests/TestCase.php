<?php

declare(strict_types=1);

namespace Craw\Tests;

/**
 * Micro-cadre de test, sans dependance.
 *
 * PHPUnit serait surdimensionne ici : le projet n'a que trois dependances et
 * doit pouvoir etre verifie sur un hebergement mutualise, avec le seul PHP.
 */
final class TestCase
{
    private static int $passed = 0;

    private static int $failed = 0;

    /** @var array<int, string> */
    private static array $failures = [];

    private static string $currentSuite = '';

    public static function suite(string $name): void
    {
        self::$currentSuite = $name;
        fwrite(STDOUT, "\n\033[1m{$name}\033[0m\n");
    }

    public static function assert(bool $condition, string $description): void
    {
        if ($condition) {
            self::$passed++;
            fwrite(STDOUT, "  \033[32m✓\033[0m {$description}\n");

            return;
        }

        self::$failed++;
        self::$failures[] = self::$currentSuite . ' — ' . $description;
        fwrite(STDOUT, "  \033[31m✗ {$description}\033[0m\n");
    }

    public static function same(mixed $expected, mixed $actual, string $description): void
    {
        $ok = $expected === $actual;

        self::assert($ok, $description);

        if (!$ok) {
            fwrite(STDOUT, sprintf(
                "      attendu : %s\n      obtenu  : %s\n",
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    public static function equals(mixed $expected, mixed $actual, string $description): void
    {
        $ok = $expected == $actual;

        self::assert($ok, $description);

        if (!$ok) {
            fwrite(STDOUT, sprintf(
                "      attendu : %s\n      obtenu  : %s\n",
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    public static function contains(string $needle, string $haystack, string $description): void
    {
        self::assert(str_contains($haystack, $needle), $description);
    }

    public static function notContains(string $needle, string $haystack, string $description): void
    {
        self::assert(!str_contains($haystack, $needle), $description);
    }

    public static function throws(callable $callback, string $description): void
    {
        try {
            $callback();
            self::assert(false, $description);
        } catch (\Throwable) {
            self::assert(true, $description);
        }
    }

    public static function summary(): int
    {
        $total = self::$passed + self::$failed;

        fwrite(STDOUT, "\n" . str_repeat('─', 60) . "\n");

        if (self::$failed === 0) {
            fwrite(STDOUT, sprintf("\033[32m%d assertion(s) — tout est vert.\033[0m\n\n", $total));

            return 0;
        }

        fwrite(STDOUT, sprintf(
            "\033[31m%d échec(s) sur %d assertion(s) :\033[0m\n",
            self::$failed,
            $total
        ));

        foreach (self::$failures as $failure) {
            fwrite(STDOUT, "  • {$failure}\n");
        }

        fwrite(STDOUT, "\n");

        return 1;
    }
}

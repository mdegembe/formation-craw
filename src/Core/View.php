<?php

declare(strict_types=1);

namespace Craw\Core;

use RuntimeException;
use Throwable;

/**
 * Moteur de gabarits en PHP natif : pas de compilation, pas de cache a
 * invalider. L'echappement est explicite via e() dans les vues.
 */
final class View
{
    /** @var array<string, mixed> Donnees partagees par toutes les vues. */
    private static array $shared = [];

    /** @var array<string, string> Sections capturees pour le gabarit parent. */
    private static array $sections = [];

    private static ?string $currentSection = null;

    private static ?string $layout = null;

    /** @var array<string, mixed> */
    private static array $layoutData = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $previousLayout = self::$layout;
        $previousSections = self::$sections;

        self::$layout = null;
        self::$sections = [];

        $content = self::evaluate($template, $data);

        $layout = self::$layout;
        $layoutData = self::$layoutData;
        $sections = self::$sections;

        self::$layout = $previousLayout;
        self::$sections = $previousSections;

        if ($layout === null) {
            return $content;
        }

        $sections['content'] = $content;

        $previousSections = self::$sections;
        self::$sections = $sections;

        try {
            return self::evaluate($layout, array_merge($data, $layoutData));
        } finally {
            self::$sections = $previousSections;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        return self::evaluate($template, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function evaluate(string $template, array $data): string
    {
        $template = trim($template, '/');
        $file = base_path('templates/' . $template . '.php');

        // Un gabarit peut avoir une variante par langue (page.en.php a cote de
        // page.php, qui reste la version francaise de reference). A defaut de
        // variante, la version francaise sert de repli.
        $locale = Locale::current();
        if ($locale !== Locale::DEFAULT) {
            $localized = base_path('templates/' . $template . '.' . $locale . '.php');
            if (is_file($localized)) {
                $file = $localized;
            }
        }

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Gabarit introuvable : "%s".', $template));
        }

        $variables = array_merge(self::$shared, $data);

        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                require $__file;
            })($file, $variables);

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }
    }

    /**
     * Declare le gabarit parent depuis une vue.
     *
     * @param array<string, mixed> $data
     */
    public static function extend(string $layout, array $data = []): void
    {
        self::$layout = $layout;
        self::$layoutData = $data;
    }

    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection === null) {
            throw new RuntimeException('endSection() appele sans startSection() correspondant.');
        }

        self::$sections[self::$currentSection] = (string) ob_get_clean();
        self::$currentSection = null;
    }

    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && trim(self::$sections[$name]) !== '';
    }
}

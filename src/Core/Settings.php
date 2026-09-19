<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Parametres metier stockes en base et editables depuis le back-office :
 * prix, capacite, seuil de presence, coordonnees bancaires, textes.
 */
final class Settings
{
    /** @var array<string, array{value: string|null, type: string}>|null */
    private static ?array $cache = null;

    /** Valeurs par defaut appliquees tant qu'aucune valeur n'est enregistree. */
    public const DEFAULTS = [
        'training.title' => ['Formation CRA-W', 'string'],
        'training.subject' => ['Sujet en cours de définition', 'string'],
        'training.subject_pending' => ['1', 'bool'],
        'diploma.min_attendance' => ['0.80', 'float'],
        'diploma.number_prefix' => ['CRAW', 'string'],
        'payment.iban' => ['BE68 5390 0754 7034', 'string'],
        'payment.bic' => ['GKCCBEBB', 'string'],
        'payment.beneficiary' => ['Centre wallon de Recherches agronomiques', 'string'],
        'payment.deadline_days' => ['15', 'int'],
        'payment.reminder_days' => ['7,3', 'string'],
        'attendance.justified_counts_present' => ['0', 'bool'],
        'contact.email' => ['formation@cra.wallonie.be', 'string'],
        'contact.phone' => ['+32 81 87 40 01', 'string'],
        'privacy.retention_years' => ['3', 'int'],
        'seo.default_description' => [
            'Formation organisée par le Centre wallon de Recherches agronomiques (CRA-W) à Gembloux.',
            'string',
        ],
    ];

    /**
     * @return array<string, array{value: string|null, type: string}>
     */
    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        try {
            $rows = Database::select('SELECT setting_key, setting_value, setting_type FROM settings');
        } catch (\Throwable) {
            // La table n'existe pas encore (avant migration) : on retombe sur les defauts.
            return self::$cache;
        }

        foreach ($rows as $row) {
            self::$cache[(string) $row['setting_key']] = [
                'value' => $row['setting_value'] === null ? null : (string) $row['setting_value'],
                'type' => (string) ($row['setting_type'] ?? 'string'),
            ];
        }

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::load();

        if (isset($settings[$key])) {
            return self::cast($settings[$key]['value'], $settings[$key]['type']);
        }

        if (isset(self::DEFAULTS[$key])) {
            return self::cast(self::DEFAULTS[$key][0], self::DEFAULTS[$key][1]);
        }

        return $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $stored = match ($type) {
            'bool' => $value ? '1' : '0',
            'int' => (string) (int) $value,
            'float' => (string) (float) $value,
            'json' => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => $value === null ? null : (string) $value,
        };

        $exists = Database::scalar('SELECT 1 FROM settings WHERE setting_key = :key', ['key' => $key]);

        if ($exists !== null) {
            Database::update(
                'settings',
                ['setting_value' => $stored, 'setting_type' => $type, 'updated_at' => date('Y-m-d H:i:s')],
                ['setting_key' => $key]
            );
        } else {
            Database::insert('settings', [
                'setting_key' => $key,
                'setting_value' => $stored,
                'setting_type' => $type,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        self::$cache = null;
    }

    private static function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool' => in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true),
            'int' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Seuil de presence exige pour le diplome.
     *
     * La variable d'environnement DIPLOMA_MIN_ATTENDANCE sert de valeur de
     * repli ; le parametre en base, editable par l'administrateur, prime.
     */
    public static function minAttendanceRate(): float
    {
        $stored = self::load()['diploma.min_attendance']['value'] ?? null;

        $rate = $stored !== null
            ? (float) $stored
            : Config::float('DIPLOMA_MIN_ATTENDANCE', 0.80);

        // Tolere une saisie en pourcentage (80) comme en fraction (0.80).
        if ($rate > 1.0) {
            $rate /= 100;
        }

        return max(0.0, min(1.0, $rate));
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}

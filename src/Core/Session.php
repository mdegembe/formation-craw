<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Session PHP durcie : cookie HttpOnly/SameSite, regeneration a la connexion,
 * et messages flash.
 */
final class Session
{
    private const FLASH_KEY = '_flash';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        $secure = Config::isProduction()
            || (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('craw_formation');
        session_start();

        // Fait expirer les messages flash de la requete precedente.
        if (isset($_SESSION[self::FLASH_KEY]['old'])) {
            unset($_SESSION[self::FLASH_KEY]['old']);
        }

        if (isset($_SESSION[self::FLASH_KEY]['new'])) {
            $_SESSION[self::FLASH_KEY]['old'] = $_SESSION[self::FLASH_KEY]['new'];
            unset($_SESSION[self::FLASH_KEY]['new']);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name() ?: 'craw_formation', '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
    }

    /** @param 'success'|'error'|'warning'|'info' $type */
    public static function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY]['new'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array<int, array{type: string, message: string}>
     */
    public static function takeFlashes(): array
    {
        /** @var array<int, array{type: string, message: string}> $flashes */
        $flashes = $_SESSION[self::FLASH_KEY]['old'] ?? [];
        unset($_SESSION[self::FLASH_KEY]['old']);

        return $flashes;
    }

    /** Memorise les valeurs soumises pour reafficher un formulaire en erreur. */
    public static function flashInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public static function takeOldInput(): array
    {
        /** @var array $input */
        $input = $_SESSION['_old_input'] ?? [];
        unset($_SESSION['_old_input']);

        return $input;
    }

    public static function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    public static function takeErrors(): array
    {
        /** @var array $errors */
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        return $errors;
    }
}

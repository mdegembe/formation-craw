<?php

declare(strict_types=1);

namespace Craw\Core;

use Craw\Core\Exception\HttpException;

/**
 * Authentification des administrateurs : hachage moderne, verrouillage
 * progressif apres echecs et regeneration de session a la connexion.
 */
final class Auth
{
    private const SESSION_KEY = '_admin_user_id';

    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    /**
     * @return array{ok: bool, message?: string}
     */
    public static function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));

        $user = Database::selectOne(
            'SELECT * FROM admin_users WHERE email = :email',
            ['email' => $email]
        );

        // Comparaison systematique pour ne pas reveler l'existence du compte
        // par un ecart de temps de reponse.
        $hash = is_array($user) ? (string) $user['password_hash'] : password_hash('placeholder', PASSWORD_DEFAULT);
        $passwordMatches = password_verify($password, $hash);

        if (!is_array($user)) {
            return ['ok' => false, 'message' => 'Identifiants incorrects.'];
        }

        if ((int) $user['is_active'] !== 1) {
            return ['ok' => false, 'message' => 'Ce compte est désactivé.'];
        }

        $lockedUntil = $user['locked_until'] ?? null;
        if (is_string($lockedUntil) && $lockedUntil !== '' && strtotime($lockedUntil) > time()) {
            return [
                'ok' => false,
                'message' => sprintf(
                    'Compte temporairement verrouillé. Réessayez dans %d minute(s).',
                    max(1, (int) ceil((strtotime($lockedUntil) - time()) / 60))
                ),
            ];
        }

        if (!$passwordMatches) {
            self::registerFailure($user);

            return ['ok' => false, 'message' => 'Identifiants incorrects.'];
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Database::update(
                'admin_users',
                ['password_hash' => password_hash($password, PASSWORD_DEFAULT)],
                ['id' => (int) $user['id']]
            );
        }

        Database::update('admin_users', [
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ], ['id' => (int) $user['id']]);

        Session::regenerate();
        Csrf::rotate();
        Session::put(self::SESSION_KEY, (int) $user['id']);
        self::$user = null;

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $user
     */
    private static function registerFailure(array $user): void
    {
        $attempts = (int) $user['failed_attempts'] + 1;

        $data = ['failed_attempts' => $attempts];

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            $data['failed_attempts'] = 0;

            Logger::warning('Compte administrateur verrouille apres echecs repetes.', [
                'admin_user_id' => (int) $user['id'],
            ]);
        }

        Database::update('admin_users', $data, ['id' => (int) $user['id']]);
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
        Csrf::rotate();
        self::$user = null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get(self::SESSION_KEY);

        if (!is_int($id) && !is_numeric($id)) {
            return null;
        }

        $user = Database::selectOne(
            'SELECT * FROM admin_users WHERE id = :id AND is_active = 1',
            ['id' => (int) $id]
        );

        if (!is_array($user)) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        self::$user = $user;

        return $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && (string) $user['role'] === 'admin';
    }

    /**
     * @throws HttpException
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            throw new HttpException(401, 'Authentification requise.');
        }
    }

    /**
     * @throws HttpException
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            throw new HttpException(403, 'Cette action est réservée aux administrateurs.');
        }
    }
}

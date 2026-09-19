<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Limitation de debit persistee en base : resiste au redemarrage et fonctionne
 * en environnement multi-processus, contrairement a un compteur en session.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        self::purge();

        $hash = self::hash($key);
        $since = date('Y-m-d H:i:s', time() - $decaySeconds);

        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = :bucket AND created_at >= :since',
            ['bucket' => $hash, 'since' => $since]
        );

        return $count >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 3600): void
    {
        Database::insert('rate_limits', [
            'bucket' => self::hash($key),
            'expires_at' => date('Y-m-d H:i:s', time() + $decaySeconds),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function clear(string $key): void
    {
        Database::delete('rate_limits', ['bucket' => self::hash($key)]);
    }

    public static function attempts(string $key, int $decaySeconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $decaySeconds);

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = :bucket AND created_at >= :since',
            ['bucket' => self::hash($key), 'since' => $since]
        );
    }

    /** Retire les entrees expirees, de temps en temps seulement. */
    private static function purge(): void
    {
        if (random_int(1, 20) !== 1) {
            return;
        }

        Database::run('DELETE FROM rate_limits WHERE expires_at < :now', ['now' => date('Y-m-d H:i:s')]);
    }

    private static function hash(string $key): string
    {
        return hash_hmac('sha256', $key, Config::get('APP_KEY', 'craw') ?? 'craw');
    }
}

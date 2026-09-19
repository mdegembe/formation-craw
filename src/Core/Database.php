<?php

declare(strict_types=1);

namespace Craw\Core;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Connexion PDO partagee, compatible SQLite (developpement) et MySQL/MariaDB
 * (production). Toutes les requetes passent par des requetes preparees.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private static string $driver = 'sqlite';

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = Config::get('DB_DRIVER', 'sqlite') ?? 'sqlite';
        self::$driver = $driver;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if ($driver === 'sqlite') {
            $path = Config::get('DB_SQLITE_PATH', 'storage/database.sqlite') ?? 'storage/database.sqlite';
            if (!str_starts_with($path, '/')) {
                $path = base_path($path);
            }

            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Impossible de creer le repertoire "%s".', $directory));
            }

            self::$pdo = new PDO('sqlite:' . $path, null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');

            return self::$pdo;
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException(sprintf('Pilote de base de donnees non supporte : "%s".', $driver));
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::int('DB_PORT', 3306),
            Config::get('DB_NAME', 'craw_formation'),
            Config::get('DB_CHARSET', 'utf8mb4')
        );

        self::$pdo = new PDO(
            $dsn,
            Config::get('DB_USER', 'root'),
            Config::get('DB_PASSWORD', ''),
            $options
        );

        self::$pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");

        return self::$pdo;
    }

    public static function driver(): string
    {
        self::connection();

        return self::$driver;
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    /**
     * @param array<string|int, mixed> $bindings
     */
    public static function run(string $sql, array $bindings = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);

        foreach ($bindings as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');

            $type = match (true) {
                is_bool($value) => PDO::PARAM_BOOL,
                is_int($value) => PDO::PARAM_INT,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue($parameter, $value, $type);
        }

        $statement->execute();

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $bindings = []): array
    {
        /** @var array<int, array<string, mixed>> $rows */
        $rows = self::run($sql, $bindings)->fetchAll();

        return $rows;
    }

    /**
     * @param array<string|int, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = self::run($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $bindings
     */
    public static function scalar(string $sql, array $bindings = []): mixed
    {
        $value = self::run($sql, $bindings)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        self::run($sql, $data);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        $assignments = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $assignments[] = sprintf('%s = :set_%s', $column, $column);
            $bindings['set_' . $column] = $value;
        }

        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = sprintf('%s = :where_%s', $column, $column);
            $bindings['where_' . $column] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $assignments),
            implode(' AND ', $conditions)
        );

        return self::run($sql, $bindings)->rowCount();
    }

    /**
     * @param array<string, mixed> $where
     */
    public static function delete(string $table, array $where): int
    {
        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = sprintf('%s = :%s', $column, $column);
        }

        $sql = sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $conditions));

        return self::run($sql, $where)->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /** Reinitialise la connexion — utilise par les tests. */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}

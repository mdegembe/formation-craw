<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Gomme les differences de DDL entre SQLite et MySQL, pour n'ecrire qu'un seul
 * jeu de migrations.
 */
final class SchemaHelper
{
    public function isSqlite(): bool
    {
        return Database::isSqlite();
    }

    public function primaryKey(): string
    {
        return $this->isSqlite()
            ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
            : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    }

    public function foreignKeyType(): string
    {
        return $this->isSqlite() ? 'INTEGER' : 'INT UNSIGNED';
    }

    public function datetime(): string
    {
        return $this->isSqlite() ? 'TEXT' : 'DATETIME';
    }

    public function date(): string
    {
        return $this->isSqlite() ? 'TEXT' : 'DATE';
    }

    public function text(): string
    {
        return $this->isSqlite() ? 'TEXT' : 'TEXT';
    }

    public function longText(): string
    {
        return $this->isSqlite() ? 'TEXT' : 'MEDIUMTEXT';
    }

    public function json(): string
    {
        return $this->isSqlite() ? 'TEXT' : 'JSON';
    }

    public function boolean(): string
    {
        return $this->isSqlite() ? 'INTEGER' : 'TINYINT(1)';
    }

    public function tableOptions(): string
    {
        return $this->isSqlite() ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /** SQLite n'a pas d'ENUM : on retombe sur VARCHAR + contrainte CHECK. */
    public function enum(string $column, array $values, string $default): string
    {
        $quoted = implode(', ', array_map(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'",
            $values
        ));

        if ($this->isSqlite()) {
            return sprintf(
                "VARCHAR(32) NOT NULL DEFAULT '%s' CHECK (%s IN (%s))",
                $default,
                $column,
                $quoted
            );
        }

        return sprintf("ENUM(%s) NOT NULL DEFAULT '%s'", $quoted, $default);
    }

    public function createTable(string $name, string $definition): void
    {
        Database::run(sprintf('CREATE TABLE IF NOT EXISTS %s (%s)%s', $name, $definition, $this->tableOptions()));
    }

    public function createIndex(string $name, string $table, string $columns, bool $unique = false): void
    {
        $sql = sprintf(
            'CREATE %sINDEX %s%s ON %s (%s)',
            $unique ? 'UNIQUE ' : '',
            $this->isSqlite() ? 'IF NOT EXISTS ' : '',
            $name,
            $table,
            $columns
        );

        try {
            Database::run($sql);
        } catch (\PDOException $exception) {
            // MySQL ne connait pas IF NOT EXISTS sur les index : on ignore un
            // doublon, toute autre erreur est relancee.
            if (!str_contains($exception->getMessage(), 'Duplicate key name')) {
                throw $exception;
            }
        }
    }
}

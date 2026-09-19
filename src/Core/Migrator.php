<?php

declare(strict_types=1);

namespace Craw\Core;

use RuntimeException;

/**
 * Migrations portables SQLite / MySQL.
 *
 * Chaque fichier de database/migrations retourne une closure recevant un
 * assistant de typage, de sorte qu'un seul jeu de migrations serve aux deux
 * moteurs.
 */
final class Migrator
{
    public static function migrate(bool $verbose = true): int
    {
        self::ensureMigrationsTable();

        $applied = array_column(
            Database::select('SELECT migration FROM migrations'),
            'migration'
        );

        $files = glob(base_path('database/migrations/*.php')) ?: [];
        sort($files);

        $batch = (int) Database::scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations') + 1;
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;

            if (!is_callable($migration)) {
                throw new RuntimeException(sprintf('La migration "%s" doit retourner une closure.', $name));
            }

            $migration(new SchemaHelper());

            Database::insert('migrations', [
                'migration' => $name,
                'batch' => $batch,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $count++;

            if ($verbose) {
                fwrite(STDOUT, "  appliquee : {$name}\n");
            }
        }

        return $count;
    }

    private static function ensureMigrationsTable(): void
    {
        $helper = new SchemaHelper();

        Database::run(sprintf(
            'CREATE TABLE IF NOT EXISTS migrations (
                id %s,
                migration VARCHAR(191) NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                applied_at %s NOT NULL
            )%s',
            $helper->primaryKey(),
            $helper->datetime(),
            $helper->tableOptions()
        ));
    }

    public static function fresh(bool $verbose = true): int
    {
        if (Database::isSqlite()) {
            $tables = Database::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            );

            Database::run('PRAGMA foreign_keys = OFF');
            foreach ($tables as $table) {
                Database::run(sprintf('DROP TABLE IF EXISTS "%s"', $table['name']));
            }
            Database::run('PRAGMA foreign_keys = ON');
        } else {
            $tables = Database::select('SHOW TABLES');
            Database::run('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $row) {
                $name = (string) reset($row);
                Database::run(sprintf('DROP TABLE IF EXISTS `%s`', $name));
            }
            Database::run('SET FOREIGN_KEY_CHECKS = 1');
        }

        Settings::flush();

        return self::migrate($verbose);
    }
}

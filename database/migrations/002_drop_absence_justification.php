<?php

declare(strict_types=1);

use Craw\Core\Database;
use Craw\Core\SchemaHelper;

/**
 * Suppression de la notion d'absence justifiee.
 *
 * Decision fonctionnelle : un participant est absent ou present, sans motif ni
 * exception. Le taux de presence reflete la presence reelle, et l'organisateur
 * n'a aucun justificatif a collecter ni a arbitrer.
 *
 * Les colonnes correspondantes sont retirees plutot que laissees inutilisees :
 * une colonne morte finit toujours par etre relue de travers.
 */
return static function (SchemaHelper $schema): void {
    // ALTER TABLE ... DROP COLUMN : MySQL 8, MariaDB 10.2 et SQLite 3.35 le
    // supportent. On tolere l'echec si la colonne a deja disparu.
    foreach (['is_justified', 'reason'] as $column) {
        try {
            Database::run(sprintf('ALTER TABLE absences DROP COLUMN %s', $column));
        } catch (\PDOException $exception) {
            $message = strtolower($exception->getMessage());

            $alreadyGone = str_contains($message, 'no such column')
                || str_contains($message, "can't drop")
                || str_contains($message, 'check that column');

            if (!$alreadyGone) {
                throw $exception;
            }
        }
    }

    // Le parametre de configuration associe n'a plus d'objet.
    Database::run(
        'DELETE FROM settings WHERE setting_key = :key',
        ['key' => 'attendance.justified_counts_present']
    );
};

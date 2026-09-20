<?php

declare(strict_types=1);

use Craw\Core\Database;
use Craw\Core\SchemaHelper;

/**
 * Site bilingue (FR/EN, voir Locale) : chaque inscription conserve la langue
 * choisie a l'inscription pour que ses courriels ulterieurs (rappels, diplome)
 * lui parlent dans la meme langue, et le contenu edite en back-office
 * (formation, actualites) recoit une colonne _en en regard de chaque colonne
 * traduisible. Une colonne vide retombe sur le francais (voir les
 * controleurs publics).
 */
return static function (SchemaHelper $schema): void {
    $text = $schema->text();
    $longText = $schema->longText();

    Database::run("ALTER TABLE registrations ADD COLUMN locale VARCHAR(2) NOT NULL DEFAULT 'fr'");

    Database::run("ALTER TABLE training ADD COLUMN subject_en VARCHAR(190) NULL");
    Database::run("ALTER TABLE training ADD COLUMN summary_en {$text} NULL");
    Database::run("ALTER TABLE training ADD COLUMN description_en {$text} NULL");
    Database::run("ALTER TABLE training ADD COLUMN practical_info_en {$text} NULL");
    Database::run("ALTER TABLE training ADD COLUMN meta_title_en VARCHAR(190) NULL");
    Database::run("ALTER TABLE training ADD COLUMN meta_description_en VARCHAR(255) NULL");

    Database::run("ALTER TABLE training_days ADD COLUMN label_en VARCHAR(190) NULL");
    Database::run("ALTER TABLE training_days ADD COLUMN topic_en VARCHAR(190) NULL");

    Database::run("ALTER TABLE news ADD COLUMN title_en VARCHAR(190) NULL");
    Database::run("ALTER TABLE news ADD COLUMN excerpt_en VARCHAR(500) NULL");
    Database::run("ALTER TABLE news ADD COLUMN body_en {$longText} NULL");
    Database::run("ALTER TABLE news ADD COLUMN meta_description_en VARCHAR(255) NULL");
};

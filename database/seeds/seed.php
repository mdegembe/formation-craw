<?php

declare(strict_types=1);

use Craw\Core\Database;
use Craw\Core\Settings;
use Craw\Domain\Slugger;

/**
 * Jeu de donnees initial : la formation du 15 au 19 mars 2027, ses creneaux,
 * les parametres metier et deux actualites de demarrage.
 *
 * Le sujet reste volontairement « a definir » : il est editable en back-office.
 */
return static function (bool $withDemoRegistrations = false): void {
    $now = date('Y-m-d H:i:s');

    // ------------------------------------------------------------- parametres
    foreach (Settings::DEFAULTS as $key => [$value, $type]) {
        if (Database::scalar('SELECT 1 FROM settings WHERE setting_key = :key', ['key' => $key]) === null) {
            Settings::set($key, $value, $type);
        }
    }

    // -------------------------------------------------------------- formation
    $existing = Database::selectOne('SELECT id FROM training LIMIT 1');

    if ($existing === null) {
        $trainingId = Database::insert('training', [
            'slug' => 'formation-mars-2027',
            'title' => 'Formation CRA-W — mars 2027',
            'subject' => null,
            'subject_pending' => 1,
            'summary' => 'Une semaine de formation au Centre wallon de Recherches agronomiques, '
                . 'du lundi 15 au vendredi 19 mars 2027, à Gembloux.',
            'description' => "Le Centre wallon de Recherches agronomiques organise une formation de cinq jours "
                . "dans ses installations de Gembloux.\n\n"
                . "Le **programme détaillé et le sujet** de cette session sont en cours de finalisation et seront "
                . "publiés sur cette page dès leur validation. Les inscriptions sont d'ores et déjà ouvertes : "
                . "le nombre de places est limité et les demandes sont traitées par ordre d'arrivée.\n\n"
                . "## À qui s'adresse cette formation ?\n\n"
                . "- aux professionnels du secteur agricole et agroalimentaire ;\n"
                . "- aux conseillers, encadrants et techniciens de terrain ;\n"
                . "- aux chercheurs et enseignants concernés par les thématiques du CRA-W.\n\n"
                . "## Ce que comprend l'inscription\n\n"
                . "- l'accès à l'ensemble des sessions de la semaine ;\n"
                . "- les supports de cours ;\n"
                . "- les pauses café et les repas de midi ;\n"
                . "- un diplôme de participation, délivré sous condition de présence.",
            'practical_info' => "## Accès\n\n"
                . "La formation se déroule au bâtiment Léon Lacroix, 9 rue de Liroux à 5030 Gembloux. "
                . "Le site est accessible en voiture (parking gratuit sur place) et à 25 minutes à pied de la "
                . "gare de Gembloux, desservie par les lignes directes depuis Bruxelles, Namur et Charleroi.\n\n"
                . "## Repas\n\n"
                . "Les pauses et les repas de midi sont inclus. Les régimes alimentaires particuliers et les "
                . "allergies signalés lors de l'inscription sont transmis au traiteur.\n\n"
                . "## Hébergement\n\n"
                . "L'hébergement n'est pas compris. Plusieurs hôtels et gîtes sont disponibles à Gembloux et "
                . "dans les environs immédiats.\n\n"
                . "## Conditions d'annulation\n\n"
                . "Toute annulation communiquée par écrit plus de 15 jours avant le début de la formation donne "
                . "lieu à un remboursement intégral. Passé ce délai, le montant reste dû, mais l'inscription "
                . "peut être transférée à un collègue sans frais.",
            'starts_on' => '2027-03-15',
            'ends_on' => '2027-03-19',
            'location_name' => 'CRA-W — Bâtiment Léon Lacroix',
            'location_address' => '9 rue de Liroux, 5030 Gembloux, Belgique',
            'location_latitude' => '50.5560',
            'location_longitude' => '4.7120',
            'price_cents' => 45000,
            'currency' => 'EUR',
            'capacity' => 40,
            'registration_deadline' => '2027-03-01',
            'status' => 'open',
            'meta_title' => 'Formation CRA-W du 15 au 19 mars 2027 à Gembloux',
            'meta_description' => 'Formation de cinq jours organisée par le Centre wallon de Recherches '
                . 'agronomiques du 15 au 19 mars 2027 à Gembloux. Inscriptions ouvertes, places limitées.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ------------------------------------------------------- creneaux
        // 5 jours x 2 demi-journees = 10 creneaux de presence.
        $days = [
            '2027-03-15' => 'Lundi 15 mars',
            '2027-03-16' => 'Mardi 16 mars',
            '2027-03-17' => 'Mercredi 17 mars',
            '2027-03-18' => 'Jeudi 18 mars',
            '2027-03-19' => 'Vendredi 19 mars',
        ];

        $position = 0;
        foreach ($days as $date => $label) {
            foreach ([['am', '09:00', '12:30'], ['pm', '13:30', '17:00']] as [$slot, $start, $end]) {
                Database::insert('training_days', [
                    'training_id' => $trainingId,
                    'day_date' => $date,
                    'slot' => $slot,
                    'label' => $label . ' — ' . ($slot === 'am' ? 'matin' : 'après-midi'),
                    'topic' => 'Programme en cours de définition',
                    'speaker' => null,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'position' => $position++,
                    'counts_for_attendance' => 1,
                ]);
            }
        }
    }

    // ------------------------------------------------------------ actualites
    if (Database::scalar('SELECT COUNT(*) FROM news') === 0) {
        $articles = [
            [
                'title' => 'Les inscriptions à la formation de mars 2027 sont ouvertes',
                'excerpt' => 'Le CRA-W ouvre les inscriptions à sa formation de cinq jours, '
                    . 'du 15 au 19 mars 2027 à Gembloux. Nombre de places limité.',
                'body' => "Les inscriptions à la formation organisée par le Centre wallon de Recherches "
                    . "agronomiques sont ouvertes.\n\n"
                    . "La session se tiendra **du lundi 15 au vendredi 19 mars 2027** au bâtiment Léon Lacroix, "
                    . "sur le site de Gembloux. Elle est limitée à 40 participants ; les demandes sont traitées "
                    . "par ordre d'arrivée.\n\n"
                    . "## Comment s'inscrire\n\n"
                    . "L'inscription se fait entièrement en ligne, sans création de compte. Le formulaire vous "
                    . "demande vos coordonnées ainsi que vos éventuelles contraintes alimentaires, afin que le "
                    . "traiteur puisse adapter les repas.\n\n"
                    . "Le paiement s'effectue par virement bancaire. Vous recevez, par courriel, une confirmation "
                    . "reprenant les coordonnées bancaires, la communication structurée à indiquer et un QR code "
                    . "permettant de pré-remplir le virement depuis votre application bancaire.\n\n"
                    . "## Le programme\n\n"
                    . "Le sujet et le programme détaillé de cette session sont en cours de finalisation. Ils "
                    . "seront publiés sur cette page dès leur validation, et communiqués par courriel à toutes "
                    . "les personnes déjà inscrites.",
                'days_ago' => 4,
            ],
            [
                'title' => 'Repas, allergies et régimes particuliers : ce que nous demandons à l\'inscription',
                'excerpt' => 'Pourquoi le formulaire d\'inscription pose des questions sur votre alimentation, '
                    . 'et ce que nous faisons de ces informations.',
                'body' => "Les pauses et les repas de midi sont compris dans le prix de la formation. Pour que "
                    . "chacun puisse déjeuner sereinement, le formulaire d'inscription comporte une étape "
                    . "consacrée à l'alimentation.\n\n"
                    . "## Ce que nous demandons\n\n"
                    . "- votre **régime** : sans restriction, végétarien, végétalien, sans porc, halal, casher "
                    . "ou autre ;\n"
                    . "- un **commentaire libre** si votre régime demande une précision ;\n"
                    . "- vos **allergies et intolérances**, décrites aussi précisément que possible.\n\n"
                    . "## Ce que nous en faisons\n\n"
                    . "Ces informations servent exclusivement à la commande des repas. Les effectifs par régime "
                    . "et le détail des allergies sont transmis au traiteur, et à personne d'autre.\n\n"
                    . "Les allergies constituent des données relatives à la santé : elles ne sont recueillies "
                    . "qu'avec votre accord explicite, ne sont accessibles qu'aux personnes chargées de la "
                    . "logistique des repas, et sont supprimées à l'issue de la période de conservation indiquée "
                    . "dans notre [déclaration de confidentialité](/confidentialite).",
                'days_ago' => 1,
            ],
        ];

        foreach ($articles as $article) {
            $publishedAt = date('Y-m-d H:i:s', strtotime('-' . $article['days_ago'] . ' days'));

            Database::insert('news', [
                'slug' => Slugger::slugify($article['title']),
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'status' => 'published',
                'published_at' => $publishedAt,
                'meta_description' => mb_substr($article['excerpt'], 0, 155),
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
        }
    }

    if ($withDemoRegistrations) {
        (require __DIR__ . '/demo_registrations.php')();
    }
};

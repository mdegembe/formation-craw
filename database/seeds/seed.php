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
            'summary_en' => 'A week-long training at the Walloon Agricultural Research Centre, '
                . 'from Monday 15 to Friday 19 March 2027, in Gembloux.',
            'description_en' => "The Walloon Agricultural Research Centre is organising a five-day training "
                . "at its Gembloux facilities.\n\n"
                . "The **detailed programme and subject** of this session are being finalised and will be "
                . "published on this page as soon as they are confirmed. Registration is already open: "
                . "places are limited and requests are handled on a first-come, first-served basis.\n\n"
                . "## Who is this training for?\n\n"
                . "- professionals in the agricultural and agri-food sector;\n"
                . "- advisors, supervisors and field technicians;\n"
                . "- researchers and teachers concerned by CRA-W's areas of work.\n\n"
                . "## What registration includes\n\n"
                . "- access to all sessions during the week;\n"
                . "- course materials;\n"
                . "- coffee breaks and lunches;\n"
                . "- a certificate of attendance, issued subject to attendance requirements.",
            'practical_info_en' => "## Access\n\n"
                . "The training takes place at the Léon Lacroix building, 9 rue de Liroux, 5030 Gembloux. "
                . "The venue is accessible by car (free on-site parking) and a 25-minute walk from "
                . "Gembloux railway station, served by direct lines from Brussels, Namur and Charleroi.\n\n"
                . "## Meals\n\n"
                . "Breaks and lunches are included. Special diets and allergies reported at registration "
                . "are passed on to the caterer.\n\n"
                . "## Accommodation\n\n"
                . "Accommodation is not included. Several hotels and guesthouses are available in Gembloux and "
                . "the immediate area.\n\n"
                . "## Cancellation terms\n\n"
                . "Any cancellation communicated in writing more than 15 days before the start of the training "
                . "entitles you to a full refund. After this deadline, the amount remains due, but the "
                . "registration can be transferred to a colleague free of charge.",
            'meta_title_en' => 'CRA-W training from 15 to 19 March 2027 in Gembloux',
            'meta_description_en' => 'Five-day training organised by the Walloon Agricultural Research Centre '
                . 'from 15 to 19 March 2027 in Gembloux. Registration open, limited places.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ------------------------------------------------------- creneaux
        // 5 jours x 2 demi-journees = 10 creneaux de presence.
        $days = [
            '2027-03-15' => ['Lundi 15 mars', 'Monday 15 March'],
            '2027-03-16' => ['Mardi 16 mars', 'Tuesday 16 March'],
            '2027-03-17' => ['Mercredi 17 mars', 'Wednesday 17 March'],
            '2027-03-18' => ['Jeudi 18 mars', 'Thursday 18 March'],
            '2027-03-19' => ['Vendredi 19 mars', 'Friday 19 March'],
        ];

        $position = 0;
        foreach ($days as $date => [$labelFr, $labelEn]) {
            foreach ([['am', '09:00', '12:30'], ['pm', '13:30', '17:00']] as [$slot, $start, $end]) {
                Database::insert('training_days', [
                    'training_id' => $trainingId,
                    'day_date' => $date,
                    'slot' => $slot,
                    'label' => $labelFr . ' — ' . ($slot === 'am' ? 'matin' : 'après-midi'),
                    'label_en' => $labelEn . ' — ' . ($slot === 'am' ? 'morning' : 'afternoon'),
                    'topic' => 'Programme en cours de définition',
                    'topic_en' => 'Programme being finalised',
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
                'title_en' => 'Registration for the March 2027 training is now open',
                'excerpt_en' => 'CRA-W is opening registration for its five-day training, '
                    . 'from 15 to 19 March 2027 in Gembloux. Limited number of places.',
                'body_en' => "Registration for the training organised by the Walloon Agricultural Research "
                    . "Centre is now open.\n\n"
                    . "The session will take place **from Monday 15 to Friday 19 March 2027** at the Léon "
                    . "Lacroix building, on the Gembloux site. It is limited to 40 participants; requests are "
                    . "handled on a first-come, first-served basis.\n\n"
                    . "## How to register\n\n"
                    . "Registration is done entirely online, with no account creation. The form asks for your "
                    . "details as well as any dietary requirements, so that the caterer can adapt the meals.\n\n"
                    . "Payment is made by bank transfer. You will receive, by email, a confirmation with the "
                    . "bank details, the structured reference to use and a QR code that pre-fills the transfer "
                    . "from your banking app.\n\n"
                    . "## The programme\n\n"
                    . "The subject and detailed programme of this session are being finalised. They will be "
                    . "published on this page as soon as they are confirmed, and sent by email to everyone "
                    . "already registered.",
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
                'title_en' => 'Meals, allergies and special diets: what we ask at registration',
                'excerpt_en' => 'Why the registration form asks about your diet, '
                    . 'and what we do with that information.',
                'body_en' => "Breaks and lunches are included in the price of the training. So that "
                    . "everyone can eat comfortably, the registration form includes a step "
                    . "dedicated to food.\n\n"
                    . "## What we ask\n\n"
                    . "- your **diet**: no restriction, vegetarian, vegan, no pork, halal, kosher "
                    . "or other;\n"
                    . "- a **free-text comment** if your diet needs clarification;\n"
                    . "- your **allergies and intolerances**, described as precisely as possible.\n\n"
                    . "## What we do with it\n\n"
                    . "This information is used exclusively to order meals. Headcounts by diet "
                    . "and allergy details are passed on to the caterer, and to no one else.\n\n"
                    . "Allergies are health-related data: they are only collected with your explicit "
                    . "consent, only accessible to staff in charge of meal logistics, and deleted at "
                    . "the end of the retention period stated in our "
                    . "[privacy notice](/confidentialite).",
            ],
        ];

        foreach ($articles as $article) {
            $publishedAt = date('Y-m-d H:i:s', strtotime('-' . $article['days_ago'] . ' days'));

            Database::insert('news', [
                'slug' => Slugger::slugify($article['title']),
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'title_en' => $article['title_en'] ?? null,
                'excerpt_en' => $article['excerpt_en'] ?? null,
                'body_en' => $article['body_en'] ?? null,
                'status' => 'published',
                'published_at' => $publishedAt,
                'meta_description' => mb_substr($article['excerpt'], 0, 155),
                'meta_description_en' => isset($article['excerpt_en']) ? mb_substr($article['excerpt_en'], 0, 155) : null,
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);
        }
    }

    if ($withDemoRegistrations) {
        (require __DIR__ . '/demo_registrations.php')();
    }
};

<?php

declare(strict_types=1);

use Craw\Core\Database;
use Craw\Repository\AttendanceRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\PaymentService;
use Craw\Service\RegistrationService;

/**
 * Jeu de demonstration : 24 inscriptions couvrant tous les cas (regimes,
 * allergies, paiements payes / impayes / partiels, absences variees).
 *
 * Sert a verifier les ecrans, les statistiques et l'eligibilite au diplome
 * sans devoir saisir des dizaines de formulaires a la main.
 */
return static function (): void {
    $trainings = new TrainingRepository();
    $training = $trainings->current();

    if ($training === null) {
        return;
    }

    $trainingId = (int) $training['id'];

    if ((int) Database::scalar('SELECT COUNT(*) FROM registrations WHERE training_id = :id', ['id' => $trainingId]) > 0) {
        return;
    }

    $people = [
        ['Mme', 'Lambert', 'Sophie', 'standard', false, null, null],
        ['M.', 'Dupont', 'Nicolas', 'vegetarien', false, null, null],
        ['Mme', 'Moreau', 'Claire', 'standard', true, null, 'Allergie aux fruits à coque (arachides, noisettes).'],
        ['M.', 'Petit', 'Antoine', 'halal', false, 'Viande halal uniquement ; poisson accepté.', null],
        ['Mme', 'Leroy', 'Fatima', 'halal', true, 'Repas halal.', 'Intolérance au lactose.'],
        ['M.', 'Roux', 'Julien', 'standard', false, null, null],
        ['Mme', 'Fontaine', 'Élodie', 'vegan', false, null, null],
        ['M.', 'Girard', 'Marc', 'sans_porc', false, null, null],
        ['Mme', 'Bonnet', 'Amandine', 'standard', true, null, 'Maladie cœliaque : régime strictement sans gluten.'],
        ['M.', 'Dumont', 'Pierre', 'standard', false, null, null],
        ['Mme', 'Lefèvre', 'Céline', 'vegetarien', false, null, null],
        ['M.', 'Mercier', 'Thomas', 'standard', false, null, null],
        ['Mme', 'Blanc', 'Nathalie', 'casher', false, 'Repas casher, traiteur agréé si possible.', null],
        ['M.', 'Guerin', 'Olivier', 'standard', false, null, null],
        ['Mme', 'Muller', 'Sarah', 'standard', true, null, 'Allergie aux crustacés et aux mollusques.'],
        ['M.', 'Henry', 'David', 'autre', false, 'Régime pauvre en sel pour raisons médicales.', null],
        ['Mme', 'Rousseau', 'Julie', 'vegetarien', false, null, null],
        ['M.', 'Colin', 'Benoît', 'standard', false, null, null],
        ['Mme', 'Vincent', 'Manon', 'standard', false, null, null],
        ['M.', 'Marchal', 'Gilles', 'standard', false, null, null],
        ['Mme', 'Simon', 'Aurélie', 'vegan', true, null, 'Allergie au soja.'],
        ['M.', 'Robin', 'Laurent', 'standard', false, null, null],
        ['Mme', 'Noel', 'Isabelle', 'standard', false, null, null],
        ['M.', 'Perrin', 'Frédéric', 'sans_porc', false, null, null],
    ];

    $organisations = [
        'Coopérative agricole de Wallonie', 'Université de Liège — Gembloux Agro-Bio Tech',
        'SPW Agriculture', 'Ferme du Grand Chêne', 'Collège provincial de Namur',
        'Bureau d\'études AgriConseil', 'Indépendant', 'Fédération Wallonne de l\'Agriculture',
    ];

    $service = new RegistrationService();
    $payments = new PaymentService();
    $attendance = new AttendanceRepository();

    $createdIds = [];

    foreach ($people as $index => [$civility, $lastName, $firstName, $diet, $hasAllergies, $dietComment, $allergyComment]) {
        $slug = strtolower((string) preg_replace('/[^a-z]/i', '', $firstName . '.' . $lastName));
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . (40 - $index) . ' days'));

        $id = $service->createDraft($trainingId, [
            'civility' => $civility,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'email' => $slug . '@exemple.be',
            'phone' => '+32 4' . random_int(70, 99) . ' ' . random_int(100000, 999999),
            'organisation' => $organisations[$index % count($organisations)],
            'job_title' => 'Participant',
            'street' => 'Rue de l\'Exemple ' . random_int(1, 120),
            'postal_code' => (string) random_int(4000, 6999),
            'city' => 'Gembloux',
            'country' => 'Belgique',
            'diet' => $diet,
            'diet_comment' => $dietComment,
            'has_allergies' => $hasAllergies ? 1 : 0,
            'allergies_comment' => $allergyComment,
            'logistics_comment' => $index === 3 ? 'Arrivée décalée le lundi matin (train).' : null,
            'consent_gdpr_at' => $createdAt,
            'consent_terms_at' => $createdAt,
            'consent_health_at' => $hasAllergies ? $createdAt : null,
            'created_at' => $createdAt,
        ]);

        $service->confirm($id, $training);
        $createdIds[] = $id;
    }

    // -------------------------------------------------------- paiements
    // 18 payes, 2 partiels, 1 exonere, le reste en attente.
    foreach (array_slice($createdIds, 0, 18) as $offset => $id) {
        $payments->confirm(
            $id,
            null,
            date('Y-m-d', strtotime('-' . (20 - $offset) . ' days')),
            'Virement reçu — extrait bancaire n°' . random_int(100, 999),
            null
        );
    }

    foreach (array_slice($createdIds, 18, 2) as $id) {
        $payments->confirm($id, 20000, date('Y-m-d', strtotime('-5 days')), 'Acompte reçu.', null);
    }

    if (isset($createdIds[20])) {
        $payments->waive($createdIds[20], 'Intervenant invité.', null);
    }

    // -------------------------------------------------------- absences
    // Seules les exceptions sont enregistrees (RG-06). On cree des profils
    // varies pour eprouver la regle des 80 %.
    $days = $trainings->days($trainingId);

    if ($days !== []) {
        $absenceProfiles = [
            // [index du participant, indices des creneaux manques]
            [0, [3]],
            [1, [0, 1]],
            [2, [7]],
            [4, [2, 3, 4]],    // 70 % : sous le seuil
            [5, [9]],
            [8, [0, 1, 2, 3]], // 60 % : sous le seuil
            [11, [5]],
            [14, [8, 9]],
            [17, [4]],
        ];

        foreach ($absenceProfiles as [$participantIndex, $dayIndexes]) {
            if (!isset($createdIds[$participantIndex])) {
                continue;
            }

            foreach ($dayIndexes as $dayIndex) {
                if (!isset($days[$dayIndex])) {
                    continue;
                }

                $attendance->markAbsent($createdIds[$participantIndex], (int) $days[$dayIndex]['id']);
            }
        }
    }
};

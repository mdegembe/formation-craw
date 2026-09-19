<?php

declare(strict_types=1);

/**
 * Tests du domaine metier : les regles qui, si elles se trompent, produisent
 * un virement non rapprochable ou un diplome delivre a tort.
 */

use Craw\Domain\AttendanceCalculator;
use Craw\Domain\DietPreference;
use Craw\Domain\DiplomaEligibility;
use Craw\Domain\Markdown;
use Craw\Domain\SepaQrPayload;
use Craw\Domain\Slugger;
use Craw\Domain\StructuredCommunication;
use Craw\Tests\TestCase as T;

// ---------------------------------------------- Communication structurée

T::suite('Communication structurée belge (VCS / OGM)');

$communication = StructuredCommunication::forRegistration(27, 2027);

T::assert(
    preg_match('/^\+\+\+\d{3}\/\d{4}\/\d{5}\+\+\+$/', $communication) === 1,
    'le format +++xxx/xxxx/xxxxx+++ est respecté'
);

T::assert(StructuredCommunication::isValid($communication), 'la clé de contrôle est valide');

T::same(
    12,
    strlen(StructuredCommunication::digitsOnly($communication)),
    'la version sans séparateurs compte 12 chiffres'
);

// Le modulo 97 : un reste nul vaut 97, jamais 0.
T::same(97, StructuredCommunication::checksum('0000000000'), 'un reste nul est ramené à 97');
T::same(1, StructuredCommunication::checksum('0000000001'), 'le modulo 97 est correct sur un petit nombre');
// 1234567890 = 97 x 12 727 504 + 2 (valeur calculée à la main, pour ne pas
// vérifier l'implémentation par elle-même et pour ne pas dépendre de bcmath).
T::same(
    2,
    StructuredCommunication::checksum('1234567890'),
    'le modulo 97 est correct sur un grand nombre'
);

// Chaque inscription doit recevoir une communication distincte.
$seen = [];
for ($i = 1; $i <= 500; $i++) {
    $seen[StructuredCommunication::forRegistration($i, 2027)] = true;
}
T::same(500, count($seen), '500 inscriptions produisent 500 communications distinctes');

// La communication est stable : rejouer la confirmation ne la change pas.
T::same(
    StructuredCommunication::forRegistration(42, 2027),
    StructuredCommunication::forRegistration(42, 2027),
    'la communication est déterministe pour une même inscription'
);

T::assert(
    StructuredCommunication::forRegistration(42, 2027) !== StructuredCommunication::forRegistration(42, 2028),
    'deux années donnent des communications différentes'
);

T::assert(!StructuredCommunication::isValid('+++123/4567/89000+++'), 'une clé fausse est rejetée');
T::assert(!StructuredCommunication::isValid('pas une communication'), 'une chaîne quelconque est rejetée');
T::throws(
    static fn () => StructuredCommunication::forRegistration(0, 2027),
    'un identifiant nul est refusé'
);

// ------------------------------------------------------------ IBAN / SEPA

T::suite('IBAN et charge utile SEPA (EPC069-12)');

T::assert(SepaQrPayload::isValidIban('BE68 5390 0754 7034'), 'un IBAN belge valide est accepté');
T::assert(SepaQrPayload::isValidIban('BE68539007547034'), 'l\'IBAN est accepté sans espaces');
T::assert(!SepaQrPayload::isValidIban('BE68 5390 0754 7035'), 'un IBAN à clé fausse est rejeté');
T::assert(!SepaQrPayload::isValidIban('XX00'), 'un IBAN trop court est rejeté');
T::assert(SepaQrPayload::isValidIban('FR1420041010050500013M02606'), 'un IBAN français valide est accepté');

T::same(
    'BE68 5390 0754 7034',
    SepaQrPayload::formatIban('BE68539007547034'),
    'l\'IBAN est formaté par groupes de quatre'
);

$payload = SepaQrPayload::build(
    'Centre wallon de Recherches agronomiques',
    'BE68 5390 0754 7034',
    45000,
    '+++270/1000/02710+++',
    'GKCCBEBB'
);

$lines = explode("\n", $payload);

T::same('BCD', $lines[0], 'le tag de service est BCD');
T::same('002', $lines[1], 'la version est 002');
T::same('1', $lines[2], 'le jeu de caractères est UTF-8');
T::same('SCT', $lines[3], 'le type d\'opération est un virement SEPA');
T::same('GKCCBEBB', $lines[4], 'le BIC est repris');
T::same('BE68539007547034', $lines[6], 'l\'IBAN est transmis sans espaces');
T::same('EUR450.00', $lines[7], 'le montant utilise le point décimal, comme l\'exige la norme');
T::same('+++270/1000/02710+++', $lines[10], 'la communication est portée par le champ libre');
T::same(12, count($lines), 'la charge utile compte 12 lignes');
T::notContains("\r", $payload, 'le séparateur est LF, jamais CRLF');

T::throws(
    static fn () => SepaQrPayload::build('CRA-W', 'IBAN invalide', 45000, 'test'),
    'un IBAN invalide empêche la génération du QR'
);
T::throws(
    static fn () => SepaQrPayload::build('CRA-W', 'BE68539007547034', 0, 'test'),
    'un montant nul est refusé'
);

// Le nom du bénéficiaire est plafonné à 70 caractères par la norme.
$long = SepaQrPayload::build('X' . str_repeat('y', 200), 'BE68539007547034', 100, 'test');
T::same(70, mb_strlen(explode("\n", $long)[5]), 'le bénéficiaire est tronqué à 70 caractères');
T::same(140, mb_strlen(explode("\n", SepaQrPayload::build(
    'CRA-W',
    'BE68539007547034',
    100,
    str_repeat('a', 300)
))[10]), 'la communication libre est tronquée à 140 caractères');

// ---------------------------------------------------- Calcul de présence

T::suite('Calcul du taux de présence (présomption de présence)');

// La présence est binaire : présent ou absent, sans motif ni exception.

T::same(1.0, AttendanceCalculator::rate(10, 0), 'aucune absence donne 100 %');
T::same(0.8, AttendanceCalculator::rate(10, 2), '2 absences sur 10 créneaux donnent 80 %');
T::same(0.0, AttendanceCalculator::rate(10, 10), 'toutes absences donnent 0 %');
T::same(0.0, AttendanceCalculator::rate(0, 0), 'sans créneau, le taux est nul plutôt qu\'une division par zéro');
T::same(0.0, AttendanceCalculator::rate(10, 15), 'plus d\'absences que de créneaux plafonne à 0 %');

T::same(0.9, AttendanceCalculator::rate(10, 1), 'une seule absence retire un créneau, sans exception');
T::same(0.5, AttendanceCalculator::rate(10, 5), 'la moitié des créneaux manqués donne 50 %');
T::same(1.0, AttendanceCalculator::rate(10, -3), 'un nombre d\'absences négatif est ignoré');

T::same(8, AttendanceCalculator::presentSlots(10, 2), 'le nombre de créneaux présents est cohérent');
T::same(0, AttendanceCalculator::presentSlots(10, 99), 'le nombre de créneaux présents ne devient jamais négatif');

T::contains('80', AttendanceCalculator::format(0.8), 'le taux est formaté en pourcentage');
T::contains('%', AttendanceCalculator::format(0.8), 'le symbole pourcent est présent');

// ------------------------------------------------- Éligibilité au diplôme

T::suite('Éligibilité au diplôme');

$rule = new DiplomaEligibility(0.80);

$verdict = $rule->evaluate(0.80, 'confirmed', 'paid');
T::assert($verdict['eligible'], 'un taux exactement au seuil rend éligible');

$verdict = $rule->evaluate(0.7999, 'confirmed', 'paid');
T::assert(!$verdict['eligible'], 'un taux juste sous le seuil ne rend pas éligible');
T::same(DiplomaEligibility::REASON_ATTENDANCE, $verdict['reason'], 'le motif est bien la présence');

$verdict = $rule->evaluate(1.0, 'confirmed', 'unpaid');
T::assert(!$verdict['eligible'], 'un paiement non réglé bloque le diplôme');
T::same(DiplomaEligibility::REASON_PAYMENT, $verdict['reason'], 'le motif est bien le paiement');

T::assert(
    $rule->evaluate(1.0, 'confirmed', 'waived')['eligible'],
    'une exonération vaut paiement'
);
T::assert(
    $rule->evaluate(1.0, 'confirmed', 'unpaid', true)['eligible'],
    'une formation gratuite dispense de la condition de paiement'
);
T::assert(
    !$rule->evaluate(1.0, 'pending_payment', 'paid')['eligible'],
    'une inscription non confirmée bloque le diplôme'
);
T::assert(
    !$rule->evaluate(1.0, 'cancelled', 'paid')['eligible'],
    'une inscription annulée bloque le diplôme'
);

// Le seuil doit être réellement configurable.
$strict = new DiplomaEligibility(1.0);
T::assert(!$strict->evaluate(0.9, 'confirmed', 'paid')['eligible'], 'un seuil à 100 % est appliqué');
$lenient = new DiplomaEligibility(0.5);
T::assert($lenient->evaluate(0.6, 'confirmed', 'paid')['eligible'], 'un seuil à 50 % est appliqué');

// --------------------------------------------------- Préférences alimentaires

T::suite('Régimes alimentaires');

T::same(7, count(DietPreference::cases()), 'sept régimes sont proposés');
T::assert(DietPreference::Autre->requiresComment(), '« Autre » exige un commentaire');
T::assert(!DietPreference::Standard->requiresComment(), '« Sans restriction » n\'exige pas de commentaire');
T::same('Halal', DietPreference::Halal->label(), 'les libellés sont en français');
T::same('Non précisé', DietPreference::tryLabel('inconnu'), 'une valeur inconnue retombe sur un libellé neutre');
T::same('Non précisé', DietPreference::tryLabel(null), 'une valeur nulle retombe sur un libellé neutre');

foreach (DietPreference::cases() as $diet) {
    T::assert(
        preg_match('/^#[0-9a-f]{6}$/i', $diet->color()) === 1,
        sprintf('la couleur du régime « %s » est un hexadécimal valide', $diet->label())
    );
}

// ------------------------------------------------------------ Markdown

T::suite('Rendu Markdown assaini');

T::contains('<h2>Titre</h2>', Markdown::toHtml('## Titre'), 'les titres de niveau 2 sont rendus');
T::contains('<strong>gras</strong>', Markdown::toHtml('**gras**'), 'le gras est rendu');
T::contains('<em>italique</em>', Markdown::toHtml('*italique*'), 'l\'italique est rendu');
T::contains('<li>un</li>', Markdown::toHtml('- un'), 'les listes à puces sont rendues');
T::contains('<ol>', Markdown::toHtml('1. un'), 'les listes numérotées sont rendues');
T::contains('<blockquote>', Markdown::toHtml('> cité'), 'les citations sont rendues');

// Le point critique : aucun HTML de l'auteur ne doit survivre.
$hostile = Markdown::toHtml('<script>alert(1)</script> et <img src=x onerror=alert(1)>');
T::notContains('<script>', $hostile, 'les balises script sont neutralisées');
T::notContains('<img', $hostile, 'les balises img injectées ne sont pas rendues');
T::contains('&lt;script&gt;', $hostile, 'le HTML injecté est échappé, donc visible en texte');

$link = Markdown::toHtml('[CRA-W](https://www.cra.wallonie.be)');
T::contains('href="https://www.cra.wallonie.be"', $link, 'les liens http(s) sont conservés');
T::contains('rel="noopener noreferrer"', $link, 'les liens externes portent rel="noopener noreferrer"');

$dangerous = Markdown::toHtml('[clic](javascript:alert(1))');
T::notContains('javascript:', $dangerous, 'un lien javascript: est neutralisé');
T::contains('clic', $dangerous, 'le libellé du lien neutralisé reste lisible');

$internal = Markdown::toHtml('[page](/confidentialite)');
T::contains('href="/confidentialite"', $internal, 'les liens internes sont conservés');
T::notContains('target="_blank"', $internal, 'un lien interne ne s\'ouvre pas dans un nouvel onglet');

T::contains('CRA-W', Markdown::toText('## Le **CRA-W**'), 'l\'extraction en texte brut fonctionne');
T::notContains('<', Markdown::toText('## Le **CRA-W**'), 'le texte brut ne contient plus de balises');

// ---------------------------------------------------------------- Slugs

T::suite('Génération des slugs');

T::same('formation-cra-w', Slugger::slugify('Formation CRA-W'), 'les espaces deviennent des tirets');
T::same('a-gembloux-en-fevrier', Slugger::slugify('À Gembloux en février'), 'les accents sont translittérés');
T::same('coeur-de-metier', Slugger::slugify('Cœur de métier'), 'la ligature œ est gérée');
T::same('test', Slugger::slugify('  --Test--  '), 'les tirets superflus sont retirés');
T::same('article', Slugger::slugify('!!!'), 'un titre sans caractère utile retombe sur une valeur par défaut');
T::assert(mb_strlen(Slugger::slugify(str_repeat('mot ', 60))) <= 90, 'la longueur du slug est plafonnée');

<?php

declare(strict_types=1);

/**
 * Tests fonctionnels de bout en bout.
 *
 * Un serveur PHP integre et une base SQLite temporaire sont demarres pour la
 * duree des tests, puis detruits : aucune donnee de developpement n'est
 * touchee, et le test est rejouable a l'identique.
 */

use Craw\Tests\TestCase as T;

// ------------------------------------------------------ environnement isole

$port = 8099;
$root = dirname(__DIR__);
$databasePath = sys_get_temp_dir() . '/craw-test-' . bin2hex(random_bytes(4)) . '.sqlite';
$envPath = $root . '/.env.testing';

file_put_contents($envPath, implode("\n", [
    'APP_ENV=testing',
    'APP_DEBUG=true',
    'APP_URL=http://127.0.0.1:' . $port,
    'APP_TIMEZONE=Europe/Brussels',
    'APP_KEY=' . bin2hex(random_bytes(32)),
    'DB_DRIVER=sqlite',
    'DB_SQLITE_PATH=' . $databasePath,
    'MAIL_TRANSPORT=log',
    'CRON_TOKEN=jetondetestcron',
    'DIPLOMA_MIN_ATTENDANCE=0.80',
    '',
]));

/** Prepare la base du test dans un processus separe, avec le .env dedie. */
$prepare = static function (string $code) use ($root, $envPath): string {
    $script = sprintf(
        '<?php copy(%s, %s); require %s; %s',
        var_export($envPath, true),
        var_export($root . '/.env', true),
        var_export($root . '/src/bootstrap.php', true),
        $code
    );

    $file = tempnam(sys_get_temp_dir(), 'craw-test-') . '.php';
    file_put_contents($file, $script);

    $output = (string) shell_exec(sprintf('php %s 2>&1', escapeshellarg($file)));
    @unlink($file);

    return $output;
};

// On sauvegarde le .env de developpement pour le restaurer a la fin.
$originalEnv = is_file($root . '/.env') ? (string) file_get_contents($root . '/.env') : null;
file_put_contents($root . '/.env', (string) file_get_contents($envPath));

$cleanup = static function () use ($root, $originalEnv, $envPath, $databasePath): void {
    if ($originalEnv !== null) {
        file_put_contents($root . '/.env', $originalEnv);
    }

    @unlink($envPath);

    foreach ([$databasePath, $databasePath . '-wal', $databasePath . '-shm'] as $file) {
        @unlink($file);
    }
};

register_shutdown_function($cleanup);

// ----------------------------------------------------- migration et donnees

shell_exec(sprintf('cd %s && php bin/console.php migrate 2>&1', escapeshellarg($root)));
shell_exec(sprintf('cd %s && php bin/console.php seed 2>&1', escapeshellarg($root)));
shell_exec(sprintf(
    'cd %s && php bin/console.php create-admin %s %s %s 2>&1',
    escapeshellarg($root),
    escapeshellarg('test@cra.wallonie.be'),
    escapeshellarg('Testeur'),
    escapeshellarg('MotDePasseDeTest2027')
));

// ------------------------------------------------------------- serveur HTTP

$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$server = proc_open(
    sprintf('php -S 127.0.0.1:%d -t %s %s', $port, escapeshellarg($root . '/public'), escapeshellarg($root . '/public/index.php')),
    $descriptors,
    $pipes,
    $root
);

register_shutdown_function(static function () use (&$server): void {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
});

$base = 'http://127.0.0.1:' . $port;
$cookieJar = tempnam(sys_get_temp_dir(), 'craw-cookies-');
register_shutdown_function(static fn () => @unlink($cookieJar));

// Attente du demarrage effectif du serveur.
$ready = false;
for ($i = 0; $i < 100; $i++) {
    $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);

    if ($socket !== false) {
        fclose($socket);
        $ready = true;
        break;
    }

    usleep(100_000);
}

T::suite('Serveur de test');
T::assert($ready, 'le serveur de test répond sur le port ' . $port);

if (!$ready) {
    return;
}

/**
 * Requete HTTP via cURL, avec gestion des cookies de session.
 *
 * @param array<string, string> $data
 * @return array{status: int, body: string, location: string, type: string}
 */
$request = static function (string $method, string $path, array $data = []) use ($base, $cookieJar): array {
    $handle = curl_init($base . $path);

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    if ($method === 'POST') {
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $body = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $location = (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL);
    $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);

    curl_close($handle);

    return ['status' => $status, 'body' => $body, 'location' => $location, 'type' => $type];
};

/** Extrait le jeton CSRF d'une page. */
$csrf = static function (string $html): string {
    preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $matches);

    return $matches[1] ?? '';
};

// ------------------------------------------------------------- pages publiques

T::suite('Pages publiques');

foreach ([
    '/' => 'accueil',
    '/programme' => 'programme',
    '/infos-pratiques' => 'infos pratiques',
    '/actualites' => 'actualités',
    '/contact' => 'contact',
    '/inscription' => 'inscription',
    '/mentions-legales' => 'mentions légales',
    '/confidentialite' => 'confidentialité',
    '/accessibilite' => 'accessibilité',
] as $path => $label) {
    $response = $request('GET', $path);
    T::same(200, $response['status'], "la page « {$label} » répond 200");
}

$home = $request('GET', '/');
T::contains('EducationEvent', $home['body'], 'la page d\'accueil expose un JSON-LD EducationEvent');
T::contains('<h1', $home['body'], 'la page d\'accueil comporte un titre de niveau 1');
T::contains('canonical', $home['body'], 'la page d\'accueil déclare une URL canonique');
T::same(
    1,
    substr_count($home['body'], '<h1'),
    'la page d\'accueil ne comporte qu\'un seul titre de niveau 1'
);

T::same(404, $request('GET', '/page-inexistante')['status'], 'une URL inconnue répond 404');

$sitemap = $request('GET', '/sitemap.xml');
T::same(200, $sitemap['status'], 'le sitemap répond 200');
T::contains('<urlset', $sitemap['body'], 'le sitemap est un urlset valide');
T::contains('/programme', $sitemap['body'], 'le sitemap référence la page programme');

$robots = $request('GET', '/robots.txt');
T::contains('Sitemap:', $robots['body'], 'robots.txt déclare le sitemap');
T::contains('Disallow: /admin', $robots['body'], 'robots.txt exclut le back-office');
T::contains('Disallow: /mon-inscription/', $robots['body'], 'robots.txt exclut les espaces personnels');

$rss = $request('GET', '/actualites/rss');
T::contains('<rss', $rss['body'], 'le flux RSS est valide');

// ------------------------------------------------------------ sécurité

T::suite('Sécurité');

$post = $request('POST', '/inscription', ['email' => 'sans.jeton@exemple.be']);
T::same(419, $post['status'], 'une soumission sans jeton CSRF est rejetée');

$admin = $request('GET', '/admin');
T::same(302, $admin['status'], 'le back-office redirige un visiteur non authentifié');
T::contains('/admin/connexion', $admin['location'], 'la redirection mène à l\'écran de connexion');

T::contains('noindex', $request('GET', '/admin/connexion')['body'], 'l\'écran de connexion est en noindex');
T::same(404, $request('GET', '/cron/mauvaisjeton')['status'], 'le cron refuse un jeton invalide');
T::same(200, $request('GET', '/cron/jetondetestcron')['status'], 'le cron accepte le bon jeton');

// Un jeton d'inscription inexistant ne doit rien reveler.
T::same(
    404,
    $request('GET', '/mon-inscription/' . str_repeat('a', 64))['status'],
    'un jeton d\'inscription inconnu répond 404'
);

// ---------------------------------------------------- parcours d'inscription

T::suite('Parcours d\'inscription complet');

$page = $request('GET', '/inscription');
$token = $csrf($page['body']);
T::assert($token !== '', 'le formulaire d\'inscription porte un jeton CSRF');

$step1 = $request('POST', '/inscription', [
    '_token' => $token,
    'civility' => 'Mme',
    'first_name' => 'Camille',
    'last_name' => 'Testeuse',
    'email' => 'camille.testeuse@exemple.be',
    'phone' => '+32 81 12 34 56',
    'organisation' => 'Organisation de test',
]);

T::same(302, $step1['status'], 'l\'étape 1 redirige vers l\'étape suivante');
T::contains('/inscription/preferences/', $step1['location'], 'la redirection mène aux préférences');

preg_match('#/inscription/preferences/([a-f0-9]{64})#', $step1['location'], $matches);
$registrationToken = $matches[1] ?? '';
T::assert($registrationToken !== '', 'un jeton d\'inscription de 64 caractères est attribué');

// Doublon : une deuxieme inscription avec la meme adresse doit etre refusee
// une fois la premiere confirmee (testee plus bas).

$step2Page = $request('GET', '/inscription/preferences/' . $registrationToken);
T::same(200, $step2Page['status'], 'l\'étape 2 est accessible avec le jeton');
T::contains('Halal', $step2Page['body'], 'les régimes alimentaires sont proposés');

// Une allergie declaree sans consentement sante doit etre refusee.
$refused = $request('POST', '/inscription/preferences/' . $registrationToken, [
    '_token' => $csrf($step2Page['body']),
    'diet' => 'halal',
    'diet_comment' => 'Viande halal uniquement.',
    'has_allergies' => '1',
    'allergies_comment' => 'Allergie aux arachides.',
]);
T::contains(
    '/inscription/preferences/',
    $refused['location'],
    'une allergie déclarée sans consentement santé renvoie au formulaire'
);

$step2Page = $request('GET', '/inscription/preferences/' . $registrationToken);
$step2 = $request('POST', '/inscription/preferences/' . $registrationToken, [
    '_token' => $csrf($step2Page['body']),
    'diet' => 'halal',
    'diet_comment' => 'Viande halal uniquement.',
    'has_allergies' => '1',
    'allergies_comment' => 'Allergie aux arachides.',
    'consent_health' => '1',
]);

T::contains('/inscription/recapitulatif/', $step2['location'], 'l\'étape 2 mène au récapitulatif');

$review = $request('GET', '/inscription/recapitulatif/' . $registrationToken);
T::same(200, $review['status'], 'le récapitulatif est accessible');
T::contains('Camille', $review['body'], 'le récapitulatif reprend le prénom saisi');
T::contains('Halal', $review['body'], 'le récapitulatif reprend le régime choisi');
T::contains('arachides', $review['body'], 'le récapitulatif reprend le détail des allergies');

// Sans consentement RGPD, la confirmation doit echouer.
$refusedConfirm = $request('POST', '/inscription/confirmer/' . $registrationToken, [
    '_token' => $csrf($review['body']),
]);
T::contains(
    '/inscription/recapitulatif/',
    $refusedConfirm['location'],
    'la confirmation sans consentement RGPD est refusée'
);

$review = $request('GET', '/inscription/recapitulatif/' . $registrationToken);
$confirm = $request('POST', '/inscription/confirmer/' . $registrationToken, [
    '_token' => $csrf($review['body']),
    'consent_gdpr' => '1',
    'consent_terms' => '1',
]);

T::contains('/inscription/paiement/', $confirm['location'], 'la confirmation mène à la page de paiement');

// ------------------------------------------------------------------ paiement

T::suite('Paiement par virement');

$payment = $request('GET', '/inscription/paiement/' . $registrationToken);
T::same(200, $payment['status'], 'la page de paiement est accessible');
T::contains('BE68', $payment['body'], 'l\'IBAN est affiché');
T::contains('+++', $payment['body'], 'la communication structurée est affichée');
T::contains('qr.png', $payment['body'], 'le QR code de virement est proposé');

$qr = $request('GET', '/inscription/paiement/' . $registrationToken . '/qr.png');
T::same(200, $qr['status'], 'le QR code est servi');
T::contains('image/png', $qr['type'], 'le QR code est une image PNG');
T::same("\x89PNG", substr($qr['body'], 0, 4), 'le fichier est un PNG valide');

$instructions = $request('GET', '/inscription/paiement/' . $registrationToken . '/instructions.pdf');
T::same(200, $instructions['status'], 'les instructions PDF sont servies');
T::same('%PDF', substr($instructions['body'], 0, 4), 'le fichier est un PDF valide');

$space = $request('GET', '/mon-inscription/' . $registrationToken);
T::same(200, $space['status'], 'l\'espace participant est accessible');
T::contains('Camille', $space['body'], 'l\'espace participant affiche le prénom');

// RG-01 : doublon refuse.
$page = $request('GET', '/inscription');
$duplicate = $request('POST', '/inscription', [
    '_token' => $csrf($page['body']),
    'first_name' => 'Camille',
    'last_name' => 'Testeuse',
    'email' => 'camille.testeuse@exemple.be',
    'phone' => '+32 81 12 34 56',
]);
T::contains('/inscription', $duplicate['location'], 'une deuxième inscription avec la même adresse est refusée');

// -------------------------------------------------------------- back-office

T::suite('Back-office');

// Le mauvais mot de passe se teste AVANT toute connexion reussie : une fois la
// session ouverte, l'ecran de connexion redirige et ne fournit plus de jeton.
$badLogin = $request('GET', '/admin/connexion');
$badResult = $request('POST', '/admin/connexion', [
    '_token' => $csrf($badLogin['body']),
    'email' => 'test@cra.wallonie.be',
    'password' => 'mauvais-mot-de-passe',
]);
T::same(302, $badResult['status'], 'un mauvais mot de passe ne connecte pas');
T::contains('/admin/connexion', $badResult['location'], 'un mauvais mot de passe renvoie à l\'écran de connexion');
T::same(302, $request('GET', '/admin')['status'], 'le back-office reste inaccessible après l\'échec');

$login = $request('GET', '/admin/connexion');
$loginResult = $request('POST', '/admin/connexion', [
    '_token' => $csrf($login['body']),
    'email' => 'test@cra.wallonie.be',
    'password' => 'MotDePasseDeTest2027',
]);

T::same(302, $loginResult['status'], 'la connexion aboutit');
T::contains('/admin', $loginResult['location'], 'la connexion mène au tableau de bord');

foreach ([
    '/admin' => 'tableau de bord',
    '/admin/inscriptions' => 'inscriptions',
    '/admin/paiements' => 'paiements',
    '/admin/presences' => 'présences',
    '/admin/statistiques' => 'statistiques',
    '/admin/diplomes' => 'diplômes',
    '/admin/diplomes/modele' => 'modèle de diplôme',
    '/admin/actualites' => 'actualités',
    '/admin/configuration' => 'configuration',
    '/admin/utilisateurs' => 'utilisateurs',
] as $path => $label) {
    T::same(200, $request('GET', $path)['status'], "l'écran « {$label} » répond 200");
}

$export = $request('GET', '/admin/inscriptions/export');
T::same(200, $export['status'], 'l\'export CSV des inscriptions répond 200');
T::contains('text/csv', $export['type'], 'l\'export est bien un CSV');
T::same("\xEF\xBB\xBF", substr($export['body'], 0, 3), 'le CSV porte un BOM UTF-8, pour Excel');
T::contains('Référence;', $export['body'], 'le CSV utilise le point-virgule comme séparateur');
T::notContains(
    'Allergies déclarées',
    $export['body'],
    'l\'export standard exclut les données de santé'
);

$healthExport = $request('GET', '/admin/inscriptions/export?sante=1');
T::contains(
    'Allergies déclarées',
    $healthExport['body'],
    'l\'export dédié inclut les données de santé'
);

$sheet = $request('GET', '/admin/statistiques/traiteur.pdf');
T::same('%PDF', substr($sheet['body'], 0, 4), 'la fiche traiteur est un PDF valide');

$signatures = $request('GET', '/admin/presences/emargement.pdf');
T::same('%PDF', substr($signatures['body'], 0, 4), 'les feuilles d\'émargement sont un PDF valide');

// ------------------------------------------------- confirmation de paiement

T::suite('Confirmation de paiement et diplôme');

$registrationsPage = $request('GET', '/admin/inscriptions');
preg_match('#/admin/inscriptions/(\d+)#', $registrationsPage['body'], $idMatch);
$registrationId = (int) ($idMatch[1] ?? 0);
T::assert($registrationId > 0, 'une inscription est visible dans le back-office');

$detail = $request('GET', '/admin/inscriptions/' . $registrationId);
$confirmPayment = $request('POST', '/admin/paiements/' . $registrationId . '/confirmer', [
    '_token' => $csrf($detail['body']),
    'from' => 'detail',
    'value_date' => date('Y-m-d'),
    'note' => 'Virement de test.',
]);

T::same(302, $confirmPayment['status'], 'la confirmation de paiement aboutit');

$detail = $request('GET', '/admin/inscriptions/' . $registrationId);
T::contains('Payé', $detail['body'], 'le paiement apparaît comme réglé');
T::contains('Confirmée', $detail['body'], 'l\'inscription apparaît comme confirmée');

// Le participant paye et present sur tous les creneaux doit etre eligible.
$diplomas = $request('GET', '/admin/diplomes');
T::contains('Éligible', $diplomas['body'], 'le participant réglé et présent est éligible au diplôme');

$generate = $request('POST', '/admin/diplomes/generer', ['_token' => $csrf($diplomas['body'])]);
T::same(302, $generate['status'], 'la génération des diplômes aboutit');

$diplomas = $request('GET', '/admin/diplomes');
preg_match('#/admin/diplomes/(\d+)/telecharger#', $diplomas['body'], $diplomaMatch);
$diplomaId = (int) ($diplomaMatch[1] ?? 0);
T::assert($diplomaId > 0, 'un diplôme a été généré');

if ($diplomaId > 0) {
    $pdf = $request('GET', '/admin/diplomes/' . $diplomaId . '/telecharger');
    T::same('%PDF', substr($pdf['body'], 0, 4), 'le diplôme généré est un PDF valide');

    $bundle = $request('GET', '/admin/diplomes/impression.pdf');
    T::same('%PDF', substr($bundle['body'], 0, 4), 'le PDF d\'impression groupée est valide');

    $send = $request('POST', '/admin/diplomes/' . $diplomaId . '/envoyer', [
        '_token' => $csrf($diplomas['body']),
    ]);
    T::same(302, $send['status'], 'l\'envoi du diplôme par courriel aboutit');
}

// Verification publique du diplome.
$detailWithDiploma = $request('GET', '/admin/inscriptions/' . $registrationId);
preg_match('#/diplome/verification/([A-Z0-9]{8,40})#', $detailWithDiploma['body'], $codeMatch);

T::assert(
    isset($codeMatch[1]),
    'la fiche d\'inscription expose le code de vérification du diplôme'
);

$verify = $request('GET', '/diplome/verification/' . ($codeMatch[1] ?? 'INTROUVABLE'));
T::same(200, $verify['status'], 'la page de vérification du diplôme répond 200');
T::contains('authentique', $verify['body'], 'le diplôme est reconnu comme authentique');
T::contains('Testeuse', $verify['body'], 'la page de vérification nomme le titulaire');
T::notContains(
    'camille.testeuse@exemple.be',
    $verify['body'],
    'la page de vérification ne divulgue aucune coordonnée'
);

// Le participant retrouve son diplôme depuis son espace personnel.
$spaceWithDiploma = $request('GET', '/mon-inscription/' . $registrationToken);
T::contains('diplôme', $spaceWithDiploma['body'], 'l\'espace participant annonce le diplôme');

$participantPdf = $request('GET', '/mon-inscription/' . $registrationToken . '/diplome');
T::same('%PDF', substr($participantPdf['body'], 0, 4), 'le participant peut télécharger son diplôme');

T::same(
    404,
    $request('GET', '/diplome/verification/CODEBIDON1234')['status'],
    'un code de vérification inconnu répond 404'
);

// ---------------------------------------------------------------- actualités

T::suite('Actualités');

$newsForm = $request('GET', '/admin/actualites/nouvelle');
$created = $request('POST', '/admin/actualites', [
    '_token' => $csrf($newsForm['body']),
    'title' => 'Une actualité de test',
    'body' => "## Sous-titre\n\nUn paragraphe de **test** avec une [référence](https://www.cra.wallonie.be).",
    'status' => 'published',
]);

T::same(302, $created['status'], 'la création d\'une actualité aboutit');

$publicNews = $request('GET', '/actualites');
T::contains('Une actualité de test', $publicNews['body'], 'l\'actualité apparaît sur le site public');

$article = $request('GET', '/actualites/une-actualite-de-test');
T::same(200, $article['status'], 'l\'article est accessible par son slug');
T::contains('<h2>Sous-titre</h2>', $article['body'], 'le Markdown est rendu en HTML');
T::contains('NewsArticle', $article['body'], 'l\'article expose un JSON-LD NewsArticle');

// ------------------------------------------------------------- déconnexion

T::suite('Déconnexion');

$dashboard = $request('GET', '/admin');
$logout = $request('POST', '/admin/deconnexion', ['_token' => $csrf($dashboard['body'])]);
T::same(302, $logout['status'], 'la déconnexion aboutit');
T::same(302, $request('GET', '/admin')['status'], 'le back-office est de nouveau protégé');

<?php

declare(strict_types=1);

use Craw\Controller\Admin\AttendanceController;
use Craw\Controller\Admin\AuthController;
use Craw\Controller\Admin\DashboardController;
use Craw\Controller\Admin\DiplomaController;
use Craw\Controller\Admin\NewsAdminController;
use Craw\Controller\Admin\PaymentAdminController;
use Craw\Controller\Admin\RegistrationAdminController;
use Craw\Controller\Admin\SettingsController;
use Craw\Controller\Admin\StatisticsController;
use Craw\Controller\Admin\UserController;
use Craw\Controller\PublicSite\ContactController;
use Craw\Controller\PublicSite\CronController;
use Craw\Controller\PublicSite\DiplomaPublicController;
use Craw\Controller\PublicSite\HomeController;
use Craw\Controller\PublicSite\MediaController;
use Craw\Controller\PublicSite\NewsController;
use Craw\Controller\PublicSite\PageController;
use Craw\Controller\PublicSite\PaymentController;
use Craw\Controller\PublicSite\RegistrationController;
use Craw\Controller\PublicSite\SeoController;
use Craw\Core\Router;

$router = new Router();

/*
 * Site bilingue (FR/EN) : chaque route publique est prefixee par la langue
 * (/fr/..., /en/...), ce qui la rend indexable separement et compatible avec
 * les balises hreflang. Le back-office et les routes techniques (cron,
 * medias, sitemap) restent non prefixes — voir Locale::requiresPrefix().
 */
$pub = static fn (string $pattern): string => '/{locale:fr|en}' . $pattern;

/* Un visiteur arrivant sans prefixe est redirige vers sa langue (Accept-Language). */
$router->get('/', [HomeController::class, 'redirectToLocale']);

/* ------------------------------------------------------------ Site public */

$router->get($pub(''), [HomeController::class, 'index']);
$router->get($pub('/programme'), [PageController::class, 'programme']);
$router->get($pub('/infos-pratiques'), [PageController::class, 'practical']);
$router->get($pub('/mentions-legales'), [PageController::class, 'legal']);
$router->get($pub('/confidentialite'), [PageController::class, 'privacy']);
$router->get($pub('/accessibilite'), [PageController::class, 'accessibility']);

$router->get($pub('/actualites'), [NewsController::class, 'index']);
$router->get($pub('/actualites/rss'), [NewsController::class, 'rss']);
$router->get($pub('/actualites/{slug:[a-z0-9-]+}'), [NewsController::class, 'show']);

/* ------------------------------------------------------------ Inscription */

$token = '{token:[a-f0-9]{64}}';

$router->get($pub('/inscription'), [RegistrationController::class, 'start']);
$router->post($pub('/inscription'), [RegistrationController::class, 'storeIdentity'], ['throttle']);
$router->get($pub('/inscription/preferences/' . $token), [RegistrationController::class, 'preferences']);
$router->post($pub('/inscription/preferences/' . $token), [RegistrationController::class, 'storePreferences']);
$router->get($pub('/inscription/recapitulatif/' . $token), [RegistrationController::class, 'review']);
$router->post($pub('/inscription/confirmer/' . $token), [RegistrationController::class, 'confirm']);

$router->get($pub('/mon-inscription/' . $token), [RegistrationController::class, 'space']);
$router->get($pub('/mon-inscription/' . $token . '/diplome'), [RegistrationController::class, 'downloadDiploma']);

/* --------------------------------------------------------------- Paiement */

$router->get($pub('/inscription/paiement/' . $token), [PaymentController::class, 'show']);
$router->get($pub('/inscription/paiement/' . $token . '/qr.png'), [PaymentController::class, 'qrCode']);
$router->get($pub('/inscription/paiement/' . $token . '/instructions.pdf'), [PaymentController::class, 'instructions']);

/* ----------------------------------------------------- Verification diplome */

$router->get($pub('/diplome/verification/{code:[A-Za-z0-9]{8,40}}'), [DiplomaPublicController::class, 'verify']);

/* ---------------------------------------------------------------- Contact */

$router->get($pub('/contact'), [ContactController::class, 'show']);
$router->post($pub('/contact'), [ContactController::class, 'submit'], ['throttle']);

/* ------------------------------------------------------------ SEO / medias */

$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/media/news/{file:[A-Za-z0-9._-]+}', [MediaController::class, 'news']);

/* ------------------------------------------------------------------- Cron */

$router->get('/cron/{token:[A-Za-z0-9]{8,64}}', [CronController::class, 'run']);

/* ------------------------------------------------------------- Back-office */

$router->get('/admin/connexion', [AuthController::class, 'showLogin']);
$router->post('/admin/connexion', [AuthController::class, 'login'], ['throttle']);
$router->post('/admin/deconnexion', [AuthController::class, 'logout'], ['auth']);

$admin = ['auth'];

$router->get('/admin', [DashboardController::class, 'index'], $admin);

$router->get('/admin/inscriptions', [RegistrationAdminController::class, 'index'], $admin);
$router->get('/admin/inscriptions/export', [RegistrationAdminController::class, 'export'], $admin);
$router->get('/admin/inscriptions/{id:\d+}', [RegistrationAdminController::class, 'show'], $admin);
$router->post('/admin/inscriptions/{id:\d+}/annuler', [RegistrationAdminController::class, 'cancel'], $admin);
$router->post('/admin/inscriptions/{id:\d+}/renvoyer', [RegistrationAdminController::class, 'resend'], $admin);
$router->post('/admin/inscriptions/{id:\d+}/supprimer', [RegistrationAdminController::class, 'destroy'], $admin);

$router->get('/admin/paiements', [PaymentAdminController::class, 'index'], $admin);
$router->post('/admin/paiements/{id:\d+}/confirmer', [PaymentAdminController::class, 'confirm'], $admin);
$router->post('/admin/paiements/{id:\d+}/exonerer', [PaymentAdminController::class, 'waive'], $admin);
$router->post('/admin/paiements/{id:\d+}/annuler', [PaymentAdminController::class, 'revert'], $admin);
$router->post('/admin/paiements/relancer', [PaymentAdminController::class, 'remind'], $admin);

$router->get('/admin/presences', [AttendanceController::class, 'index'], $admin);
$router->post('/admin/presences', [AttendanceController::class, 'save'], $admin);
$router->get('/admin/presences/export', [AttendanceController::class, 'export'], $admin);
$router->get('/admin/presences/emargement.pdf', [AttendanceController::class, 'signatureSheet'], $admin);

$router->get('/admin/statistiques', [StatisticsController::class, 'index'], $admin);
$router->get('/admin/statistiques/export', [StatisticsController::class, 'export'], $admin);
$router->get('/admin/statistiques/traiteur.pdf', [StatisticsController::class, 'catererSheet'], $admin);

$router->get('/admin/diplomes', [DiplomaController::class, 'index'], $admin);
$router->post('/admin/diplomes/generer', [DiplomaController::class, 'generate'], $admin);
$router->post('/admin/diplomes/{id:\d+}/envoyer', [DiplomaController::class, 'send'], $admin);
$router->post('/admin/diplomes/envoyer-tous', [DiplomaController::class, 'sendAll'], $admin);
$router->get('/admin/diplomes/impression.pdf', [DiplomaController::class, 'printBundle'], $admin);
$router->get('/admin/diplomes/{id:\d+}/telecharger', [DiplomaController::class, 'download'], $admin);
$router->get('/admin/diplomes/modele', [DiplomaController::class, 'templateForm'], $admin);
$router->post('/admin/diplomes/modele', [DiplomaController::class, 'templateSave'], $admin);
$router->post('/admin/diplomes/modele/{id:\d+}/activer', [DiplomaController::class, 'templateActivate'], $admin);
$router->post('/admin/diplomes/modele/{id:\d+}/supprimer', [DiplomaController::class, 'templateDelete'], $admin);
$router->get('/admin/diplomes/modele/apercu.pdf', [DiplomaController::class, 'templatePreview'], $admin);

$router->get('/admin/actualites', [NewsAdminController::class, 'index'], $admin);
$router->get('/admin/actualites/nouvelle', [NewsAdminController::class, 'create'], $admin);
$router->post('/admin/actualites', [NewsAdminController::class, 'store'], $admin);
$router->get('/admin/actualites/{id:\d+}', [NewsAdminController::class, 'edit'], $admin);
$router->post('/admin/actualites/{id:\d+}', [NewsAdminController::class, 'update'], $admin);
$router->post('/admin/actualites/{id:\d+}/supprimer', [NewsAdminController::class, 'destroy'], $admin);

$router->get('/admin/configuration', [SettingsController::class, 'index'], $admin);
$router->post('/admin/configuration', [SettingsController::class, 'save'], $admin);
$router->post('/admin/configuration/programme', [SettingsController::class, 'saveProgramme'], $admin);

$router->get('/admin/utilisateurs', [UserController::class, 'index'], ['auth', 'admin']);
$router->post('/admin/utilisateurs', [UserController::class, 'store'], ['auth', 'admin']);
$router->post('/admin/utilisateurs/{id:\d+}/basculer', [UserController::class, 'toggle'], ['auth', 'admin']);

return $router;

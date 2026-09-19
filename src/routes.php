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

/* ------------------------------------------------------------ Site public */

$router->get('/', [HomeController::class, 'index']);
$router->get('/programme', [PageController::class, 'programme']);
$router->get('/infos-pratiques', [PageController::class, 'practical']);
$router->get('/mentions-legales', [PageController::class, 'legal']);
$router->get('/confidentialite', [PageController::class, 'privacy']);
$router->get('/accessibilite', [PageController::class, 'accessibility']);

$router->get('/actualites', [NewsController::class, 'index']);
$router->get('/actualites/rss', [NewsController::class, 'rss']);
$router->get('/actualites/{slug:[a-z0-9-]+}', [NewsController::class, 'show']);

/* ------------------------------------------------------------ Inscription */

$token = '{token:[a-f0-9]{64}}';

$router->get('/inscription', [RegistrationController::class, 'start']);
$router->post('/inscription', [RegistrationController::class, 'storeIdentity'], ['throttle']);
$router->get('/inscription/preferences/' . $token, [RegistrationController::class, 'preferences']);
$router->post('/inscription/preferences/' . $token, [RegistrationController::class, 'storePreferences']);
$router->get('/inscription/recapitulatif/' . $token, [RegistrationController::class, 'review']);
$router->post('/inscription/confirmer/' . $token, [RegistrationController::class, 'confirm']);

$router->get('/mon-inscription/' . $token, [RegistrationController::class, 'space']);
$router->get('/mon-inscription/' . $token . '/diplome', [RegistrationController::class, 'downloadDiploma']);

/* --------------------------------------------------------------- Paiement */

$router->get('/inscription/paiement/' . $token, [PaymentController::class, 'show']);
$router->get('/inscription/paiement/' . $token . '/qr.png', [PaymentController::class, 'qrCode']);
$router->get('/inscription/paiement/' . $token . '/instructions.pdf', [PaymentController::class, 'instructions']);

/* ----------------------------------------------------- Verification diplome */

$router->get('/diplome/verification/{code:[A-Za-z0-9]{8,40}}', [DiplomaPublicController::class, 'verify']);

/* ---------------------------------------------------------------- Contact */

$router->get('/contact', [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'submit'], ['throttle']);

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

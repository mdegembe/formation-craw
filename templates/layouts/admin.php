<?php

declare(strict_types=1);

use Craw\Core\Auth;
use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * Gabarit du back-office.
 *
 * @var string $title
 * @var string $activeAdminNav
 */
$title = $title ?? 'Administration';
$activeAdminNav = $activeAdminNav ?? '';
$user = Auth::user();

$navigation = [
    'dashboard' => ['label' => 'Tableau de bord', 'url' => '/admin'],
    'registrations' => ['label' => 'Inscriptions', 'url' => '/admin/inscriptions'],
    'payments' => ['label' => 'Paiements', 'url' => '/admin/paiements'],
    'attendance' => ['label' => 'Présences', 'url' => '/admin/presences'],
    'statistics' => ['label' => 'Statistiques', 'url' => '/admin/statistiques'],
    'diplomas' => ['label' => 'Diplômes', 'url' => '/admin/diplomes'],
    'news' => ['label' => 'Actualités', 'url' => '/admin/actualites'],
    'settings' => ['label' => 'Configuration', 'url' => '/admin/configuration'],
];

if ($user !== null && (string) $user['role'] === 'admin') {
    $navigation['users'] = ['label' => 'Utilisateurs', 'url' => '/admin/utilisateurs'];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('craw-theme');
                var dark = stored
                    ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            } catch (e) {}
        })();
    </script>
</head>
<body class="admin-body">
    <a class="skip-link" href="#contenu">Aller au contenu principal</a>

    <div class="admin-shell">
        <header class="admin-topbar no-print">
            <div class="container">
                <div class="admin-topbar__inner">
                    <a class="brand" href="<?= e(url('/admin')) ?>">
                        <span class="brand__mark" aria-hidden="true">CRA-W</span>
                        <span class="brand__text">
                            <span class="brand__name" style="color: #fff;">Administration</span>
                            <span class="brand__tagline" style="color: #adb5bd;">Formation CRA-W</span>
                        </span>
                    </a>

                    <div class="cluster">
                        <?php if ($user !== null): ?>
                            <span class="small" style="color: #adb5bd;">
                                <?= e((string) $user['display_name']) ?>
                                <span class="badge badge--neutral" style="background:#343a40;border-color:#495057;color:#dee2e6;">
                                    <?= e((string) $user['role'] === 'admin' ? 'Administrateur' : 'Gestionnaire') ?>
                                </span>
                            </span>
                        <?php endif; ?>

                        <button type="button" class="theme-toggle" data-theme-toggle
                                aria-label="Changer de thème" aria-pressed="false"
                                style="border-color:#495057;color:#dee2e6;">
                            <svg class="icon-moon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
                            </svg>
                            <svg class="icon-sun" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8"/>
                            </svg>
                        </button>

                        <a class="btn btn--ghost btn--sm" href="<?= e(url('/')) ?>"
                           style="border-color:#495057;color:#dee2e6;" target="_blank" rel="noopener">
                            Voir le site
                        </a>

                        <form method="post" action="<?= e(url('/admin/deconnexion')) ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--ghost btn--sm"
                                    style="border-color:#495057;color:#dee2e6;">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>

                <nav aria-label="Navigation de l'administration">
                    <ul class="admin-nav">
                        <?php foreach ($navigation as $key => $item): ?>
                            <li>
                                <a class="admin-nav__link" href="<?= e(url($item['url'])) ?>"
                                   <?= $activeAdminNav === $key ? 'aria-current="page"' : '' ?>>
                                    <?= e($item['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </header>

        <main id="contenu" class="admin-main">
            <div class="container">
                <?= View::partial('partials/flash') ?>
                <?= View::section('content') ?>
            </div>
        </main>
    </div>

    <div id="live-announcer" class="visually-hidden" role="status" aria-live="polite"></div>

    <script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>

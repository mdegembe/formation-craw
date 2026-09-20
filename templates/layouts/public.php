<?php

declare(strict_types=1);

use Craw\Core\Locale;
use Craw\Core\View;

/**
 * Gabarit du site public.
 *
 * @var string             $title        Titre de la page (<= 60 caracteres)
 * @var string             $description  Meta description (<= 155 caracteres)
 * @var string             $canonical    URL canonique
 * @var array<int, string> $jsonLd       Blocs JSON-LD deja serialises
 * @var string             $activeNav    Identifiant de l'onglet actif
 * @var array<int, array{label: string, url: string}> $breadcrumb
 */
$title = $title ?? 'Formation CRA-W';
$description = $description ?? (string) setting('seo.default_description');
$canonical = $canonical ?? url('/');
$jsonLd = $jsonLd ?? [];
$activeNav = $activeNav ?? '';
$breadcrumb = $breadcrumb ?? [];
$ogImage = $ogImage ?? url('assets/img/og-default.svg');
$noindex = $noindex ?? false;

$fullTitle = str_contains($title, 'CRA-W') ? $title : $title . ' | Formation CRA-W';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e(str_excerpt($description, 155)) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <link rel="alternate" hreflang="fr" href="<?= e(Locale::switchUrl('fr')) ?>">
    <link rel="alternate" hreflang="en" href="<?= e(Locale::switchUrl('en')) ?>">
    <?php if ($noindex): ?>
        <meta name="robots" content="noindex, follow">
    <?php else: ?>
        <meta name="robots" content="index, follow, max-image-preview:large">
    <?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Formation CRA-W">
    <meta property="og:locale" content="fr_BE">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e(str_excerpt($description, 155)) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <meta name="theme-color" content="#198754" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#212529" media="(prefers-color-scheme: dark)">

    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">

    <?php foreach ($jsonLd as $block): ?>
        <?= $block ?>

    <?php endforeach; ?>

    <script>
        /* Applique le thème avant le premier rendu : évite le flash blanc. */
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
<body>
    <a class="skip-link" href="#contenu">Aller au contenu principal</a>

    <?= View::partial('partials/header', ['activeNav' => $activeNav]) ?>

    <main id="contenu">
        <?= View::partial('partials/flash') ?>
        <?= View::section('content') ?>
    </main>

    <?= View::partial('partials/footer') ?>

    <div id="live-announcer" class="visually-hidden" role="status" aria-live="polite"></div>

    <script src="<?= e(asset('js/site.js')) ?>" defer></script>
</body>
</html>

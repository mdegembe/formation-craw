<?php

declare(strict_types=1);

/**
 * Two-level header, mirroring cra.wallonie.be (English variant).
 *
 * @var string $activeNav
 */
$activeNav = $activeNav ?? '';

$navigation = [
    'accueil' => ['label' => 'The training', 'url' => '/'],
    'programme' => ['label' => 'Programme', 'url' => '/programme'],
    'infos' => ['label' => 'Practical information', 'url' => '/infos-pratiques'],
    'actualites' => ['label' => 'News', 'url' => '/actualites'],
    'contact' => ['label' => 'Contact', 'url' => '/contact'],
];
?>
<header class="site-header">
    <div class="utility-bar">
        <div class="container utility-bar__inner">
            <ul class="utility-bar__list">
                <li><a href="https://www.wallonie.be/" rel="noopener noreferrer" target="_blank">Wallonia</a></li>
                <li><a href="https://agriculture.wallonie.be/accueil" rel="noopener noreferrer" target="_blank">Agriculture</a></li>
                <li><a href="https://www.cra.wallonie.be/fr" rel="noopener noreferrer" target="_blank">CRA-W website</a></li>
            </ul>
            <ul class="utility-bar__list">
                <li><a href="https://www.cra.wallonie.be/fr/annuaire" rel="noopener noreferrer" target="_blank">Directory</a></li>
                <li><a href="https://www.cra.wallonie.be/fr/presse" rel="noopener noreferrer" target="_blank">Press</a></li>
                <li><a href="<?= e(url('/contact')) ?>">Contact us</a></li>
                <li><a href="<?= e(\Craw\Core\Locale::switchUrl('fr')) ?>" hreflang="fr" lang="fr">Français</a></li>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="navbar">
            <a class="brand" href="<?= e(url('/')) ?>">
                <span class="brand__mark" aria-hidden="true">CRA-W</span>
                <span class="brand__text">
                    <span class="brand__name">CRA-W Training</span>
                    <span class="brand__tagline">Walloon Agricultural Research Centre</span>
                </span>
            </a>

            <button type="button" class="nav-toggle" data-nav-toggle
                    aria-expanded="false" aria-controls="primary-navigation">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M1.5 3h13a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1Zm0 4.5h13a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1Zm0 4.5h13a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1Z"/>
                </svg>
                Menu
            </button>

            <nav id="primary-navigation" class="primary-nav" aria-label="Main navigation">
                <ul class="nav">
                    <?php foreach ($navigation as $key => $item): ?>
                        <li>
                            <a class="nav__link" href="<?= e(url($item['url'])) ?>"
                               <?= $activeNav === $key ? 'aria-current="page"' : '' ?>>
                                <?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="header-actions">
                <button type="button" class="theme-toggle" data-theme-toggle
                        aria-label="Change theme" aria-pressed="false">
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
                    </svg>
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6m0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
                    </svg>
                </button>

                <a class="btn btn--primary btn--pill btn--sm" href="<?= e(url('/inscription')) ?>">
                    Register
                </a>
            </div>
        </div>
    </div>
</header>

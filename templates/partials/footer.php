<?php

declare(strict_types=1);

/**
 * Pied de page : appel a l'action puis bande sombre a quatre colonnes,
 * comme sur cra.wallonie.be.
 */
?>
<footer class="site-footer">
    <div class="footer-cta">
        <div class="container">
            <h2>Une question sur la formation&nbsp;?</h2>
            <p class="lead mb-3">
                L'équipe organisatrice vous répond du lundi au vendredi.
            </p>
            <a class="btn btn--primary btn--pill btn--lg" href="<?= e(url('/contact')) ?>">
                Nous contacter
            </a>
        </div>
    </div>

    <svg class="wave wave--dark" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0 60V20c180 26 360 34 540 22S900 6 1080 8s270 12 360 18v34z"/>
    </svg>

    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <span class="brand__name">Formation CRA-W</span><br>
                    <span class="brand__tagline">Centre wallon de Recherches agronomiques</span>
                    <address class="mt-2 small" style="font-style: normal;">
                        <?= e((string) setting('payment.beneficiary')) ?><br>
                        Rue de Liroux 9<br>
                        5030 Gembloux — Belgique
                    </address>
                </div>

                <div>
                    <h2>La formation</h2>
                    <ul class="footer-list">
                        <li><a href="<?= e(url('/')) ?>">Présentation</a></li>
                        <li><a href="<?= e(url('/programme')) ?>">Programme</a></li>
                        <li><a href="<?= e(url('/infos-pratiques')) ?>">Infos pratiques</a></li>
                        <li><a href="<?= e(url('/inscription')) ?>">S'inscrire</a></li>
                    </ul>
                </div>

                <div>
                    <h2>Informations</h2>
                    <ul class="footer-list">
                        <li><a href="<?= e(url('/actualites')) ?>">Actualités</a></li>
                        <li><a href="<?= e(url('/actualites/rss')) ?>">Flux RSS</a></li>
                        <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                        <li>
                            <a href="https://www.cra.wallonie.be/fr" target="_blank" rel="noopener noreferrer">
                                Site du CRA-W
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2>Nous joindre</h2>
                    <ul class="footer-list">
                        <?php $contactEmail = (string) setting('contact.email'); ?>
                        <?php if ($contactEmail !== ''): ?>
                            <li><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></li>
                        <?php endif; ?>
                        <?php $contactPhone = (string) setting('contact.phone'); ?>
                        <?php if ($contactPhone !== ''): ?>
                            <li>
                                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contactPhone) ?? '') ?>">
                                    <?= e($contactPhone) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><a href="<?= e(url('/admin')) ?>">Espace administrateur</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-legal">
                <p class="mb-0">
                    CRA-W &copy; <?= date('Y') ?> — Tous droits réservés
                </p>
                <ul>
                    <li><a href="<?= e(url('/mentions-legales')) ?>">Mentions légales</a></li>
                    <li><a href="<?= e(url('/confidentialite')) ?>">Déclaration de confidentialité</a></li>
                    <li><a href="<?= e(url('/accessibilite')) ?>">Accessibilité</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

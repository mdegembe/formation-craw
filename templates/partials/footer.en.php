<?php

declare(strict_types=1);

/**
 * Footer: call to action then dark four-column band (English variant).
 */
?>
<footer class="site-footer">
    <div class="footer-cta">
        <div class="container">
            <h2>A question about the training?</h2>
            <p class="lead mb-3">
                The organising team answers Monday to Friday.
            </p>
            <a class="btn btn--primary btn--pill btn--lg" href="<?= e(url('/contact')) ?>">
                Contact us
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
                    <span class="brand__name">CRA-W Training</span><br>
                    <span class="brand__tagline">Walloon Agricultural Research Centre</span>
                    <address class="mt-2 small" style="font-style: normal;">
                        <?= e((string) setting('payment.beneficiary')) ?><br>
                        Rue de Liroux 9<br>
                        5030 Gembloux — Belgium
                    </address>
                </div>

                <div>
                    <h2>The training</h2>
                    <ul class="footer-list">
                        <li><a href="<?= e(url('/')) ?>">Overview</a></li>
                        <li><a href="<?= e(url('/programme')) ?>">Programme</a></li>
                        <li><a href="<?= e(url('/infos-pratiques')) ?>">Practical information</a></li>
                        <li><a href="<?= e(url('/inscription')) ?>">Register</a></li>
                    </ul>
                </div>

                <div>
                    <h2>Information</h2>
                    <ul class="footer-list">
                        <li><a href="<?= e(url('/actualites')) ?>">News</a></li>
                        <li><a href="<?= e(url('/actualites/rss')) ?>">RSS feed</a></li>
                        <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
                        <li>
                            <a href="https://www.cra.wallonie.be/fr" target="_blank" rel="noopener noreferrer">
                                CRA-W website
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h2>Get in touch</h2>
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
                        <li><a href="<?= e(url('/admin')) ?>">Administrator area</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-legal">
                <p class="mb-0">
                    CRA-W &copy; <?= date('Y') ?> — All rights reserved
                </p>
                <ul>
                    <li><a href="<?= e(url('/mentions-legales')) ?>">Legal notice</a></li>
                    <li><a href="<?= e(url('/confidentialite')) ?>">Privacy notice</a></li>
                    <li><a href="<?= e(url('/accessibilite')) ?>">Accessibility</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

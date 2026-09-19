<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $training
 * @var array<string, array<int, array<string, mixed>>> $days
 * @var array<int, array<string, mixed>> $news
 */
// Les donnees passees au controleur (title, description, jsonLd...) sont
// automatiquement transmises au gabarit parent par le moteur de vues.
View::extend('layouts/public');

$subjectPending = (bool) $training['subject_pending'] || ($training['subject'] ?? '') === '';
$price = (int) $training['price_cents'];
$filled = max(0, $capacity - $remainingSeats);
$fillPercent = $capacity > 0 ? min(100, (int) round($filled / $capacity * 100)) : 0;
$startDate = new DateTimeImmutable((string) $training['starts_on']);
?>

<section class="hero">
    <div class="container">
        <div class="hero__inner">
            <div>
                <p class="hero__badge">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                    </svg>
                    Du <?= e(fr_date((string) $training['starts_on'])) ?>
                    au <?= e(fr_date((string) $training['ends_on'])) ?>
                </p>

                <h1 class="hero__title">
                    <?php if ($subjectPending): ?>
                        <?= e((string) $training['title']) ?>
                    <?php else: ?>
                        <?= e((string) $training['subject']) ?>
                    <?php endif; ?>
                </h1>

                <p class="hero__lead"><?= e((string) ($training['summary'] ?? '')) ?></p>

                <?php if ($subjectPending): ?>
                    <div class="alert alert--info mt-3" role="status">
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                        </svg>
                        <div class="alert__body">
                            <strong>Sujet en cours de définition.</strong>
                            Le thème et le programme détaillé seront publiés ici dès leur validation.
                            Les inscriptions sont déjà ouvertes&nbsp;: les personnes inscrites seront
                            informées par courriel.
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="hero__meta">
                    <li>
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                        </svg>
                        <span>
                            <strong><?= e((string) $training['location_name']) ?></strong><br>
                            <span class="text-muted"><?= e((string) $training['location_address']) ?></span>
                        </span>
                    </li>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                        </svg>
                        <span>5 jours — 10 demi-journées, de 9h00 à 17h00</span>
                    </li>
                    <li>
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.797-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.816-1.828v3.495l-.216-.05zm1.259 1.988c1.276.325 1.898.882 1.898 1.79 0 1.06-.813 1.791-2.132 1.888V8.874z"/>
                        </svg>
                        <span>
                            <?php if ($price <= 0): ?>
                                <strong>Participation gratuite</strong>
                            <?php else: ?>
                                <strong><?= e(money($price, (string) $training['currency'])) ?></strong>
                                — repas et supports compris
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>

                <div class="btn-group">
                    <?php if ($isOpen && $remainingSeats > 0): ?>
                        <a class="btn btn--primary btn--pill btn--lg" href="<?= e(url('/inscription')) ?>">
                            S'inscrire à la formation
                        </a>
                    <?php elseif ($isOpen): ?>
                        <a class="btn btn--primary btn--pill btn--lg" href="<?= e(url('/inscription')) ?>">
                            Rejoindre la liste d'attente
                        </a>
                    <?php else: ?>
                        <span class="btn btn--ghost btn--pill btn--lg" aria-disabled="true">
                            Inscriptions closes
                        </span>
                    <?php endif; ?>
                    <a class="btn btn--outline btn--pill btn--lg" href="<?= e(url('/programme')) ?>">
                        Voir le programme
                    </a>
                </div>
            </div>

            <aside class="hero__card">
                <div class="date-badge" aria-hidden="true">
                    <span class="date-badge__day"><?= e($startDate->format('d')) ?></span>
                    <span class="date-badge__month">mars <?= e($startDate->format('Y')) ?></span>
                </div>

                <h2 class="text-center mt-3" style="font-size: 1.125rem;">Inscriptions</h2>

                <dl class="definition-list mt-3" style="grid-template-columns: 1fr auto;">
                    <dt>Places totales</dt>
                    <dd class="text-end fw-600"><?= e((string) $capacity) ?></dd>
                    <dt>Places restantes</dt>
                    <dd class="text-end fw-600"><?= e((string) $remainingSeats) ?></dd>
                    <?php if ($training['registration_deadline'] !== null): ?>
                        <dt>Clôture</dt>
                        <dd class="text-end fw-600"><?= e(fr_date((string) $training['registration_deadline'])) ?></dd>
                    <?php endif; ?>
                </dl>

                <div class="seats">
                    <div class="seats__bar">
                        <div class="seats__fill" style="width: <?= e((string) $fillPercent) ?>%;"></div>
                    </div>
                    <p class="small text-muted mt-1 mb-0">
                        <?= e((string) $filled) ?> inscription(s) sur <?= e((string) $capacity) ?> places
                        (<?= e((string) $fillPercent) ?>&nbsp;%).
                    </p>
                </div>

                <?php if ($remainingSeats <= 5 && $remainingSeats > 0): ?>
                    <p class="badge badge--warning mt-3">
                        Plus que <?= e((string) $remainingSeats) ?> place(s) disponible(s)
                    </p>
                <?php elseif ($remainingSeats === 0): ?>
                    <p class="badge badge--danger mt-3">Complet — liste d'attente ouverte</p>
                <?php endif; ?>

                <p class="small text-muted mt-3 mb-0">
                    Inscription sans création de compte. Paiement par virement bancaire.
                </p>
            </aside>
        </div>
    </div>
</section>

<svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path d="M0 0v28c180 22 360 30 540 20S900 18 1080 20s270 10 360 14V0z"/>
</svg>

<section class="section section--alt">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">La formation</p>
            <h2>Une semaine au cœur de la recherche agronomique wallonne</h2>
        </div>
        <div class="prose"><?= $descriptionHtml ?></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">Déroulé</p>
            <h2>Cinq jours, dix demi-journées</h2>
            <p class="lead">
                Chaque journée se compose d'une session le matin et d'une session l'après-midi.
                La présence est relevée à chaque demi-journée.
            </p>
        </div>

        <div class="grid grid--3">
            <?php foreach ($days as $date => $slots): ?>
                <?php $day = new DateTimeImmutable((string) $date); ?>
                <article class="card card--flat">
                    <div class="card__body">
                        <p class="card__meta">
                            <span><?= e(fr_date($day, true)) ?></span>
                        </p>
                        <h3 class="card__title">
                            <?= e(ucfirst((string) strtok(fr_date($day, true), ' '))) ?>
                            <?= e($day->format('d')) ?>
                        </h3>
                        <ul class="footer-list small">
                            <?php foreach ($slots as $slot): ?>
                                <li>
                                    <strong><?= e((string) $slot['starts_at']) ?>–<?= e((string) $slot['ends_at']) ?></strong>
                                    — <?= e((string) ($slot['topic'] ?? 'À définir')) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="mt-4">
            <a class="btn btn--outline btn--pill" href="<?= e(url('/programme')) ?>">
                Consulter le programme détaillé
            </a>
        </p>
    </div>
</section>

<?php if ($news !== []): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section__header">
                <p class="section__eyebrow">Actualités</p>
                <h2>Les dernières informations</h2>
            </div>

            <div class="grid grid--3">
                <?php foreach ($news as $article): ?>
                    <?= View::partial('partials/news_card', ['article' => $article]) ?>
                <?php endforeach; ?>
            </div>

            <p class="mt-4">
                <a class="btn btn--outline btn--pill" href="<?= e(url('/actualites')) ?>">
                    Toutes les actualités
                </a>
            </p>
        </div>
    </section>
<?php endif; ?>

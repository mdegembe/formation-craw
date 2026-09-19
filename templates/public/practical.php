<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $training
 * @var string $practicalHtml
 * @var array<int, array{question: string, answer: string}> $faq
 */
View::extend('layouts/public');

$price = (int) $training['price_cents'];
?>

<section class="section">
    <div class="container">
        <nav aria-label="Fil d'Ariane" class="small text-muted mb-3">
            <a href="<?= e(url('/')) ?>">Accueil</a> › <span aria-current="page">Infos pratiques</span>
        </nav>

        <div class="section__header">
            <p class="section__eyebrow">Infos pratiques</p>
            <h1>Tout ce qu'il faut savoir avant de venir</h1>
        </div>

        <div class="grid grid--4 mb-3">
            <div class="stat stat--accent">
                <span class="stat__label">Dates</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e((new DateTimeImmutable((string) $training['starts_on']))->format('d')) ?>–<?= e((new DateTimeImmutable((string) $training['ends_on']))->format('d M Y')) ?>
                </span>
                <span class="stat__hint">Du lundi au vendredi</span>
            </div>
            <div class="stat">
                <span class="stat__label">Horaires</span>
                <span class="stat__value" style="font-size: 1.25rem;">9h–17h</span>
                <span class="stat__hint">Accueil dès 8h30 le lundi</span>
            </div>
            <div class="stat">
                <span class="stat__label">Tarif</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= $price <= 0 ? 'Gratuit' : e(money($price, (string) $training['currency'])) ?>
                </span>
                <span class="stat__hint">Repas et supports compris</span>
            </div>
            <div class="stat">
                <span class="stat__label">Lieu</span>
                <span class="stat__value" style="font-size: 1.25rem;">Gembloux</span>
                <span class="stat__hint"><?= e((string) $training['location_name']) ?></span>
            </div>
        </div>

        <div class="prose"><?= $practicalHtml ?></div>
    </div>
</section>

<svg class="wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path d="M0 0v28c180 22 360 30 540 20S900 18 1080 20s270 10 360 14V0z"/>
</svg>

<section class="section section--alt">
    <div class="container container--narrow">
        <div class="section__header">
            <p class="section__eyebrow">Questions fréquentes</p>
            <h2>Vos questions, nos réponses</h2>
        </div>

        <div class="stack">
            <?php foreach ($faq as $index => $entry): ?>
                <details class="panel"<?= $index === 0 ? ' open' : '' ?>>
                    <summary style="cursor: pointer; font-weight: 600;">
                        <?= e($entry['question']) ?>
                    </summary>
                    <p class="mt-2 mb-0 text-muted"><?= e($entry['answer']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>

        <div class="btn-group mt-4">
            <a class="btn btn--primary btn--pill" href="<?= e(url('/inscription')) ?>">S'inscrire</a>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Poser une question</a>
        </div>
    </div>
</section>

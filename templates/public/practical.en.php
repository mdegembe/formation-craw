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
        <nav aria-label="Breadcrumb" class="small text-muted mb-3">
            <a href="<?= e(url('/')) ?>">Home</a> › <span aria-current="page">Practical information</span>
        </nav>

        <div class="section__header">
            <p class="section__eyebrow">Practical information</p>
            <h1>Everything you need to know before you come</h1>
        </div>

        <div class="grid grid--4 mb-3">
            <div class="stat stat--accent">
                <span class="stat__label">Dates</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e((new DateTimeImmutable((string) $training['starts_on']))->format('d')) ?>–<?= e((new DateTimeImmutable((string) $training['ends_on']))->format('d M Y')) ?>
                </span>
                <span class="stat__hint">Monday to Friday</span>
            </div>
            <div class="stat">
                <span class="stat__label">Hours</span>
                <span class="stat__value" style="font-size: 1.25rem;">9am–5pm</span>
                <span class="stat__hint">Check-in from 8:30am on Monday</span>
            </div>
            <div class="stat">
                <span class="stat__label">Price</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= $price <= 0 ? 'Free' : e(money_en($price, (string) $training['currency'])) ?>
                </span>
                <span class="stat__hint">Meals and materials included</span>
            </div>
            <div class="stat">
                <span class="stat__label">Venue</span>
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
            <p class="section__eyebrow">Frequently asked questions</p>
            <h2>Your questions, our answers</h2>
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
            <a class="btn btn--primary btn--pill" href="<?= e(url('/inscription')) ?>">Register</a>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Ask a question</a>
        </div>
    </div>
</section>

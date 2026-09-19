<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $article
 * @var string $bodyHtml
 * @var array<int, array<string, mixed>> $related
 */
View::extend('layouts/public');

$published = (string) $article['published_at'];
?>

<article class="section">
    <div class="container container--narrow">
        <nav aria-label="Fil d'Ariane" class="small text-muted mb-3">
            <a href="<?= e(url('/')) ?>">Accueil</a> ›
            <a href="<?= e(url('/actualites')) ?>">Actualités</a> ›
            <span aria-current="page"><?= e(str_excerpt((string) $article['title'], 50)) ?></span>
        </nav>

        <header class="mb-3">
            <p class="card__meta">
                <time datetime="<?= e(date('Y-m-d', strtotime($published))) ?>">
                    Publié le <?= e(fr_date($published)) ?>
                </time>
                <?php if (!empty($article['author_name'])): ?>
                    <span>par <?= e((string) $article['author_name']) ?></span>
                <?php endif; ?>
            </p>
            <h1><?= e((string) $article['title']) ?></h1>
            <?php if (!empty($article['excerpt'])): ?>
                <p class="lead"><?= e((string) $article['excerpt']) ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($article['cover_path'])): ?>
            <img src="<?= e(url('/media/news/' . (string) $article['cover_path'])) ?>"
                 alt="<?= e((string) ($article['cover_alt'] ?? '')) ?>"
                 width="1200" height="675"
                 style="border-radius: var(--craw-radius-lg); margin-bottom: 2rem;">
        <?php endif; ?>

        <div class="prose"><?= $bodyHtml ?></div>

        <div class="btn-group mt-4">
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/actualites')) ?>">
                ← Toutes les actualités
            </a>
            <a class="btn btn--primary btn--pill" href="<?= e(url('/inscription')) ?>">
                S'inscrire à la formation
            </a>
        </div>
    </div>
</article>

<?php if ($related !== []): ?>
    <section class="section section--alt section--tight">
        <div class="container">
            <h2>À lire également</h2>
            <div class="grid grid--3 mt-3">
                <?php foreach ($related as $item): ?>
                    <?= View::partial('partials/news_card', ['article' => $item]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

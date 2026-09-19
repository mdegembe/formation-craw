<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $article
 */
$link = url('/actualites/' . (string) $article['slug']);
$published = (string) ($article['published_at'] ?? $article['created_at']);
?>
<article class="card">
    <div class="card__media">
        <?php if (!empty($article['cover_path'])): ?>
            <img src="<?= e(url('/media/news/' . (string) $article['cover_path'])) ?>"
                 alt="<?= e((string) ($article['cover_alt'] ?? '')) ?>"
                 width="800" height="450" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="card__media-placeholder" aria-hidden="true">
                <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5M5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1z"/>
                    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <p class="card__meta">
            <time datetime="<?= e(date('Y-m-d', strtotime($published))) ?>">
                <?= e(fr_date($published)) ?>
            </time>
        </p>
        <h3 class="card__title">
            <a href="<?= e($link) ?>"><?= e((string) $article['title']) ?></a>
        </h3>
        <p class="text-muted small"><?= e(str_excerpt((string) ($article['excerpt'] ?? ''), 150)) ?></p>
        <div class="card__footer">
            <a class="btn btn--ghost btn--sm" href="<?= e($link) ?>">
                Lire l'article
                <span class="visually-hidden"> : <?= e((string) $article['title']) ?></span>
            </a>
        </div>
    </div>
</article>

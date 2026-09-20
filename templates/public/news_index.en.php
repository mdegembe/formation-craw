<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<int, array<string, mixed>> $articles
 * @var int $page
 * @var int $pages
 */
View::extend('layouts/public');
?>

<section class="section">
    <div class="container">
        <nav aria-label="Breadcrumb" class="small text-muted mb-3">
            <a href="<?= e(url('/')) ?>">Home</a> › <span aria-current="page">News</span>
        </nav>

        <div class="section__header">
            <p class="section__eyebrow">News</p>
            <h1>Updates about the training</h1>
            <p class="lead">
                Registration opening, programme, practical organisation: find all the
                announcements about the March 2027 session here.
                <a href="<?= e(url('/actualites/rss')) ?>">RSS feed</a>.
            </p>
        </div>

        <?php if ($articles === []): ?>
            <p class="empty-state">No news has been published yet.</p>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach ($articles as $article): ?>
                    <?= View::partial('partials/news_card', ['article' => $article]) ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav aria-label="News pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="<?= e(url('/actualites' . ($page - 1 > 1 ? '?page=' . ($page - 1) : ''))) ?>"
                                   rel="prev">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li>
                                <?php if ($i === $page): ?>
                                    <span aria-current="page">
                                        <span class="visually-hidden">Page </span><?= e((string) $i) ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= e(url('/actualites' . ($i > 1 ? '?page=' . $i : ''))) ?>">
                                        <span class="visually-hidden">Page </span><?= e((string) $i) ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $pages): ?>
                            <li><a href="<?= e(url('/actualites?page=' . ($page + 1))) ?>" rel="next">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

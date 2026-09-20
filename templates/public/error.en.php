<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var int    $status
 * @var string $message
 * @var bool   $isAdmin
 */
View::extend('layouts/public');
?>

<section class="section">
    <div class="container container--narrow text-center">
        <p class="section__eyebrow">Error <?= e((string) $status) ?></p>
        <h1><?= e((string) $title) ?></h1>
        <p class="lead"><?= e($message) ?></p>

        <div class="btn-group mt-4" style="justify-content: center;">
            <?php if ($isAdmin): ?>
                <a class="btn btn--primary btn--pill" href="<?= e(url('/admin')) ?>">
                    Back to the dashboard
                </a>
            <?php else: ?>
                <a class="btn btn--primary btn--pill" href="<?= e(url('/')) ?>">
                    Back to the homepage
                </a>
            <?php endif; ?>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Report this problem</a>
        </div>

        <?php if ($status === 404): ?>
            <p class="small text-muted mt-4">
                The page you were looking for may have moved. See the
                <a href="<?= e(url('/programme')) ?>">programme</a>,
                the <a href="<?= e(url('/infos-pratiques')) ?>">practical information</a>
                or the <a href="<?= e(url('/actualites')) ?>">news</a>.
            </p>
        <?php endif; ?>
    </div>
</section>

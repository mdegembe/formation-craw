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
        <p class="section__eyebrow">Erreur <?= e((string) $status) ?></p>
        <h1><?= e((string) $title) ?></h1>
        <p class="lead"><?= e($message) ?></p>

        <div class="btn-group mt-4" style="justify-content: center;">
            <?php if ($isAdmin): ?>
                <a class="btn btn--primary btn--pill" href="<?= e(url('/admin')) ?>">
                    Retour au tableau de bord
                </a>
            <?php else: ?>
                <a class="btn btn--primary btn--pill" href="<?= e(url('/')) ?>">
                    Retour à l'accueil
                </a>
            <?php endif; ?>
            <a class="btn btn--ghost btn--pill" href="<?= e(url('/contact')) ?>">Nous signaler le problème</a>
        </div>

        <?php if ($status === 404): ?>
            <p class="small text-muted mt-4">
                La page recherchée a peut-être été déplacée. Consultez le
                <a href="<?= e(url('/programme')) ?>">programme</a>,
                les <a href="<?= e(url('/infos-pratiques')) ?>">infos pratiques</a>
                ou les <a href="<?= e(url('/actualites')) ?>">actualités</a>.
            </p>
        <?php endif; ?>
    </div>
</section>

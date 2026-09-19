<?php

declare(strict_types=1);

use Craw\Core\Auth;
use Craw\Core\Csrf;
use Craw\Core\View;

/** @var array<int, array<string, mixed>> $articles */
View::extend('layouts/admin');
?>

<div class="admin-header">
    <div>
        <h1>Actualités</h1>
        <p class="text-muted mb-0">
            Les articles publiés apparaissent sur le site, dans le flux RSS et dans le sitemap.
        </p>
    </div>
    <a class="btn btn--primary btn--sm" href="<?= e(url('/admin/actualites/nouvelle')) ?>">
        Rédiger une actualité
    </a>
</div>

<?php if ($articles === []): ?>
    <p class="empty-state">
        Aucune actualité pour l'instant.
        <a href="<?= e(url('/admin/actualites/nouvelle')) ?>">Rédigez la première</a>.
    </p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <caption class="visually-hidden">Liste des actualités</caption>
            <thead>
                <tr>
                    <th scope="col">Titre</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Publication</th>
                    <th scope="col">Auteur</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/admin/actualites/' . (int) $article['id'])) ?>">
                                <strong><?= e((string) $article['title']) ?></strong>
                            </a><br>
                            <span class="small text-muted">/<?= e((string) $article['slug']) ?></span>
                        </td>
                        <td>
                            <?php if ((string) $article['status'] === 'published'): ?>
                                <span class="badge badge--success">Publiée</span>
                            <?php else: ?>
                                <span class="badge badge--neutral">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= $article['published_at'] === null
                                ? '—'
                                : e(fr_datetime((string) $article['published_at'])) ?>
                        </td>
                        <td class="small text-muted"><?= e((string) ($article['author_name'] ?? '—')) ?></td>
                        <td>
                            <div class="btn-group">
                                <a class="btn btn--ghost btn--sm"
                                   href="<?= e(url('/admin/actualites/' . (int) $article['id'])) ?>">Modifier</a>

                                <?php if ((string) $article['status'] === 'published'): ?>
                                    <a class="btn btn--ghost btn--sm"
                                       href="<?= e(url('/actualites/' . (string) $article['slug'])) ?>"
                                       target="_blank" rel="noopener">Voir</a>
                                <?php endif; ?>

                                <?php if (Auth::isAdmin()): ?>
                                    <form method="post"
                                          action="<?= e(url('/admin/actualites/' . (int) $article['id'] . '/supprimer')) ?>"
                                          class="inline-form"
                                          data-confirm="Supprimer définitivement cette actualité ?">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--danger btn--sm">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * @var array<string, mixed>|null $article
 * @var array<string, string>     $errors
 * @var array<string, mixed>      $old
 */
View::extend('layouts/admin');

$isEdit = $article !== null;
$action = $isEdit
    ? url('/admin/actualites/' . (int) $article['id'])
    : url('/admin/actualites');

$value = static function (string $key, string $default = '') use ($old, $article): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }

    return (string) ($article[$key] ?? $default);
};
?>

<div class="admin-header">
    <div>
        <p class="small text-muted mb-0">
            <a href="<?= e(url('/admin/actualites')) ?>">← Actualités</a>
        </p>
        <h1><?= $isEdit ? 'Modifier l\'actualité' : 'Nouvelle actualité' ?></h1>
    </div>
    <?php if ($isEdit && (string) $article['status'] === 'published'): ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/actualites/' . (string) $article['slug'])) ?>"
           target="_blank" rel="noopener">Voir sur le site</a>
    <?php endif; ?>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-once novalidate>
    <?= Csrf::field() ?>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Contenu</h2>

        <div class="form-grid mt-3">
            <div class="form-field">
                <label class="form-label" for="title">
                    Titre <span class="required" aria-hidden="true">*</span>
                </label>
                <input class="form-control" type="text" id="title" name="title" maxlength="190" required
                       value="<?= e($value('title')) ?>"
                    <?= isset($errors['title']) ? 'aria-invalid="true" aria-describedby="title-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'title']) ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="slug">Adresse de la page (slug)</label>
                <input class="form-control" type="text" id="slug" name="slug" maxlength="190"
                       value="<?= e($value('slug')) ?>" placeholder="Généré automatiquement depuis le titre">
                <p class="form-hint">
                    Laissez vide pour le déduire du titre. Modifier le slug d'un article déjà publié
                    casse les liens existants.
                </p>
            </div>

            <div class="form-field">
                <label class="form-label" for="excerpt">Chapô</label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="2" maxlength="500"
                ><?= e($value('excerpt')) ?></textarea>
                <p class="form-hint">
                    Résumé affiché dans la liste des actualités et dans les partages.
                    Généré automatiquement s'il est laissé vide.
                </p>
            </div>

            <div class="form-field">
                <label class="form-label" for="body">
                    Corps de l'article <span class="required" aria-hidden="true">*</span>
                </label>
                <textarea class="form-control" id="body" name="body" rows="16" required
                          style="font-family: var(--craw-font-mono); font-size: 0.9375rem;"
                    <?= isset($errors['body']) ? 'aria-invalid="true" aria-describedby="body-error"' : '' ?>
                ><?= e($value('body')) ?></textarea>
                <p class="form-hint">
                    Mise en forme Markdown&nbsp;: <code>## Titre</code>, <code>**gras**</code>,
                    <code>*italique*</code>, <code>- liste</code>, <code>1. liste numérotée</code>,
                    <code>&gt; citation</code>, <code>[lien](https://…)</code>.
                    Aucun code HTML n'est interprété.
                </p>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'body']) ?>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Contenu (anglais)</h2>
        <p class="form-hint mb-3">
            Facultatif. Laissé vide, l'article reste affiché en français sur la version anglaise
            du site.
        </p>

        <div class="form-grid mt-3">
            <div class="form-field">
                <label class="form-label" for="title_en">Title</label>
                <input class="form-control" type="text" id="title_en" name="title_en" maxlength="190"
                       value="<?= e($value('title_en')) ?>"
                    <?= isset($errors['title_en']) ? 'aria-invalid="true" aria-describedby="title_en-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'title_en']) ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="excerpt_en">Excerpt</label>
                <textarea class="form-control" id="excerpt_en" name="excerpt_en" rows="2" maxlength="500"
                ><?= e($value('excerpt_en')) ?></textarea>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'excerpt_en']) ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="body_en">Article body</label>
                <textarea class="form-control" id="body_en" name="body_en" rows="16"
                          style="font-family: var(--craw-font-mono); font-size: 0.9375rem;"
                ><?= e($value('body_en')) ?></textarea>
                <p class="form-hint">Same Markdown formatting as the French text above.</p>
            </div>

            <div class="form-field">
                <label class="form-label" for="meta_description_en">SEO description</label>
                <textarea class="form-control" id="meta_description_en" name="meta_description_en"
                          rows="2" maxlength="255"><?= e($value('meta_description_en')) ?></textarea>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'meta_description_en']) ?>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Image de couverture</h2>

        <?php if ($isEdit && !empty($article['cover_path'])): ?>
            <img src="<?= e(url('/media/news/' . (string) $article['cover_path'])) ?>" alt=""
                 style="max-width: 20rem; border-radius: var(--craw-radius); margin: 1rem 0;">
            <div class="form-check">
                <input type="checkbox" id="remove_cover" name="remove_cover" value="1">
                <label for="remove_cover">Retirer l'image actuelle</label>
            </div>
        <?php endif; ?>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field">
                <label class="form-label" for="cover">Fichier image</label>
                <input class="form-control" type="file" id="cover" name="cover"
                       accept="image/jpeg,image/png,image/webp">
                <p class="form-hint">JPEG, PNG ou WebP, 8 Mo maximum. Redimensionnée automatiquement.</p>
            </div>

            <div class="form-field">
                <label class="form-label" for="cover_alt">Texte alternatif</label>
                <input class="form-control" type="text" id="cover_alt" name="cover_alt" maxlength="190"
                       value="<?= e($value('cover_alt')) ?>">
                <p class="form-hint">
                    Décrit l'image pour les personnes utilisant un lecteur d'écran.
                    Laissez vide si l'image est purement décorative.
                </p>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Publication et référencement</h2>

        <div class="form-grid form-grid--2 mt-3">
            <div class="form-field">
                <label class="form-label" for="status">Statut</label>
                <select class="form-select" id="status" name="status">
                    <option value="draft" <?= $value('status', 'draft') === 'draft' ? 'selected' : '' ?>>
                        Brouillon
                    </option>
                    <option value="published" <?= $value('status') === 'published' ? 'selected' : '' ?>>
                        Publiée
                    </option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label" for="published_at">Date de publication</label>
                <input class="form-control" type="datetime-local" id="published_at" name="published_at"
                       value="<?= e($article !== null && $article['published_at'] !== null
                           ? date('Y-m-d\TH:i', strtotime((string) $article['published_at']))
                           : '') ?>">
                <p class="form-hint">
                    Une date future planifie la publication&nbsp;: l'article n'apparaît qu'à échéance.
                </p>
            </div>

            <div class="form-field">
                <label class="form-label" for="meta_title">Titre SEO</label>
                <input class="form-control" type="text" id="meta_title" name="meta_title" maxlength="190"
                       value="<?= e($value('meta_title')) ?>">
                <p class="form-hint">60 caractères recommandés. Reprend le titre s'il est vide.</p>
            </div>

            <div class="form-field">
                <label class="form-label" for="meta_description">Description SEO</label>
                <textarea class="form-control" id="meta_description" name="meta_description"
                          rows="2" maxlength="255"><?= e($value('meta_description')) ?></textarea>
                <p class="form-hint">155 caractères recommandés. Reprend le chapô s'il est vide.</p>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--pill">
            <?= $isEdit ? 'Enregistrer' : 'Créer l\'actualité' ?>
        </button>
        <a class="btn btn--ghost btn--pill" href="<?= e(url('/admin/actualites')) ?>">Annuler</a>
    </div>
</form>

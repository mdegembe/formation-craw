<?php

declare(strict_types=1);

use Craw\Core\Auth;
use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * Depot et configuration du modele de diplome fourni par l'administrateur.
 *
 * @var array<int, array<string, mixed>> $templates
 * @var array<string, mixed>|null        $active
 * @var array<string, string>            $placeholders
 */
View::extend('layouts/admin');

/** @var array<int, array<string, mixed>> $layout */
$layout = $active !== null && $active['field_layout'] !== null
    ? (json_decode((string) $active['field_layout'], true) ?: [])
    : [];

$layoutByKey = [];
foreach ($layout as $field) {
    $layoutByKey[(string) ($field['key'] ?? '')] = $field;
}

// Disposition proposee par defaut : lisible d'emblee sur un fond A4 paysage.
$defaults = [
    'participant.nom_complet' => ['top' => 88, 'left' => 30, 'width' => 237, 'size' => 30, 'align' => 'center', 'bold' => true],
    'formation.sujet' => ['top' => 120, 'left' => 40, 'width' => 217, 'size' => 15, 'align' => 'center', 'bold' => true],
    'formation.dates' => ['top' => 136, 'left' => 40, 'width' => 217, 'size' => 12, 'align' => 'center', 'bold' => false],
    'presence.taux' => ['top' => 150, 'left' => 40, 'width' => 217, 'size' => 11, 'align' => 'center', 'bold' => false],
    'diplome.numero' => ['top' => 182, 'left' => 180, 'width' => 90, 'size' => 9, 'align' => 'right', 'bold' => false],
    'diplome.date_emission' => ['top' => 182, 'left' => 26, 'width' => 90, 'size' => 9, 'align' => 'left', 'bold' => false],
    'qr_verification' => ['top' => 160, 'left' => 134, 'width' => 30, 'size' => 9, 'align' => 'center', 'bold' => false],
];

$mode = $active === null ? 'background' : (string) $active['mode'];
?>

<div class="admin-header">
    <div>
        <p class="small text-muted mb-0">
            <a href="<?= e(url('/admin/diplomes')) ?>">← Diplômes</a>
        </p>
        <h1>Modèle de diplôme</h1>
        <p class="text-muted mb-0">
            Fournissez votre propre visuel et positionnez les informations à imprimer dessus.
        </p>
    </div>
    <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/diplomes/modele/apercu.pdf')) ?>" target="_blank">
        Aperçu PDF du modèle actif
    </a>
</div>

<div class="panel panel--accent mb-3">
    <h2 style="font-size: 1.0625rem;">Deux façons de procéder</h2>
    <ol class="mt-2" style="padding-left: 1.25rem;">
        <li>
            <strong>Fond + calque (recommandé).</strong> Vous déposez une image A4 paysage
            (JPEG ou PNG, idéalement 3508 × 2480 px pour une impression à 300 dpi) et vous indiquez
            où placer chaque information. Aucune compétence technique requise.
        </li>
        <li>
            <strong>Gabarit HTML (avancé).</strong> Vous rédigez le diplôme en HTML/CSS et insérez
            les variables ci-dessous. Utile si vous partez d'un gabarit existant.
        </li>
    </ol>
    <p class="small text-muted mb-0">
        Dans les deux cas, prévisualisez avant de générer les diplômes&nbsp;: l'aperçu utilise un
        participant fictif et reflète exactement le rendu final.
    </p>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Variables disponibles</h2>
    <div class="table-wrap mt-3">
        <table class="table table--compact">
            <caption class="visually-hidden">Variables utilisables dans un gabarit de diplôme</caption>
            <thead>
                <tr>
                    <th scope="col">Variable</th>
                    <th scope="col">Contenu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($placeholders as $key => $description): ?>
                    <tr>
                        <td><code>{{<?= e($key) ?>}}</code></td>
                        <td class="text-muted"><?= e($description) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/diplomes/modele')) ?>" enctype="multipart/form-data" data-once>
    <?= Csrf::field() ?>
    <?php if ($active !== null): ?>
        <input type="hidden" name="template_id" value="<?= e((string) $active['id']) ?>">
    <?php endif; ?>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Modèle</h2>

        <div class="form-grid form-grid--3 mt-3">
            <div class="form-field">
                <label class="form-label" for="name">Nom du modèle</label>
                <input class="form-control" type="text" id="name" name="name" maxlength="150" required
                       value="<?= e((string) ($active['name'] ?? 'Modèle officiel CRA-W')) ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="mode">Mode</label>
                <select class="form-select" id="mode" name="mode">
                    <option value="background" <?= $mode === 'background' ? 'selected' : '' ?>>
                        Fond + calque (recommandé)
                    </option>
                    <option value="html" <?= $mode === 'html' ? 'selected' : '' ?>>
                        Gabarit HTML (avancé)
                    </option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label" for="orientation">Orientation</label>
                <select class="form-select" id="orientation" name="orientation">
                    <option value="landscape"
                        <?= (string) ($active['orientation'] ?? 'landscape') === 'landscape' ? 'selected' : '' ?>>
                        Paysage (A4)
                    </option>
                    <option value="portrait"
                        <?= (string) ($active['orientation'] ?? '') === 'portrait' ? 'selected' : '' ?>>
                        Portrait (A4)
                    </option>
                </select>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Image de fond</h2>
        <p class="text-muted small">
            JPEG, PNG ou WebP, 8 Mo maximum. L'image est ré-encodée à la réception, ce qui garantit
            qu'aucun contenu exécutable n'y subsiste.
        </p>

        <div class="form-field mt-2">
            <label class="form-label" for="background">Déposer un visuel</label>
            <input class="form-control" type="file" id="background" name="background"
                   accept="image/jpeg,image/png,image/webp">
            <?php if ($active !== null && !empty($active['background_path'])): ?>
                <p class="form-hint">
                    Un fond est déjà en place. Laissez ce champ vide pour le conserver.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Position des informations</h2>
        <p class="text-muted small">
            Les positions sont exprimées en millimètres depuis le coin supérieur gauche de la page
            (A4 paysage&nbsp;: 297 × 210&nbsp;mm). Décochez une ligne pour ne pas l'imprimer.
        </p>

        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Positionnement des champs sur le diplôme</caption>
                <thead>
                    <tr>
                        <th scope="col">Afficher</th>
                        <th scope="col">Information</th>
                        <th scope="col">Haut (mm)</th>
                        <th scope="col">Gauche (mm)</th>
                        <th scope="col">Largeur (mm)</th>
                        <th scope="col">Taille (pt)</th>
                        <th scope="col">Alignement</th>
                        <th scope="col">Gras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($placeholders as $key => $description): ?>
                        <?php
                        $field = str_replace('.', '_', $key);
                        $current = $layoutByKey[$key] ?? ($defaults[$key] ?? null);
                        $enabled = isset($layoutByKey[$key]) || ($layout === [] && isset($defaults[$key]));
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" id="field_<?= e($field) ?>_enabled"
                                       name="field_<?= e($field) ?>_enabled" value="1"
                                    <?= $enabled ? 'checked' : '' ?>>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_enabled">
                                    Afficher <?= e($description) ?>
                                </label>
                            </td>
                            <td>
                                <strong><?= e($description) ?></strong><br>
                                <code class="small"><?= e($key) ?></code>
                            </td>
                            <td>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_top">Position haute</label>
                                <input class="form-control" style="width: 5.5rem;" type="number" step="0.5" min="0" max="300"
                                       id="field_<?= e($field) ?>_top" name="field_<?= e($field) ?>_top"
                                       value="<?= e((string) ($current['top'] ?? 20)) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_left">Position gauche</label>
                                <input class="form-control" style="width: 5.5rem;" type="number" step="0.5" min="0" max="300"
                                       id="field_<?= e($field) ?>_left" name="field_<?= e($field) ?>_left"
                                       value="<?= e((string) ($current['left'] ?? 20)) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_width">Largeur</label>
                                <input class="form-control" style="width: 5.5rem;" type="number" step="0.5" min="5" max="300"
                                       id="field_<?= e($field) ?>_width" name="field_<?= e($field) ?>_width"
                                       value="<?= e((string) ($current['width'] ?? 100)) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_size">Taille de police</label>
                                <input class="form-control" style="width: 4.5rem;" type="number" step="0.5" min="6" max="80"
                                       id="field_<?= e($field) ?>_size" name="field_<?= e($field) ?>_size"
                                       value="<?= e((string) ($current['size'] ?? 12)) ?>">
                            </td>
                            <td>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_align">Alignement</label>
                                <select class="form-select" style="width: 6.5rem;"
                                        id="field_<?= e($field) ?>_align" name="field_<?= e($field) ?>_align">
                                    <?php foreach (['left' => 'Gauche', 'center' => 'Centré', 'right' => 'Droite'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>"
                                            <?= (string) ($current['align'] ?? 'left') === $value ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="checkbox" id="field_<?= e($field) ?>_bold"
                                       name="field_<?= e($field) ?>_bold" value="1"
                                    <?= !empty($current['bold']) ? 'checked' : '' ?>>
                                <label class="visually-hidden" for="field_<?= e($field) ?>_bold">En gras</label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Gabarit HTML (mode avancé)</h2>
        <p class="text-muted small">
            Utilisé uniquement si le mode « Gabarit HTML » est sélectionné. Les balises
            <code>&lt;script&gt;</code>, <code>&lt;iframe&gt;</code> et les appels distants sont
            retirés à l'enregistrement.
        </p>

        <div class="form-field mt-2">
            <label class="form-label" for="html_body">Code HTML du diplôme</label>
            <textarea class="form-control" id="html_body" name="html_body" rows="12"
                      style="font-family: var(--craw-font-mono); font-size: 0.875rem;"
            ><?= e((string) ($active['html_body'] ?? '')) ?></textarea>
            <p class="form-hint">
                Exemple&nbsp;:
                <code>&lt;div style="text-align:center;margin-top:60mm"&gt;&lt;h1&gt;{{participant.nom_complet}}&lt;/h1&gt;&lt;p&gt;{{formation.sujet}} — {{formation.dates}}&lt;/p&gt;&lt;/div&gt;</code>
            </p>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--pill">Enregistrer et activer</button>
        <a class="btn btn--ghost btn--pill" href="<?= e(url('/admin/diplomes/modele/apercu.pdf')) ?>" target="_blank">
            Prévisualiser le modèle actif
        </a>
    </div>
</form>

<?php if ($templates !== []): ?>
    <div class="panel mt-4">
        <h2 style="font-size: 1.125rem;">Modèles enregistrés</h2>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Modèles de diplôme enregistrés</caption>
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Mode</th>
                        <th scope="col">Créé le</th>
                        <th scope="col">État</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $item): ?>
                        <tr>
                            <td><strong><?= e((string) $item['name']) ?></strong></td>
                            <td class="small">
                                <?= (string) $item['mode'] === 'html' ? 'Gabarit HTML' : 'Fond + calque' ?>
                            </td>
                            <td class="small text-muted"><?= e(fr_date((string) $item['created_at'])) ?></td>
                            <td>
                                <?php if ((int) $item['is_active'] === 1): ?>
                                    <span class="badge badge--success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge--neutral">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a class="btn btn--ghost btn--sm"
                                       href="<?= e(url('/admin/diplomes/modele/apercu.pdf?modele=' . (int) $item['id'])) ?>"
                                       target="_blank">Aperçu</a>

                                    <?php if ((int) $item['is_active'] !== 1): ?>
                                        <form method="post"
                                              action="<?= e(url('/admin/diplomes/modele/' . (int) $item['id'] . '/activer')) ?>"
                                              class="inline-form">
                                            <?= Csrf::field() ?>
                                            <button type="submit" class="btn btn--outline btn--sm">Activer</button>
                                        </form>

                                        <?php if (Auth::isAdmin()): ?>
                                            <form method="post"
                                                  action="<?= e(url('/admin/diplomes/modele/' . (int) $item['id'] . '/supprimer')) ?>"
                                                  class="inline-form"
                                                  data-confirm="Supprimer définitivement ce modèle ?">
                                                <?= Csrf::field() ?>
                                                <button type="submit" class="btn btn--danger btn--sm">Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

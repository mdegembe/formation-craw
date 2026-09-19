<?php

declare(strict_types=1);

use Craw\Core\Auth;
use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * @var array<int, array<string, mixed>> $users
 * @var array<int, array<string, mixed>> $audit
 * @var array<string, string>            $errors
 * @var array<string, mixed>             $old
 */
View::extend('layouts/admin');

$actionLabels = [
    'auth.login' => 'Connexion',
    'auth.logout' => 'Déconnexion',
    'payment.confirm' => 'Paiement confirmé',
    'payment.waive' => 'Exonération accordée',
    'payment.revert' => 'Confirmation de paiement annulée',
    'registration.cancel' => 'Inscription annulée',
    'registration.delete' => 'Inscription supprimée',
    'registration.resend' => 'Courriel renvoyé',
    'registration.export' => 'Export des inscriptions',
    'attendance.save' => 'Présences enregistrées',
    'attendance.reason' => 'Motif d\'absence enregistré',
    'diploma.generate' => 'Diplôme généré',
    'diploma.send' => 'Diplôme envoyé',
    'diploma.print_bundle' => 'Impression groupée des diplômes',
    'diploma.template_save' => 'Modèle de diplôme enregistré',
    'settings.save' => 'Configuration modifiée',
    'settings.programme' => 'Programme modifié',
    'statistics.caterer_sheet' => 'Fiche traiteur générée',
    'news.create' => 'Actualité créée',
    'news.update' => 'Actualité modifiée',
    'news.delete' => 'Actualité supprimée',
    'user.create' => 'Compte créé',
    'user.disable' => 'Compte désactivé',
    'user.enable' => 'Compte réactivé',
];
?>

<div class="admin-header">
    <div>
        <h1>Utilisateurs et journal</h1>
        <p class="text-muted mb-0">
            Comptes d'administration et traçabilité des actions sensibles.
        </p>
    </div>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Comptes</h2>

    <div class="table-wrap mt-3">
        <table class="table table--compact">
            <caption class="visually-hidden">Comptes d'administration</caption>
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Adresse e-mail</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Dernière connexion</th>
                    <th scope="col">État</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $isSelf = (int) $user['id'] === Auth::id(); ?>
                    <tr>
                        <td>
                            <strong><?= e((string) $user['display_name']) ?></strong>
                            <?php if ($isSelf): ?>
                                <span class="badge badge--info">vous</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e((string) $user['email']) ?></td>
                        <td>
                            <span class="badge <?= (string) $user['role'] === 'admin' ? 'badge--accent' : 'badge--neutral' ?>">
                                <?= (string) $user['role'] === 'admin' ? 'Administrateur' : 'Gestionnaire' ?>
                            </span>
                        </td>
                        <td class="small text-muted">
                            <?= $user['last_login_at'] === null
                                ? 'jamais'
                                : e(fr_datetime((string) $user['last_login_at'])) ?>
                        </td>
                        <td>
                            <?php if ((int) $user['is_active'] === 1): ?>
                                <span class="badge badge--success">Actif</span>
                            <?php else: ?>
                                <span class="badge badge--neutral">Désactivé</span>
                            <?php endif; ?>
                            <?php if (!empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()): ?>
                                <br><span class="badge badge--danger">Verrouillé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$isSelf): ?>
                                <form method="post"
                                      action="<?= e(url('/admin/utilisateurs/' . (int) $user['id'] . '/basculer')) ?>"
                                      class="inline-form"
                                      data-confirm="<?= (int) $user['is_active'] === 1
                                          ? 'Désactiver ce compte ?'
                                          : 'Réactiver ce compte ?' ?>">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm">
                                        <?= (int) $user['is_active'] === 1 ? 'Désactiver' : 'Réactiver' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="small text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Créer un compte</h2>

    <form method="post" action="<?= e(url('/admin/utilisateurs')) ?>" class="mt-3" data-once novalidate>
        <?= Csrf::field() ?>

        <div class="form-grid form-grid--2">
            <div class="form-field">
                <label class="form-label" for="display_name">Nom affiché</label>
                <input class="form-control" type="text" id="display_name" name="display_name" maxlength="120" required
                       value="<?= e((string) ($old['display_name'] ?? '')) ?>"
                    <?= isset($errors['display_name']) ? 'aria-invalid="true" aria-describedby="display_name-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'display_name']) ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="email">Adresse e-mail</label>
                <input class="form-control" type="email" id="email" name="email" maxlength="190" required
                       value="<?= e((string) ($old['email'] ?? '')) ?>"
                    <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
            </div>

            <div class="form-field">
                <label class="form-label" for="role">Rôle</label>
                <select class="form-select" id="role" name="role">
                    <option value="gestionnaire">Gestionnaire — tout sauf configuration et suppressions</option>
                    <option value="admin">Administrateur — accès complet</option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label" for="password">Mot de passe</label>
                <input class="form-control" type="password" id="password" name="password"
                       required minlength="12" autocomplete="new-password"
                    <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
                <p class="form-hint">12 caractères minimum. Une phrase de passe est préférable.</p>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'password']) ?>
            </div>
        </div>

        <button type="submit" class="btn btn--primary btn--sm mt-3">Créer le compte</button>
    </form>
</div>

<div class="panel">
    <h2 style="font-size: 1.125rem;">Journal d'audit</h2>
    <p class="text-muted small">40 dernières actions sensibles. L'adresse IP est conservée hachée.</p>

    <?php if ($audit === []): ?>
        <p class="empty-state mt-3">Aucune action journalisée.</p>
    <?php else: ?>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Journal des actions d'administration</caption>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Auteur</th>
                        <th scope="col">Action</th>
                        <th scope="col">Objet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit as $entry): ?>
                        <tr>
                            <td class="small text-muted"><?= e(fr_datetime((string) $entry['created_at'])) ?></td>
                            <td class="small"><?= e((string) ($entry['display_name'] ?? 'système')) ?></td>
                            <td><?= e($actionLabels[(string) $entry['action']] ?? (string) $entry['action']) ?></td>
                            <td class="small text-muted">
                                <?= e((string) $entry['entity']) ?>
                                <?= $entry['entity_id'] === null ? '' : '#' . e((string) $entry['entity_id']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

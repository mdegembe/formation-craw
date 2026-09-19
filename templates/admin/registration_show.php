<?php

declare(strict_types=1);

use Craw\Core\Auth;
use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Repository\PaymentRepository;
use Craw\Service\RegistrationService;

/**
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>|null $payment
 * @var array<string, mixed>|null $diploma
 * @var array<int, array<string, mixed>> $emails
 */
View::extend('layouts/admin');

$id = (int) $registration['id'];
$paymentStatus = (string) ($payment['status'] ?? 'unpaid');
$isSettled = in_array($paymentStatus, ['paid', 'waived'], true);
?>

<div class="admin-header">
    <div>
        <p class="small text-muted mb-0">
            <a href="<?= e(url('/admin/inscriptions')) ?>">← Toutes les inscriptions</a>
        </p>
        <h1><?= e(RegistrationService::fullName($registration)) ?></h1>
        <p class="text-muted mb-0">
            Référence <strong><?= e((string) $registration['reference']) ?></strong> —
            inscrit le <?= e(fr_datetime((string) $registration['created_at'])) ?>
        </p>
    </div>
    <div class="cluster">
        <span class="badge <?= (string) $registration['status'] === 'confirmed' ? 'badge--success' : 'badge--warning' ?>">
            <?= e(RegistrationService::statusLabel((string) $registration['status'])) ?>
        </span>
        <span class="badge <?= $isSettled ? 'badge--success' : 'badge--warning' ?>">
            <?= e(PaymentRepository::statusLabel($paymentStatus)) ?>
        </span>
    </div>
</div>

<div class="grid grid--2">
    <div class="panel">
        <h2 style="font-size: 1.125rem;">Identité et contact</h2>
        <dl class="definition-list mt-3">
            <dt>Nom complet</dt>
            <dd>
                <?= e(trim((string) ($registration['civility'] ?? '') . ' '
                    . RegistrationService::fullName($registration))) ?>
            </dd>
            <dt>E-mail</dt>
            <dd><a href="mailto:<?= e((string) $registration['email']) ?>">
                <?= e((string) $registration['email']) ?></a></dd>
            <dt>Téléphone</dt>
            <dd><?= e((string) ($registration['phone'] ?? '—')) ?></dd>
            <?php if (!empty($registration['birth_date'])): ?>
                <dt>Date de naissance</dt>
                <dd><?= e(fr_date((string) $registration['birth_date'])) ?></dd>
            <?php endif; ?>
            <dt>Organisation</dt>
            <dd>
                <?= e((string) ($registration['organisation'] ?? '—')) ?>
                <?php if (!empty($registration['job_title'])): ?>
                    <br><span class="text-muted small"><?= e((string) $registration['job_title']) ?></span>
                <?php endif; ?>
            </dd>
            <?php if ((int) $registration['needs_invoice'] === 1): ?>
                <dt>Facturation</dt>
                <dd>
                    <?= e((string) ($registration['street'] ?? '')) ?><br>
                    <?= e((string) ($registration['postal_code'] ?? '')) ?>
                    <?= e((string) ($registration['city'] ?? '')) ?> —
                    <?= e((string) ($registration['country'] ?? '')) ?>
                    <?php if (!empty($registration['vat_number'])): ?>
                        <br>TVA : <?= e((string) $registration['vat_number']) ?>
                    <?php endif; ?>
                </dd>
            <?php endif; ?>
        </dl>
    </div>

    <div class="panel">
        <h2 style="font-size: 1.125rem;">Repas</h2>
        <dl class="definition-list mt-3">
            <dt>Régime</dt>
            <dd>
                <strong><?= e($dietLabel) ?></strong>
                <?php if (!empty($registration['diet_comment'])): ?>
                    <br><span class="text-muted small"><?= e((string) $registration['diet_comment']) ?></span>
                <?php endif; ?>
            </dd>
            <dt>Allergies</dt>
            <dd>
                <?php if ((int) $registration['has_allergies'] === 1): ?>
                    <span class="badge badge--warning">Déclarées</span>
                    <br><span class="small"><?= e((string) $registration['allergies_comment']) ?></span>
                    <?php if (!empty($registration['consent_health_at'])): ?>
                        <br><span class="text-muted small">
                            Consentement santé recueilli le
                            <?= e(fr_datetime((string) $registration['consent_health_at'])) ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">Aucune</span>
                <?php endif; ?>
            </dd>
            <?php if (!empty($registration['logistics_comment'])): ?>
                <dt>Remarques</dt>
                <dd class="text-muted"><?= e((string) $registration['logistics_comment']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<div class="panel mt-3">
    <h2 style="font-size: 1.125rem;">Paiement</h2>

    <dl class="definition-list mt-3">
        <dt>Montant dû</dt>
        <dd class="fw-600"><?= e(money((int) $registration['amount_due_cents'])) ?></dd>
        <dt>Communication structurée</dt>
        <dd style="font-family: var(--craw-font-mono);">
            <?= e((string) ($registration['structured_communication'] ?? '—')) ?>
        </dd>
        <dt>Statut</dt>
        <dd><?= e(PaymentRepository::statusLabel($paymentStatus)) ?></dd>
        <?php if ($payment !== null): ?>
            <?php if (!empty($payment['due_date'])): ?>
                <dt>Échéance</dt>
                <dd><?= e(fr_date((string) $payment['due_date'])) ?></dd>
            <?php endif; ?>
            <?php if ($payment['received_cents'] !== null): ?>
                <dt>Montant reçu</dt>
                <dd class="fw-600"><?= e(money((int) $payment['received_cents'])) ?></dd>
            <?php endif; ?>
            <?php if (!empty($payment['value_date'])): ?>
                <dt>Date de valeur</dt>
                <dd><?= e(fr_date((string) $payment['value_date'])) ?></dd>
            <?php endif; ?>
            <?php if ((int) $payment['reminders_sent'] > 0): ?>
                <dt>Relances envoyées</dt>
                <dd>
                    <?= e((string) $payment['reminders_sent']) ?>
                    <?php if (!empty($payment['last_reminder_at'])): ?>
                        <span class="text-muted small">
                            (dernière le <?= e(fr_date((string) $payment['last_reminder_at'])) ?>)
                        </span>
                    <?php endif; ?>
                </dd>
            <?php endif; ?>
            <?php if (!empty($payment['note'])): ?>
                <dt>Note</dt>
                <dd class="text-muted"><?= e((string) $payment['note']) ?></dd>
            <?php endif; ?>
        <?php endif; ?>
    </dl>

    <?php if (!$isSettled && (string) $registration['status'] !== 'cancelled'): ?>
        <form method="post" action="<?= e(url('/admin/paiements/' . $id . '/confirmer')) ?>"
              class="mt-3" data-once>
            <?= Csrf::field() ?>
            <input type="hidden" name="from" value="detail">

            <h3 style="font-size: 1rem;">Confirmer la réception du paiement</h3>

            <div class="form-grid form-grid--3">
                <div class="form-field">
                    <label class="form-label" for="received">Montant reçu (€)</label>
                    <input class="form-control" type="text" id="received" name="received"
                           inputmode="decimal"
                           placeholder="<?= e(number_format((int) $registration['amount_due_cents'] / 100, 2, ',', '')) ?>">
                    <p class="form-hint">Laisser vide si le montant dû a été réglé intégralement.</p>
                </div>
                <div class="form-field">
                    <label class="form-label" for="value_date">Date de valeur</label>
                    <input class="form-control" type="date" id="value_date" name="value_date"
                           value="<?= e(date('Y-m-d')) ?>">
                </div>
                <div class="form-field">
                    <label class="form-label" for="note">Note interne</label>
                    <input class="form-control" type="text" id="note" name="note" maxlength="500"
                           placeholder="N° d'extrait bancaire…">
                </div>
            </div>

            <div class="btn-group mt-3">
                <button type="submit" class="btn btn--primary btn--sm">
                    Confirmer le paiement
                </button>
            </div>
        </form>

        <form method="post" action="<?= e(url('/admin/paiements/' . $id . '/exonerer')) ?>"
              class="mt-3" data-confirm="Exonérer définitivement ce participant de paiement ?">
            <?= Csrf::field() ?>
            <input type="hidden" name="from" value="detail">
            <input type="hidden" name="note" value="Exonération accordée par l'administration.">
            <button type="submit" class="btn btn--ghost btn--sm">Exonérer de paiement</button>
        </form>
    <?php elseif ($isSettled && Auth::isAdmin()): ?>
        <form method="post" action="<?= e(url('/admin/paiements/' . $id . '/annuler')) ?>"
              class="mt-3" data-confirm="Annuler la confirmation de paiement ? L'inscription repassera en attente.">
            <?= Csrf::field() ?>
            <input type="hidden" name="from" value="detail">
            <button type="submit" class="btn btn--danger btn--sm">
                Annuler la confirmation de paiement
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($diploma !== null): ?>
    <div class="panel panel--accent mt-3">
        <h2 style="font-size: 1.125rem;">Diplôme</h2>
        <dl class="definition-list mt-3">
            <dt>Numéro</dt>
            <dd class="fw-600"><?= e((string) $diploma['number']) ?> (version <?= e((string) $diploma['version']) ?>)</dd>
            <dt>Généré le</dt>
            <dd><?= e(fr_datetime((string) $diploma['generated_at'])) ?></dd>
            <dt>Envoyé</dt>
            <dd>
                <?= $diploma['sent_at'] === null
                    ? '<span class="badge badge--warning">Pas encore envoyé</span>'
                    : e(fr_datetime((string) $diploma['sent_at'])) ?>
            </dd>
            <dt>Code de vérification</dt>
            <dd style="font-family: var(--craw-font-mono);">
                <?= e((string) $diploma['verification_code']) ?>
            </dd>
        </dl>
        <div class="btn-group mt-3">
            <a class="btn btn--ghost btn--sm"
               href="<?= e(url('/admin/diplomes/' . (int) $diploma['id'] . '/telecharger')) ?>">
                Voir le PDF
            </a>
            <a class="btn btn--ghost btn--sm"
               href="<?= e(url('/diplome/verification/' . (string) $diploma['verification_code'])) ?>"
               target="_blank" rel="noopener">
                Page de vérification
            </a>
            <form method="post" action="<?= e(url('/admin/diplomes/' . (int) $diploma['id'] . '/envoyer')) ?>"
                  class="inline-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--outline btn--sm">
                    <?= $diploma['sent_at'] === null ? 'Envoyer par courriel' : 'Renvoyer par courriel' ?>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="panel mt-3">
    <h2 style="font-size: 1.125rem;">Courriels envoyés</h2>

    <?php if ($emails === []): ?>
        <p class="text-muted mt-2 mb-0">Aucun courriel encore envoyé à ce participant.</p>
    <?php else: ?>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Objet</th>
                        <th scope="col">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emails as $email): ?>
                        <tr>
                            <td class="small text-muted"><?= e(fr_datetime((string) $email['sent_at'])) ?></td>
                            <td><?= e((string) $email['subject']) ?></td>
                            <td>
                                <span class="badge <?= (string) $email['status'] === 'sent' ? 'badge--success' : 'badge--danger' ?>">
                                    <?= (string) $email['status'] === 'sent' ? 'Envoyé' : 'Échec' ?>
                                </span>
                                <?php if (!empty($email['error'])): ?>
                                    <br><span class="small text-muted"><?= e((string) $email['error']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/inscriptions/' . $id . '/renvoyer')) ?>" class="mt-3">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--ghost btn--sm">
            Renvoyer la confirmation et les instructions de paiement
        </button>
    </form>
</div>

<div class="panel mt-3">
    <h2 style="font-size: 1.125rem;">Actions</h2>

    <p class="small text-muted mt-2">
        Lien personnel du participant (à ne communiquer qu'à l'intéressé)&nbsp;:<br>
        <code><?= e(url('/mon-inscription/' . (string) $registration['token'])) ?></code>
    </p>

    <?php if ((string) $registration['status'] !== 'cancelled'): ?>
        <form method="post" action="<?= e(url('/admin/inscriptions/' . $id . '/annuler')) ?>"
              class="mt-3" data-confirm="Annuler cette inscription ?">
            <?= Csrf::field() ?>

            <div class="form-grid form-grid--2">
                <div class="form-field">
                    <label class="form-label" for="reason">Motif de l'annulation</label>
                    <input class="form-control" type="text" id="reason" name="reason" maxlength="500"
                           placeholder="Désistement du participant…">
                </div>
                <div class="form-field">
                    <label class="form-label">Notification</label>
                    <div class="form-check">
                        <input type="checkbox" id="notify" name="notify" value="1" checked>
                        <label for="notify">Prévenir le participant par courriel</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--danger btn--sm mt-2">Annuler l'inscription</button>
        </form>
    <?php elseif (Auth::isAdmin()): ?>
        <form method="post" action="<?= e(url('/admin/inscriptions/' . $id . '/supprimer')) ?>"
              class="mt-3"
              data-confirm="Supprimer définitivement cette inscription et toutes ses données ? Cette action est irréversible.">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--danger btn--sm">Supprimer définitivement</button>
        </form>
    <?php endif; ?>
</div>

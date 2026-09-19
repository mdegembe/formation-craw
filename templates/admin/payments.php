<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Repository\PaymentRepository;

/**
 * @var array<int, array<string, mixed>> $pending
 * @var array<string, mixed>             $totals
 */
View::extend('layouts/admin');
?>

<div class="admin-header">
    <div>
        <h1>Paiements</h1>
        <p class="text-muted mb-0">
            Rapprochement manuel des virements reçus sur le compte du CRA-W.
        </p>
    </div>
    <form method="post" action="<?= e(url('/admin/paiements/relancer')) ?>"
          data-confirm="Mettre en file les relances de paiement dues ?">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--outline btn--sm">Envoyer les relances dues</button>
    </form>
</div>

<div class="stat-grid mb-3">
    <div class="stat stat--accent">
        <span class="stat__label">Encaissé</span>
        <span class="stat__value" style="font-size: 1.5rem;"><?= e(money($totals['collected_cents'])) ?></span>
        <span class="stat__hint"><?= e((string) $totals['paid']) ?> paiement(s) confirmé(s)</span>
    </div>
    <div class="stat">
        <span class="stat__label">Restant dû</span>
        <span class="stat__value" style="font-size: 1.5rem;"><?= e(money($totals['outstanding_cents'])) ?></span>
        <span class="stat__hint"><?= e((string) count($pending)) ?> dossier(s) en attente</span>
    </div>
    <div class="stat">
        <span class="stat__label">Partiels</span>
        <span class="stat__value"><?= e((string) $totals['partial']) ?></span>
        <span class="stat__hint">montant reçu inférieur au dû</span>
    </div>
    <div class="stat">
        <span class="stat__label">Exonérés</span>
        <span class="stat__value"><?= e((string) $totals['waived']) ?></span>
        <span class="stat__hint">invités, intervenants</span>
    </div>
</div>

<h2>À rapprocher</h2>

<?php if ($pending === []): ?>
    <p class="empty-state">
        Tous les paiements attendus ont été confirmés. Rien à rapprocher pour l'instant.
    </p>
<?php else: ?>
    <p class="text-muted">
        Comparez la <strong>communication structurée</strong> figurant sur votre extrait bancaire
        avec celle de chaque ligne, puis confirmez. Le participant reçoit alors automatiquement
        un courriel de confirmation.
    </p>

    <div class="table-wrap">
        <table class="table">
            <caption class="visually-hidden">Paiements en attente de confirmation</caption>
            <thead>
                <tr>
                    <th scope="col">Participant</th>
                    <th scope="col">Communication structurée</th>
                    <th scope="col" class="numeric">Montant dû</th>
                    <th scope="col">Échéance</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Confirmer la réception</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $row): ?>
                    <?php $registrationId = (int) $row['registration_id']; ?>
                    <?php
                    $overdue = !empty($row['due_date'])
                        && strtotime((string) $row['due_date']) < strtotime(date('Y-m-d'));
                    ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/admin/inscriptions/' . $registrationId)) ?>">
                                <strong><?= e((string) $row['last_name']) ?>
                                    <?= e((string) $row['first_name']) ?></strong>
                            </a><br>
                            <span class="small text-muted">
                                <?= e((string) $row['reference']) ?>
                                <?php if (!empty($row['organisation'])): ?>
                                    — <?= e((string) $row['organisation']) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td style="font-family: var(--craw-font-mono); font-size: 0.9375rem;">
                            <?= e((string) ($row['structured_communication'] ?? '—')) ?>
                        </td>
                        <td class="numeric fw-600"><?= e(money((int) $row['amount_cents'])) ?></td>
                        <td>
                            <?php if (!empty($row['due_date'])): ?>
                                <span class="<?= $overdue ? 'badge badge--danger' : 'small' ?>">
                                    <?= e(fr_date((string) $row['due_date'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= (string) $row['status'] === 'partial' ? 'badge--warning' : 'badge--neutral' ?>">
                                <?= e(PaymentRepository::statusLabel((string) $row['status'])) ?>
                            </span>
                            <?php if ((int) $row['reminders_sent'] > 0): ?>
                                <br><span class="small text-muted">
                                    <?= e((string) $row['reminders_sent']) ?> relance(s)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post"
                                  action="<?= e(url('/admin/paiements/' . $registrationId . '/confirmer')) ?>"
                                  class="cluster" style="gap: 0.35rem;">
                                <?= Csrf::field() ?>
                                <label class="visually-hidden" for="received-<?= e((string) $registrationId) ?>">
                                    Montant reçu pour <?= e((string) $row['reference']) ?>
                                </label>
                                <input class="form-control" style="width: 7rem;" type="text"
                                       id="received-<?= e((string) $registrationId) ?>" name="received"
                                       inputmode="decimal"
                                       placeholder="<?= e(number_format((int) $row['amount_cents'] / 100, 2, ',', '')) ?>">
                                <label class="visually-hidden" for="date-<?= e((string) $registrationId) ?>">
                                    Date de valeur
                                </label>
                                <input class="form-control" style="width: 9.5rem;" type="date"
                                       id="date-<?= e((string) $registrationId) ?>" name="value_date"
                                       value="<?= e(date('Y-m-d')) ?>">
                                <button type="submit" class="btn btn--primary btn--sm">Confirmer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3">
        Laisser le champ « montant » vide vaut confirmation du montant dû intégral. Un montant
        inférieur bascule le dossier en <strong>paiement partiel</strong> : l'inscription reste en
        attente et aucun courriel de confirmation n'est envoyé.
    </p>
<?php endif; ?>

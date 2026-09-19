<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Domain\DietPreference;
use Craw\Repository\PaymentRepository;
use Craw\Service\RegistrationService;

/**
 * @var array<int, array<string, mixed>> $registrations
 * @var array<string, string>            $filters
 * @var array<int, DietPreference>       $diets
 */
View::extend('layouts/admin');

$query = array_filter([
    'statut' => $filters['status'],
    'paiement' => $filters['payment'],
    'regime' => $filters['diet'],
    'q' => $filters['search'],
]);
$queryString = $query === [] ? '' : '&' . http_build_query($query);
?>

<div class="admin-header">
    <div>
        <h1>Inscriptions</h1>
        <p class="text-muted mb-0">
            <?= e((string) $total) ?> inscription(s) correspondant aux filtres —
            capacité : <?= e((string) $capacity) ?> places
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--outline btn--sm"
           href="<?= e(url('/admin/inscriptions/export?x=1' . $queryString)) ?>">
            Export CSV
        </a>
        <a class="btn btn--ghost btn--sm"
           href="<?= e(url('/admin/inscriptions/export?sante=1' . $queryString)) ?>"
           title="Inclut les allergies déclarées — données de santé, à manipuler avec précaution">
            Export CSV + données santé
        </a>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/inscriptions')) ?>" class="filter-bar no-print">
    <div class="form-field">
        <label class="form-label" for="q">Recherche</label>
        <input class="form-control" type="search" id="q" name="q"
               value="<?= e($filters['search']) ?>" placeholder="Nom, e-mail, organisation, référence">
    </div>

    <div class="form-field">
        <label class="form-label" for="statut">Statut</label>
        <select class="form-select" id="statut" name="statut">
            <option value="">Tous sauf brouillons</option>
            <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Tous, brouillons compris</option>
            <?php foreach (['pending_payment', 'confirmed', 'waitlist', 'cancelled', 'draft'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                    <?= e(RegistrationService::statusLabel($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-field">
        <label class="form-label" for="paiement">Paiement</label>
        <select class="form-select" id="paiement" name="paiement">
            <option value="">Tous</option>
            <?php foreach (['unpaid', 'reminded', 'partial', 'paid', 'waived', 'cancelled'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['payment'] === $status ? 'selected' : '' ?>>
                    <?= e(PaymentRepository::statusLabel($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-field">
        <label class="form-label" for="regime">Régime</label>
        <select class="form-select" id="regime" name="regime">
            <option value="">Tous</option>
            <?php foreach ($diets as $diet): ?>
                <option value="<?= e($diet->value) ?>" <?= $filters['diet'] === $diet->value ? 'selected' : '' ?>>
                    <?= e($diet->label()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="btn-group">
        <button type="submit" class="btn btn--primary btn--sm">Filtrer</button>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/inscriptions')) ?>">Réinitialiser</a>
    </div>
</form>

<?php if ($registrations === []): ?>
    <p class="empty-state">Aucune inscription ne correspond à ces critères.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <caption class="visually-hidden">Liste des inscriptions</caption>
            <thead>
                <tr>
                    <th scope="col">Référence</th>
                    <th scope="col">Participant</th>
                    <th scope="col">Organisation</th>
                    <th scope="col">Régime</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Paiement</th>
                    <th scope="col" class="numeric">Montant</th>
                    <th scope="col">Inscrit le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrations as $row): ?>
                    <?php
                    $paymentStatus = (string) ($row['payment_status'] ?? 'unpaid');
                    $paymentBadge = match ($paymentStatus) {
                        'paid', 'waived' => 'badge--success',
                        'partial' => 'badge--warning',
                        'reminded' => 'badge--info',
                        'cancelled', 'refunded' => 'badge--neutral',
                        default => 'badge--warning',
                    };
                    $statusBadge = match ((string) $row['status']) {
                        'confirmed' => 'badge--success',
                        'pending_payment' => 'badge--warning',
                        'waitlist' => 'badge--info',
                        'cancelled' => 'badge--danger',
                        default => 'badge--neutral',
                    };
                    ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/admin/inscriptions/' . (int) $row['id'])) ?>">
                                <?= e((string) $row['reference']) ?>
                            </a>
                        </td>
                        <td>
                            <strong><?= e(RegistrationService::fullName($row)) ?></strong><br>
                            <span class="small text-muted"><?= e((string) $row['email']) ?></span>
                        </td>
                        <td class="text-muted"><?= e((string) ($row['organisation'] ?? '—')) ?></td>
                        <td>
                            <span class="small"><?= e(DietPreference::tryLabel((string) $row['diet'])) ?></span>
                            <?php if ((int) $row['has_allergies'] === 1): ?>
                                <br><span class="badge badge--warning">Allergies</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= e($statusBadge) ?>">
                                <?= e(RegistrationService::statusLabel((string) $row['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= e($paymentBadge) ?>">
                                <?= e(PaymentRepository::statusLabel($paymentStatus)) ?>
                            </span>
                        </td>
                        <td class="numeric"><?= e(money((int) $row['amount_due_cents'])) ?></td>
                        <td class="small text-muted"><?= e(fr_date((string) $row['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3">
        L'export standard exclut les allergies déclarées&nbsp;: ce sont des données de santé,
        qui ne doivent circuler que lorsque c'est nécessaire (fiche traiteur). Un export
        spécifique les inclut, et chaque export est journalisé.
    </p>
<?php endif; ?>

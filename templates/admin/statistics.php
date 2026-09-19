<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Domain\DietPreference;
use Craw\Service\RegistrationService;

/**
 * @var array<string, int> $headline
 * @var array<int, array<string, mixed>> $breakdown
 * @var array<int, array<string, mixed>> $allergies
 * @var array<int, array<string, mixed>> $dietComments
 * @var array<int, array<string, mixed>> $logisticsComments
 */
View::extend('layouts/admin');

$active = array_values(array_filter($breakdown, static fn (array $row): bool => $row['total'] > 0));
?>

<div class="admin-header">
    <div>
        <h1>Statistiques alimentaires</h1>
        <p class="text-muted mb-0">
            Effectifs à restaurer et contraintes déclarées, pour la commande des repas.
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--primary btn--sm" href="<?= e(url('/admin/statistiques/traiteur.pdf')) ?>">
            Fiche traiteur (PDF)
        </a>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/statistiques/export')) ?>">Export CSV</a>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat stat--accent">
        <span class="stat__label">Repas à prévoir</span>
        <span class="stat__value"><?= e((string) $headline['total']) ?></span>
        <span class="stat__hint">inscriptions actives</span>
    </div>
    <div class="stat">
        <span class="stat__label">Régimes particuliers</span>
        <span class="stat__value"><?= e((string) $headline['special_diets']) ?></span>
        <span class="stat__hint">hors « sans restriction »</span>
    </div>
    <div class="stat">
        <span class="stat__label">Allergies déclarées</span>
        <span class="stat__value"><?= e((string) $headline['with_allergies']) ?></span>
        <span class="stat__hint">à traiter individuellement</span>
    </div>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Répartition par régime</h2>

    <?php if ($active === []): ?>
        <p class="empty-state mt-3">Aucune inscription active pour l'instant.</p>
    <?php else: ?>
        <div class="bar-chart mt-3" role="img"
             aria-label="Répartition des participants par régime alimentaire">
            <?php foreach ($active as $row): ?>
                <?php $width = (int) round($row['total'] / $maxTotal * 100); ?>
                <div class="bar-chart__row">
                    <span class="bar-chart__label"><?= e($row['label']) ?></span>
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill"
                             style="width: <?= e((string) max(2, $width)) ?>%; background-color: <?= e($row['color']) ?>;"></div>
                    </div>
                    <span class="bar-chart__value">
                        <?= e((string) $row['total']) ?>
                        <span class="text-muted">(<?= e(number_format($row['percentage'], 1, ',', '')) ?>&nbsp;%)</span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-wrap mt-4">
            <table class="table table--compact">
                <caption class="visually-hidden">Détail des effectifs par régime et statut</caption>
                <thead>
                    <tr>
                        <th scope="col">Régime</th>
                        <th scope="col" class="numeric">Total</th>
                        <th scope="col" class="numeric">Confirmés</th>
                        <th scope="col" class="numeric">En attente de paiement</th>
                        <th scope="col" class="numeric">Part</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active as $row): ?>
                        <tr>
                            <td>
                                <span style="display:inline-block;width:0.75rem;height:0.75rem;border-radius:2px;background-color:<?= e($row['color']) ?>;margin-right:0.4rem;"
                                      aria-hidden="true"></span>
                                <?= e($row['label']) ?>
                            </td>
                            <td class="numeric fw-600"><?= e((string) $row['total']) ?></td>
                            <td class="numeric"><?= e((string) $row['confirmed']) ?></td>
                            <td class="numeric"><?= e((string) $row['pending']) ?></td>
                            <td class="numeric"><?= e(number_format($row['percentage'], 1, ',', '')) ?>&nbsp;%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="small text-muted mt-3">
            La colonne « en attente de paiement » permet de ne commander que les repas confirmés
            si vous le souhaitez&nbsp;: ces participants n'ont pas encore réglé leur inscription.
        </p>
    <?php endif; ?>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">
        Allergies et intolérances
        <span class="badge badge--warning">Données de santé</span>
    </h2>
    <p class="text-muted small">
        Ces informations ne doivent être communiquées qu'au traiteur, et uniquement pour la
        préparation des repas.
    </p>

    <?php if ($allergies === []): ?>
        <p class="empty-state mt-3">Aucune allergie déclarée à ce jour.</p>
    <?php else: ?>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Allergies déclarées par les participants</caption>
                <thead>
                    <tr>
                        <th scope="col">Participant</th>
                        <th scope="col">Régime</th>
                        <th scope="col">Allergies et intolérances déclarées</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allergies as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/inscriptions/' . (int) $row['id'])) ?>">
                                    <?= e(RegistrationService::fullName($row)) ?>
                                </a>
                            </td>
                            <td class="small"><?= e(DietPreference::tryLabel((string) $row['diet'])) ?></td>
                            <td><?= e((string) $row['allergies_comment']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Précisions sur les régimes</h2>

    <?php if ($dietComments === []): ?>
        <p class="empty-state mt-3">Aucune précision de régime renseignée.</p>
    <?php else: ?>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Commentaires libres sur les régimes</caption>
                <thead>
                    <tr>
                        <th scope="col">Participant</th>
                        <th scope="col">Régime</th>
                        <th scope="col">Précision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dietComments as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/inscriptions/' . (int) $row['id'])) ?>">
                                    <?= e(RegistrationService::fullName($row)) ?>
                                </a>
                            </td>
                            <td class="small"><?= e(DietPreference::tryLabel((string) $row['diet'])) ?></td>
                            <td><?= e((string) $row['diet_comment']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($logisticsComments !== []): ?>
    <div class="panel">
        <h2 style="font-size: 1.125rem;">Remarques d'organisation</h2>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <caption class="visually-hidden">Remarques logistiques des participants</caption>
                <thead>
                    <tr>
                        <th scope="col">Participant</th>
                        <th scope="col">Remarque</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logisticsComments as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/inscriptions/' . (int) $row['id'])) ?>">
                                    <?= e(RegistrationService::fullName($row)) ?>
                                </a>
                            </td>
                            <td><?= e((string) $row['logistics_comment']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

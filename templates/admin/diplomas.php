<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Domain\AttendanceCalculator;
use Craw\Service\RegistrationService;

/**
 * @var array<int, array<string, mixed>> $summary
 * @var array<int, array<string, mixed>> $diplomas  Indexes par registration_id
 * @var array<string, mixed>|null        $template
 */
View::extend('layouts/admin');
?>

<div class="admin-header">
    <div>
        <h1>Diplômes</h1>
        <p class="text-muted mb-0">
            Seuil de présence requis&nbsp;: <strong><?= e(AttendanceCalculator::format($minRate)) ?></strong>
            — modifiable dans la <a href="<?= e(url('/admin/configuration')) ?>">configuration</a>.
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/diplomes/modele')) ?>">Modèle</a>
        <?php if ($generatedCount > 0): ?>
            <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/diplomes/impression.pdf')) ?>">
                Tout imprimer (PDF unique)
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($template === null): ?>
    <div class="alert alert--info" role="status">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
        </svg>
        <div class="alert__body">
            <strong>Aucun modèle personnalisé n'est déposé.</strong>
            Les diplômes seront produits avec le modèle par défaut du site.
            <a href="<?= e(url('/admin/diplomes/modele')) ?>">Déposez votre propre modèle</a>
            si vous disposez d'un visuel officiel.
        </div>
    </div>
<?php endif; ?>

<div class="stat-grid mb-3">
    <div class="stat stat--accent">
        <span class="stat__label">Éligibles</span>
        <span class="stat__value"><?= e((string) $eligibleCount) ?></span>
        <span class="stat__hint">présence et paiement en règle</span>
    </div>
    <div class="stat">
        <span class="stat__label">Diplômes générés</span>
        <span class="stat__value"><?= e((string) $generatedCount) ?></span>
    </div>
    <div class="stat">
        <span class="stat__label">Restant à générer</span>
        <span class="stat__value"><?= e((string) max(0, $pendingCount)) ?></span>
    </div>
</div>

<div class="panel mb-3">
    <h2 style="font-size: 1.125rem;">Génération et envoi</h2>

    <div class="btn-group mt-3">
        <form method="post" action="<?= e(url('/admin/diplomes/generer')) ?>" class="inline-form"
              data-confirm="Générer les diplômes de tous les participants éligibles ?">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--primary btn--sm">
                Générer les diplômes manquants
            </button>
        </form>

        <form method="post" action="<?= e(url('/admin/diplomes/generer')) ?>" class="inline-form"
              data-confirm="Régénérer TOUS les diplômes ? Les versions précédentes sont conservées mais une nouvelle version est créée pour chacun.">
            <?= Csrf::field() ?>
            <input type="hidden" name="regenerate" value="1">
            <button type="submit" class="btn btn--ghost btn--sm">Tout régénérer</button>
        </form>

        <form method="post" action="<?= e(url('/admin/diplomes/envoyer-tous')) ?>" class="inline-form"
              data-confirm="Envoyer par courriel tous les diplômes non encore transmis ?">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--outline btn--sm">
                Envoyer les diplômes non transmis
            </button>
        </form>
    </div>

    <p class="small text-muted mt-3 mb-0">
        L'envoi met les messages en file d'attente&nbsp;: ils partent au prochain passage du cron,
        avec le PDF en pièce jointe. « Tout imprimer » produit un fichier PDF unique, une page par
        participant, prêt à envoyer à l'imprimante.
    </p>
</div>

<?php if ($summary === []): ?>
    <p class="empty-state">
        Aucun participant confirmé. Les diplômes ne peuvent être générés qu'une fois les
        inscriptions confirmées et les présences relevées.
    </p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <caption class="visually-hidden">Éligibilité au diplôme par participant</caption>
            <thead>
                <tr>
                    <th scope="col">Participant</th>
                    <th scope="col" class="numeric">Présence</th>
                    <th scope="col">Éligibilité</th>
                    <th scope="col">Diplôme</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $row): ?>
                    <?php
                    $registrationId = (int) $row['id'];
                    $diploma = $diplomas[$registrationId] ?? null;
                    ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/admin/inscriptions/' . $registrationId)) ?>">
                                <strong><?= e(RegistrationService::fullName($row)) ?></strong>
                            </a><br>
                            <span class="small text-muted"><?= e((string) $row['email']) ?></span>
                        </td>
                        <td class="numeric">
                            <span class="rate-pill <?= $row['eligible'] ? 'rate-pill--ok' : 'rate-pill--ko' ?>">
                                <?= e((string) $row['attendance_label']) ?>
                            </span><br>
                            <span class="small text-muted">
                                <?= e((string) $row['present_slots']) ?>/<?= e((string) $row['total_slots']) ?> créneaux
                            </span>
                        </td>
                        <td>
                            <?php if ($row['eligible'] === true): ?>
                                <span class="badge badge--success">Éligible</span>
                            <?php else: ?>
                                <span class="badge badge--danger">Non éligible</span><br>
                                <span class="small text-muted"><?= e((string) $row['eligibility_message']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($diploma === null): ?>
                                <span class="text-muted">—</span>
                            <?php else: ?>
                                <span class="small" style="font-family: var(--craw-font-mono);">
                                    <?= e((string) $diploma['number']) ?>
                                </span><br>
                                <?php if ($diploma['sent_at'] === null): ?>
                                    <span class="badge badge--warning">Non envoyé</span>
                                <?php else: ?>
                                    <span class="badge badge--success">
                                        Envoyé le <?= e(fr_date((string) $diploma['sent_at'])) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($diploma !== null): ?>
                                <div class="btn-group">
                                    <a class="btn btn--ghost btn--sm"
                                       href="<?= e(url('/admin/diplomes/' . (int) $diploma['id'] . '/telecharger')) ?>">
                                        PDF
                                    </a>
                                    <form method="post"
                                          action="<?= e(url('/admin/diplomes/' . (int) $diploma['id'] . '/envoyer')) ?>"
                                          class="inline-form">
                                        <?= Csrf::field() ?>
                                        <button type="submit" class="btn btn--outline btn--sm">
                                            <?= $diploma['sent_at'] === null ? 'Envoyer' : 'Renvoyer' ?>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="small text-muted">
                                    <?= $row['eligible'] === true ? 'À générer' : 'Aucun' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

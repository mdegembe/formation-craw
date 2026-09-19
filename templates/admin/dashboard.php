<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Domain\AttendanceCalculator;
use Craw\Repository\PaymentRepository;
use Craw\Service\RegistrationService;

/**
 * @var array<string, mixed> $training
 * @var array<string, int>   $counters
 * @var array<string, mixed> $payments
 * @var array<string, int>   $diet
 * @var array<string, mixed>|null $attendance
 * @var array<int, array<string, mixed>> $recent
 */
View::extend('layouts/admin');

$subjectPending = (bool) $training['subject_pending'];
?>

<div class="admin-header">
    <div>
        <h1>Tableau de bord</h1>
        <p class="text-muted mb-0">
            <?= e((string) $training['title']) ?> —
            du <?= e(fr_date((string) $training['starts_on'])) ?>
            au <?= e(fr_date((string) $training['ends_on'])) ?>
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/inscriptions/export')) ?>">
            Exporter les inscrits
        </a>
        <a class="btn btn--primary btn--sm" href="<?= e(url('/admin/configuration')) ?>">Configuration</a>
    </div>
</div>

<?php if ($subjectPending): ?>
    <div class="alert alert--warning" role="status">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M7.938 2.016a.13.13 0 0 1 .125 0 .13.13 0 0 1 .06.055l6.857 11.667c.05.09.002.163-.043.187a.13.13 0 0 1-.064.015H1.127a.13.13 0 0 1-.064-.015c-.045-.024-.093-.097-.043-.187zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.437-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
        </svg>
        <div class="alert__body">
            <strong>Le sujet de la formation n'est pas encore défini.</strong>
            Le site affiche une mention « en cours de définition » sur les pages publiques.
            <a href="<?= e(url('/admin/configuration')) ?>">Renseignez-le</a> dès qu'il est arrêté.
        </div>
    </div>
<?php endif; ?>

<div class="stat-grid mb-3">
    <div class="stat stat--accent">
        <span class="stat__label">Inscriptions actives</span>
        <span class="stat__value"><?= e((string) $counters['active']) ?></span>
        <span class="stat__hint">
            sur <?= e((string) $capacity) ?> places — <?= e((string) $fillRate) ?>&nbsp;% de remplissage
        </span>
    </div>
    <div class="stat">
        <span class="stat__label">Confirmées</span>
        <span class="stat__value"><?= e((string) $counters['confirmed']) ?></span>
        <span class="stat__hint">paiement reçu ou exonéré</span>
    </div>
    <div class="stat">
        <span class="stat__label">En attente de paiement</span>
        <span class="stat__value"><?= e((string) $counters['pending_payment']) ?></span>
        <span class="stat__hint">
            <?= e(money($payments['outstanding_cents'])) ?> à encaisser
        </span>
    </div>
    <div class="stat">
        <span class="stat__label">Liste d'attente</span>
        <span class="stat__value"><?= e((string) $counters['waitlist']) ?></span>
        <span class="stat__hint"><?= e((string) $remainingSeats) ?> place(s) libre(s)</span>
    </div>
</div>

<div class="grid grid--2 mb-3">
    <div class="panel">
        <h2 style="font-size: 1.125rem;">Paiements</h2>
        <dl class="definition-list mt-3" style="grid-template-columns: 1fr auto;">
            <dt>Encaissé</dt>
            <dd class="text-end fw-600"><?= e(money($payments['collected_cents'])) ?></dd>
            <dt>Restant dû</dt>
            <dd class="text-end fw-600"><?= e(money($payments['outstanding_cents'])) ?></dd>
            <dt>Réglés</dt>
            <dd class="text-end"><?= e((string) $payments['paid']) ?></dd>
            <dt>En attente ou relancés</dt>
            <dd class="text-end"><?= e((string) $payments['unpaid']) ?></dd>
            <dt>Partiels</dt>
            <dd class="text-end"><?= e((string) $payments['partial']) ?></dd>
            <dt>Exonérés</dt>
            <dd class="text-end"><?= e((string) $payments['waived']) ?></dd>
        </dl>
        <a class="btn btn--ghost btn--sm mt-3" href="<?= e(url('/admin/paiements')) ?>">
            Gérer les paiements
        </a>
    </div>

    <div class="panel">
        <h2 style="font-size: 1.125rem;">Repas</h2>
        <dl class="definition-list mt-3" style="grid-template-columns: 1fr auto;">
            <dt>Participants à restaurer</dt>
            <dd class="text-end fw-600"><?= e((string) $diet['total']) ?></dd>
            <dt>Régimes particuliers</dt>
            <dd class="text-end fw-600"><?= e((string) $diet['special_diets']) ?></dd>
            <dt>Allergies déclarées</dt>
            <dd class="text-end fw-600"><?= e((string) $diet['with_allergies']) ?></dd>
        </dl>
        <div class="btn-group mt-3">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/statistiques')) ?>">Statistiques</a>
            <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/statistiques/traiteur.pdf')) ?>">
                Fiche traiteur (PDF)
            </a>
        </div>
    </div>
</div>

<?php if ($attendance !== null): ?>
    <div class="panel mb-3">
        <h2 style="font-size: 1.125rem;">Présences et diplômes</h2>
        <div class="stat-grid mt-3">
            <div class="stat">
                <span class="stat__label">Taux de présence moyen</span>
                <span class="stat__value"><?= e(AttendanceCalculator::format($attendance['average_rate'])) ?></span>
            </div>
            <div class="stat">
                <span class="stat__label">Éligibles au diplôme</span>
                <span class="stat__value"><?= e((string) $attendance['eligible']) ?></span>
                <span class="stat__hint">seuil : <?= e(AttendanceCalculator::format($minRate)) ?></span>
            </div>
            <div class="stat">
                <span class="stat__label">Non éligibles</span>
                <span class="stat__value"><?= e((string) $attendance['not_eligible']) ?></span>
            </div>
        </div>
        <div class="btn-group mt-3">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/presences')) ?>">Pointer les présences</a>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/diplomes')) ?>">Diplômes</a>
        </div>
    </div>
<?php else: ?>
    <div class="panel panel--accent mb-3">
        <h2 style="font-size: 1.125rem;">Présences</h2>
        <p class="text-muted mb-2">
            La formation débute le <?= e(fr_date((string) $training['starts_on'], true)) ?>.
            Le pointage des présences sera pertinent à partir de cette date. Rappel du principe :
            <strong>tout le monde est présumé présent</strong>, vous ne saisissez que les absences.
        </p>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/presences')) ?>">Voir la grille</a>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="cluster" style="justify-content: space-between;">
        <h2 style="font-size: 1.125rem;">Dernières inscriptions</h2>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/inscriptions')) ?>">Tout voir</a>
    </div>

    <?php if ($recent === []): ?>
        <p class="empty-state mt-3">Aucune inscription pour l'instant.</p>
    <?php else: ?>
        <div class="table-wrap mt-3">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th scope="col">Référence</th>
                        <th scope="col">Participant</th>
                        <th scope="col">Organisation</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Paiement</th>
                        <th scope="col">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/inscriptions/' . (int) $row['id'])) ?>">
                                    <?= e((string) $row['reference']) ?>
                                </a>
                            </td>
                            <td><?= e(RegistrationService::fullName($row)) ?></td>
                            <td class="text-muted"><?= e((string) ($row['organisation'] ?? '—')) ?></td>
                            <td><?= e(RegistrationService::statusLabel((string) $row['status'])) ?></td>
                            <td><?= e(PaymentRepository::statusLabel((string) ($row['payment_status'] ?? 'unpaid'))) ?></td>
                            <td class="text-muted small"><?= e(fr_date((string) $row['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pendingMails > 0 || $unhandledMessages > 0): ?>
    <div class="panel mt-3">
        <h2 style="font-size: 1.125rem;">À traiter</h2>
        <ul class="footer-list mt-2">
            <?php if ($pendingMails > 0): ?>
                <li>
                    <strong><?= e((string) $pendingMails) ?></strong> courriel(s) en file d'envoi.
                    Ils partent au prochain passage du cron
                    (<code>php bin/console.php cron</code>).
                </li>
            <?php endif; ?>
            <?php if ($unhandledMessages > 0): ?>
                <li>
                    <strong><?= e((string) $unhandledMessages) ?></strong> message(s) de contact non traité(s).
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Domain\AttendanceCalculator;
use Craw\Service\RegistrationService;

/**
 * Grille de pointage « a l'envers » : toutes les cases sont cochees
 * (= present) par defaut, l'administrateur decoche les absents.
 *
 * @var array<int, array<string, mixed>> $days
 * @var array<int, array<string, mixed>> $participants
 * @var array<int, array<int, true>> $absences
 * @var array<int, array<string, mixed>> $summary
 */
View::extend('layouts/admin');

$summaryById = [];
foreach ($summary as $row) {
    $summaryById[(int) $row['id']] = $row;
}
?>

<div class="admin-header">
    <div>
        <h1>Présences</h1>
        <p class="text-muted mb-0">
            <?= e((string) count($participants)) ?> participant(s) —
            <?= e((string) count($days)) ?> créneau(x)
        </p>
    </div>
    <div class="btn-group">
        <a class="btn btn--outline btn--sm" href="<?= e(url('/admin/presences/emargement.pdf')) ?>">
            Feuilles d'émargement (PDF)
        </a>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/presences/export')) ?>">Export CSV</a>
    </div>
</div>

<div class="panel panel--accent mb-3">
    <p class="mb-0">
        <strong>Le principe : tout le monde est présumé présent.</strong>
        Les cases sont cochées par défaut&nbsp;; il vous suffit de <strong>décocher les absents</strong>.
        Seules les absences sont enregistrées en base — vous n'avez rien à faire pour les présents.
    </p>
</div>

<?php if ($participants === []): ?>
    <p class="empty-state">
        Aucun participant confirmé pour l'instant. La grille se remplira à mesure que les
        inscriptions sont confirmées.
    </p>
<?php else: ?>
    <div class="stat-grid mb-3">
        <div class="stat stat--accent">
            <span class="stat__label">Taux de présence moyen</span>
            <span class="stat__value"><?= e(AttendanceCalculator::format($statistics['average_rate'])) ?></span>
        </div>
        <div class="stat">
            <span class="stat__label">Éligibles au diplôme</span>
            <span class="stat__value"><?= e((string) $statistics['eligible']) ?></span>
            <span class="stat__hint">seuil : <?= e(AttendanceCalculator::format($minRate)) ?></span>
        </div>
        <div class="stat">
            <span class="stat__label">Sous le seuil</span>
            <span class="stat__value"><?= e((string) $statistics['not_eligible']) ?></span>
            <span class="stat__hint">pas de diplôme en l'état</span>
        </div>
    </div>

    <form method="post" action="<?= e(url('/admin/presences')) ?>">
        <?= Csrf::field() ?>

        <p class="attendance-legend">
            <span><strong>Case cochée</strong> = présent</span>
            <span><strong>Case décochée</strong> = absent</span>
            <span>Les boutons « tout » basculent une colonne entière.</span>
        </p>

        <div class="table-wrap">
            <table class="table table--compact attendance-grid">
                <caption class="visually-hidden">
                    Grille de présence : une ligne par participant, une colonne par créneau
                </caption>
                <thead>
                    <tr>
                        <th scope="col" class="participant">Participant</th>
                        <?php foreach ($days as $day): ?>
                            <?php
                            $dayDate = new DateTimeImmutable((string) $day['day_date']);
                            $slotLabel = (string) $day['slot'] === 'am'
                                ? 'Matin'
                                : ((string) $day['slot'] === 'pm' ? 'A-m.' : 'Journée');
                            ?>
                            <th scope="col" class="slot">
                                <input type="hidden" name="days[]" value="<?= e((string) $day['id']) ?>">
                                <?= e($dayDate->format('d/m')) ?><br>
                                <?= e($slotLabel) ?>
                                <?php if (!(bool) $day['counts_for_attendance']): ?>
                                    <br><span class="text-muted" style="font-size: 0.65rem;">hors calcul</span>
                                <?php endif; ?>
                                <br>
                                <button type="button" class="copy-btn no-print"
                                        style="padding: 0.1rem 0.3rem; font-size: 0.7rem; margin-top: 0.2rem;"
                                        data-mark-all="<?= e((string) $day['id']) ?>" data-state="present">
                                    tout
                                    <span class="visually-hidden">
                                        basculer la présence de tous les participants sur ce créneau
                                    </span>
                                </button>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="numeric">Taux</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $participant): ?>
                        <?php
                        $participantId = (int) $participant['id'];
                        $row = $summaryById[$participantId] ?? null;
                        $rate = $row === null ? 0.0 : (float) $row['attendance_rate'];
                        $eligible = $row !== null && $row['eligible'] === true;
                        ?>
                        <tr>
                            <th scope="row" class="participant" style="font-weight: 500; text-align: left;">
                                <a href="<?= e(url('/admin/inscriptions/' . $participantId)) ?>">
                                    <?= e(RegistrationService::fullName($participant)) ?>
                                </a>
                                <?php if (!empty($participant['organisation'])): ?>
                                    <br><span class="small text-muted">
                                        <?= e(str_excerpt((string) $participant['organisation'], 34)) ?>
                                    </span>
                                <?php endif; ?>
                            </th>

                            <?php foreach ($days as $day): ?>
                                <?php
                                $dayId = (int) $day['id'];
                                $isPresent = !isset($absences[$participantId][$dayId]);
                                ?>
                                <td class="slot">
                                    <input type="checkbox"
                                           class="presence-box"
                                           data-slot="<?= e((string) $dayId) ?>"
                                           name="present_<?= e((string) $dayId) ?>[]"
                                           value="<?= e((string) $participantId) ?>"
                                        <?= $isPresent ? 'checked' : '' ?>
                                           id="p<?= e((string) $participantId) ?>d<?= e((string) $dayId) ?>">
                                    <label class="visually-hidden"
                                           for="p<?= e((string) $participantId) ?>d<?= e((string) $dayId) ?>">
                                        <?= e(RegistrationService::fullName($participant)) ?> présent le
                                        <?= e((string) ($day['label'] ?? $day['day_date'])) ?>
                                    </label>
                                </td>
                            <?php endforeach; ?>

                            <td class="numeric">
                                <span class="rate-pill <?= $eligible ? 'rate-pill--ok' : 'rate-pill--ko' ?>">
                                    <?= e(AttendanceCalculator::format($rate)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions no-print">
            <button type="submit" class="btn btn--primary btn--pill">Enregistrer les présences</button>
            <p class="small text-muted mb-0">
                L'enregistrement remplace les absences de tous les créneaux affichés.
            </p>
        </div>
    </form>

<?php endif; ?>

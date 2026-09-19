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
 * @var array<int, array<int, array{is_justified: bool, reason: string|null}>> $absences
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
            <span class="stat__hint">
                absences justifiées
                <?= $justifiedCountsPresent ? 'comptées présentes' : 'comptées absentes' ?>
            </span>
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
                                $absence = $absences[$participantId][$dayId] ?? null;
                                $isPresent = $absence === null;
                                $justified = $absence !== null && $absence['is_justified'];
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
                                    <?php if ($justified): ?>
                                        <br><span class="badge badge--info"
                                                  style="font-size: 0.65rem; padding: 0 0.3rem;"
                                                  title="<?= e((string) ($absence['reason'] ?? '')) ?>">just.</span>
                                    <?php endif; ?>
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

    <div class="panel mt-4">
        <h2 style="font-size: 1.125rem;">Préciser le motif d'une absence</h2>
        <p class="text-muted">
            Une absence justifiée (certificat médical, mission de service) peut être comptée comme
            une présence dans le calcul du diplôme&nbsp;: ce comportement se règle dans la
            <a href="<?= e(url('/admin/configuration')) ?>">configuration</a>.
            Actuellement, les absences justifiées sont
            <strong><?= $justifiedCountsPresent ? 'comptées comme des présences' : 'comptées comme des absences' ?></strong>.
        </p>

        <form method="post" action="<?= e(url('/admin/presences/motif')) ?>" class="mt-3">
            <?= Csrf::field() ?>

            <div class="form-grid form-grid--2">
                <div class="form-field">
                    <label class="form-label" for="registration_id">Participant</label>
                    <select class="form-select" id="registration_id" name="registration_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($participants as $participant): ?>
                            <option value="<?= e((string) $participant['id']) ?>">
                                <?= e(RegistrationService::fullName($participant)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label" for="training_day_id">Créneau</label>
                    <select class="form-select" id="training_day_id" name="training_day_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($days as $day): ?>
                            <option value="<?= e((string) $day['id']) ?>">
                                <?= e((string) ($day['label'] ?? $day['day_date'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field form-field--full">
                    <label class="form-label" for="reason">Motif</label>
                    <input class="form-control" type="text" id="reason" name="reason" maxlength="255"
                           placeholder="Certificat médical transmis, mission de service…">
                </div>

                <div class="form-field">
                    <div class="form-check">
                        <input type="checkbox" id="is_justified" name="is_justified" value="1" checked>
                        <label for="is_justified">Absence justifiée</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn--ghost btn--sm mt-2">Enregistrer le motif</button>
        </form>
    </div>
<?php endif; ?>

<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Service\RegistrationService;

/**
 * Feuille d'emargement papier : une page par creneau, a faire signer sur place.
 *
 * @var array<string, mixed>             $training
 * @var array<int, array<string, mixed>> $days
 * @var array<int, array<string, mixed>> $participants
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Feuilles d'émargement</title>
    <style>
        <?= View::partial('pdf/_styles') ?>
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        table.sign { width: 100%; border-collapse: collapse; margin-top: 8pt; }
        table.sign th, table.sign td { border: 1px solid #adb5bd; padding: 5pt 7pt; }
        table.sign th { background-color: #f5f5f5; font-size: 8.5pt; text-transform: uppercase; }
        table.sign td.num { width: 8%; text-align: center; color: #5c636a; }
        table.sign td.sig { width: 32%; }
    </style>
</head>
<body>

<?php foreach ($days as $day): ?>
    <?php
    $date = new DateTimeImmutable((string) $day['day_date']);
    $slot = (string) $day['slot'] === 'am'
        ? 'Matin'
        : ((string) $day['slot'] === 'pm' ? 'Après-midi' : 'Journée');
    ?>
    <div class="sheet">
        <div class="brand-bar">
            <span class="brand-name">CRA-W — Formation</span><br>
            <span class="brand-sub">Centre wallon de Recherches agronomiques</span>
        </div>

        <h1>Feuille d'émargement</h1>

        <table class="kv">
            <tr>
                <td class="label">Formation</td>
                <td class="value"><?= e((string) $training['title']) ?></td>
            </tr>
            <tr>
                <td class="label">Date et créneau</td>
                <td class="value"><?= e(ucfirst(fr_date($date, true))) ?> — <?= e($slot) ?></td>
            </tr>
            <tr>
                <td class="label">Horaire</td>
                <td class="value">
                    <?= e((string) ($day['starts_at'] ?? '')) ?> – <?= e((string) ($day['ends_at'] ?? '')) ?>
                </td>
            </tr>
            <tr>
                <td class="label">Lieu</td>
                <td class="value"><?= e((string) $training['location_name']) ?></td>
            </tr>
        </table>

        <?php if (!empty($day['topic'])): ?>
            <p><strong>Session :</strong> <?= e((string) $day['topic']) ?>
                <?php if (!empty($day['speaker'])): ?>
                    — <span class="muted"><?= e((string) $day['speaker']) ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if ($participants === []): ?>
            <p class="muted">Aucun participant confirmé à ce jour.</p>
        <?php else: ?>
            <table class="sign">
                <thead>
                    <tr>
                        <th class="num">N°</th>
                        <th>Nom et prénom</th>
                        <th>Organisation</th>
                        <th class="sig">Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $index => $participant): ?>
                        <tr>
                            <td class="num"><?= e((string) ($index + 1)) ?></td>
                            <td class="signature-cell">
                                <?= e(RegistrationService::fullName($participant)) ?>
                            </td>
                            <td class="small"><?= e((string) ($participant['organisation'] ?? '')) ?></td>
                            <td class="sig"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="footer-note">
            Document à conserver par l'organisateur. Les absences constatées sur cette feuille sont à
            reporter dans le back-office (rubrique « Présences »)&nbsp;: seules les absences y sont
            saisies, les participants étant présumés présents.
            <br>Visa du formateur&nbsp;: ______________________
        </div>
    </div>
<?php endforeach; ?>

</body>
</html>

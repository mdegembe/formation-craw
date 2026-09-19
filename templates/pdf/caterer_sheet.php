<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Domain\DietPreference;
use Craw\Service\RegistrationService;

/**
 * Fiche a transmettre au traiteur.
 *
 * Principe de minimisation : effectifs par regime et contraintes individuelles,
 * sans aucune coordonnee de contact.
 *
 * @var array<string, mixed> $training
 * @var array<string, mixed> $sheet
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche traiteur</title>
    <style>
        <?= View::partial('pdf/_styles') ?>
        .swatch { display: inline-block; width: 8pt; height: 8pt; margin-right: 4pt; }
    </style>
</head>
<body>

<div class="brand-bar">
    <span class="brand-name">CRA-W — Formation</span><br>
    <span class="brand-sub">Centre wallon de Recherches agronomiques</span>
</div>

<h1>Fiche traiteur</h1>
<p class="muted">
    <?= e((string) $training['title']) ?> —
    du <?= e(fr_date((string) $training['starts_on'])) ?>
    au <?= e(fr_date((string) $training['ends_on'])) ?><br>
    <?= e((string) $training['location_name']) ?>, <?= e((string) $training['location_address']) ?><br>
    Document établi le <?= e(fr_date(date('Y-m-d'))) ?>
</p>

<div class="note">
    <strong>Effectif total à restaurer : <?= e((string) $sheet['headline']['total']) ?> personnes</strong>,
    dont <?= e((string) $sheet['headline']['special_diets']) ?> régime(s) particulier(s)
    et <?= e((string) $sheet['headline']['with_allergies']) ?> allergie(s) déclarée(s).
    Ces effectifs correspondent à une journée&nbsp;; la formation compte 5 jours.
</div>

<h2>Effectifs par régime</h2>

<?php if ($sheet['breakdown'] === []): ?>
    <p class="muted">Aucune inscription active.</p>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 45%;">Régime</th>
                <th style="width: 18%;">Total</th>
                <th style="width: 19%;">Confirmés</th>
                <th style="width: 18%;">En attente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sheet['breakdown'] as $row): ?>
                <tr>
                    <td>
                        <span class="swatch" style="background-color: <?= e((string) $row['color']) ?>;"></span>
                        <?= e((string) $row['label']) ?>
                    </td>
                    <td class="bold"><?= e((string) $row['total']) ?></td>
                    <td><?= e((string) $row['confirmed']) ?></td>
                    <td><?= e((string) $row['pending']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="small muted">
        La colonne « en attente » recense les participants dont le paiement n'est pas encore
        confirmé&nbsp;: à commander ou non selon la politique retenue.
    </p>
<?php endif; ?>

<h2>Allergies et intolérances</h2>

<?php if ($sheet['allergies'] === []): ?>
    <p class="muted">Aucune allergie déclarée.</p>
<?php else: ?>
    <div class="note warning">
        <strong>Attention :</strong> les repas des personnes ci-dessous doivent être préparés en
        tenant compte de contraintes individuelles. En cas de doute sur un ingrédient, contactez
        l'organisateur plutôt que de servir le plat standard.
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 28%;">Participant</th>
                <th style="width: 22%;">Régime</th>
                <th>Allergies et intolérances</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sheet['allergies'] as $row): ?>
                <tr>
                    <td class="bold"><?= e(RegistrationService::fullName($row)) ?></td>
                    <td><?= e(DietPreference::tryLabel((string) $row['diet'])) ?></td>
                    <td><?= e((string) $row['allergies_comment']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Précisions sur les régimes</h2>

<?php if ($sheet['diet_comments'] === []): ?>
    <p class="muted">Aucune précision particulière.</p>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 28%;">Participant</th>
                <th style="width: 22%;">Régime</th>
                <th>Précision</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sheet['diet_comments'] as $row): ?>
                <tr>
                    <td class="bold"><?= e(RegistrationService::fullName($row)) ?></td>
                    <td><?= e(DietPreference::tryLabel((string) $row['diet'])) ?></td>
                    <td><?= e((string) $row['diet_comment']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="footer-note">
    <strong>Confidentialité.</strong> Ce document contient des données personnelles, dont des
    informations relatives à la santé. Il est transmis au seul prestataire chargé de la
    restauration, pour la seule préparation des repas de cette formation, et doit être détruit à
    l'issue de celle-ci.
    <br>
    Contact organisateur&nbsp;: <?= e((string) setting('contact.email')) ?> —
    <?= e((string) setting('contact.phone')) ?>
</div>

</body>
</html>

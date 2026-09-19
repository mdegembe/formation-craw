<?php

declare(strict_types=1);

/**
 * Modele de diplome par defaut, utilise tant que l'administrateur n'a pas
 * fourni son propre gabarit.
 *
 * @var array<string, string> $values   Valeurs des variables de substitution
 * @var array<string, mixed>  $training
 */
$qr = $values['qr_verification'] ?? '';
?>
<div class="diploma-default">
    <div class="diploma-border"></div>
    <div class="diploma-border-inner"></div>
    <div class="diploma-accent"></div>

    <div class="diploma-inner">
        <div class="diploma-issuer">Centre wallon de Recherches agronomiques</div>
        <div class="diploma-issuer-sub">Rue de Liroux 9 — 5030 Gembloux — Belgique</div>

        <div class="diploma-title">Diplôme de participation</div>
        <div class="diploma-rule"></div>

        <div class="diploma-intro">Le présent diplôme est décerné à</div>
        <div class="diploma-name"><?= e($values['participant.nom_complet'] ?? '') ?></div>

        <div class="diploma-body">
            pour sa participation à la formation
            <div class="diploma-subject"><?= e($values['formation.sujet'] ?? '') ?></div>
            organisée <?= e($values['formation.dates'] ?? '') ?><br>
            au <?= e($values['formation.lieu'] ?? '') ?>,
            avec un taux de présence de <strong><?= e($values['presence.taux'] ?? '') ?></strong>.
        </div>
    </div>

    <table class="diploma-meta" style="width: 245mm; border-collapse: collapse;">
        <tr>
            <td style="width: 34%;">
                <div class="diploma-signature">
                    Fait à Gembloux,<br>
                    le <?= e($values['diplome.date_emission'] ?? '') ?>
                </div>
            </td>
            <td style="width: 32%; text-align: center;">
                <?php if ($qr !== ''): ?>
                    <img src="<?= $qr ?>" alt="" class="diploma-qr"><br>
                <?php endif; ?>
                Vérifier l'authenticité
            </td>
            <td style="width: 34%; text-align: right;">
                <div class="diploma-signature" style="margin-left: auto;">
                    Pour le CRA-W,<br>
                    la direction
                </div>
                <div style="margin-top: 2mm;">
                    Diplôme n° <strong><?= e($values['diplome.numero'] ?? '') ?></strong>
                </div>
            </td>
        </tr>
    </table>
</div>

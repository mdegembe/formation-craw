<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $links
 */
require_once base_path('templates/emails/_components.php');

$dates = sprintf(
    'du %s au %s',
    fr_date((string) $training['starts_on'], true),
    fr_date((string) $training['ends_on'], true)
);

ob_start();
?>
<p style="margin:0 0 16px;">Bonjour <?= e((string) $registration['first_name']) ?>,</p>

<?= email_note(
    '<strong>Nous avons bien reçu votre paiement.</strong> Votre inscription est désormais '
    . 'définitivement confirmée.',
    'success'
) ?>

<?= email_rows([
    'Référence' => (string) $registration['reference'],
    'Montant réglé' => (string) $payment['amount'],
    'Communication' => (string) $payment['communication'],
]) ?>

<h2 style="margin:28px 0 8px;font-size:17px;">Rendez-vous</h2>
<p style="margin:0 0 16px;">
    La formation se tient <strong><?= e($dates) ?></strong>, de 9h00 à 17h00, au
    <strong><?= e((string) $training['location_name']) ?></strong>,
    <?= e((string) $training['location_address']) ?>.
    L'accueil est ouvert dès 8h30 le premier jour.
</p>

<p style="margin:0 0 16px;">
    Les pauses et les repas de midi sont compris. Les contraintes alimentaires que vous nous avez
    signalées ont été transmises au traiteur.
</p>

<?= email_button('Voir mon inscription', (string) $links['space']) ?>

<p style="margin:16px 0 0;font-size:14px;color:#5c636a;">
    À l'issue de la formation, un diplôme de participation vous sera envoyé par courriel si votre
    taux de présence atteint le seuil requis.
</p>

<p style="margin:20px 0 0;">À bientôt,<br>
    <strong>L'équipe formation du CRA-W</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Paiement reçu — inscription confirmée',
    'emailBody' => $emailBody,
    'preheader' => 'Votre inscription ' . (string) $registration['reference'] . ' est confirmée.',
]);

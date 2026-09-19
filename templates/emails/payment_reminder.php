<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $payment
 * @var array<string, mixed> $links
 * @var bool $isFinal
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">Bonjour <?= e((string) $registration['first_name']) ?>,</p>

<?php if ($isFinal): ?>
    <?= email_note(
        '<strong>La date limite de paiement est dépassée.</strong> Sans règlement de votre part '
        . 'dans les prochains jours, nous serons contraints de libérer votre place au profit '
        . 'des personnes inscrites en liste d\'attente.',
        'danger'
    ) ?>
<?php else: ?>
    <p style="margin:0 0 16px;">
        Sauf erreur de notre part, nous n'avons pas encore reçu le paiement de votre inscription
        à la formation <strong><?= e((string) $training['title']) ?></strong>.
    </p>
    <?= email_note(
        'Si vous avez effectué le virement ces derniers jours, ce message peut se croiser avec '
        . 'son traitement : dans ce cas, merci de ne pas en tenir compte.'
    ) ?>
<?php endif; ?>

<p style="margin:0 0 12px;">
    Voici un rappel des coordonnées à utiliser&nbsp;:
</p>

<?= email_rows([
    'Bénéficiaire' => (string) $payment['beneficiary'],
    'IBAN' => (string) $payment['iban'],
    'BIC' => (string) $payment['bic'],
    'Montant' => (string) $payment['amount'],
    'Communication structurée' => (string) $payment['communication'],
], true) ?>

<?= email_button('Payer avec un QR code', (string) $links['payment']) ?>

<p style="margin:16px 0 0;font-size:14px;color:#5c636a;">
    Référence de votre inscription&nbsp;: <strong><?= e((string) $registration['reference']) ?></strong>.
    Une difficulté ou une question&nbsp;? Écrivez-nous à
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>, nous trouverons une solution.
</p>

<p style="margin:20px 0 0;">Bien à vous,<br>
    <strong>L'équipe formation du CRA-W</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => $isFinal ? 'Dernier rappel de paiement' : 'Rappel : paiement de votre inscription',
    'emailBody' => $emailBody,
    'preheader' => 'Montant dû : ' . (string) $payment['amount']
        . ' — communication ' . (string) $payment['communication'],
]);

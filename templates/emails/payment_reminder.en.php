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
<p style="margin:0 0 16px;">Hello <?= e((string) $registration['first_name']) ?>,</p>

<?php if ($isFinal): ?>
    <?= email_note(
        '<strong>The payment deadline has passed.</strong> Without payment from you in the '
        . 'coming days, we will be forced to release your place to people on the waiting '
        . 'list.',
        'danger'
    ) ?>
<?php else: ?>
    <p style="margin:0 0 16px;">
        Unless we are mistaken, we have not yet received payment for your registration
        for the training <strong><?= e((string) $training['title']) ?></strong>.
    </p>
    <?= email_note(
        'If you made the transfer in the last few days, this message may have crossed with '
        . 'its processing: if so, please disregard it.'
    ) ?>
<?php endif; ?>

<p style="margin:0 0 12px;">
    Here is a reminder of the details to use:
</p>

<?= email_rows([
    'Beneficiary' => (string) $payment['beneficiary'],
    'IBAN' => (string) $payment['iban'],
    'BIC' => (string) $payment['bic'],
    'Amount' => (string) $payment['amount'],
    'Structured reference' => (string) $payment['communication'],
], true) ?>

<?= email_button('Pay with a QR code', (string) $links['payment']) ?>

<p style="margin:16px 0 0;font-size:14px;color:#5c636a;">
    Your registration reference: <strong><?= e((string) $registration['reference']) ?></strong>.
    Any difficulty or question? Write to us at
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>, we will find a solution.
</p>

<p style="margin:20px 0 0;">Best regards,<br>
    <strong>The CRA-W training team</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => $isFinal ? 'Final payment reminder' : 'Reminder: payment for your registration',
    'emailBody' => $emailBody,
    'preheader' => 'Amount due: ' . (string) $payment['amount']
        . ' — reference ' . (string) $payment['communication'],
]);

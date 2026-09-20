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
    'from %s to %s',
    en_date((string) $training['starts_on'], true),
    en_date((string) $training['ends_on'], true)
);

ob_start();
?>
<p style="margin:0 0 16px;">Hello <?= e((string) $registration['first_name']) ?>,</p>

<?= email_note(
    '<strong>We have received your payment.</strong> Your registration is now '
    . 'definitively confirmed.',
    'success'
) ?>

<?= email_rows([
    'Reference' => (string) $registration['reference'],
    'Amount paid' => (string) $payment['amount'],
    'Reference used' => (string) $payment['communication'],
]) ?>

<h2 style="margin:28px 0 8px;font-size:17px;">See you there</h2>
<p style="margin:0 0 16px;">
    The training takes place <strong><?= e($dates) ?></strong>, from 9:00am to 5:00pm, at the
    <strong><?= e((string) $training['location_name']) ?></strong>,
    <?= e((string) $training['location_address']) ?>.
    Check-in is open from 8:30am on the first day.
</p>

<p style="margin:0 0 16px;">
    Breaks and lunches are included. The dietary requirements you reported have been passed on
    to the caterer.
</p>

<?= email_button('View my registration', (string) $links['space']) ?>

<p style="margin:16px 0 0;font-size:14px;color:#5c636a;">
    At the end of the training, a certificate of attendance will be sent to you by email if your
    attendance rate reaches the required threshold.
</p>

<p style="margin:20px 0 0;">See you soon,<br>
    <strong>The CRA-W training team</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Payment received — registration confirmed',
    'emailBody' => $emailBody,
    'preheader' => 'Your registration ' . (string) $registration['reference'] . ' is confirmed.',
]);

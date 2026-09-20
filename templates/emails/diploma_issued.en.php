<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $diploma
 * @var string $verificationUrl
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">Hello <?= e((string) $registration['first_name']) ?>,</p>

<p style="margin:0 0 16px;">
    You attended the training <strong><?= e((string) $training['title']) ?></strong>,
    held from <?= e(en_date((string) $training['starts_on'])) ?>
    to <?= e(en_date((string) $training['ends_on'])) ?>
    at <?= e((string) $training['location_name']) ?>.
</p>

<?= email_note(
    '<strong>Your certificate of attendance is attached to this message</strong>, as a PDF. '
    . 'We encourage you to keep it.',
    'success'
) ?>

<?= email_rows([
    'Certificate number' => (string) $diploma['number'],
    'Attendance rate' => \Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate']),
    'Date issued' => en_date((string) $diploma['generated_at']),
]) ?>

<p style="margin:0 0 16px;">
    The certificate carries a QR code and a verification code allowing any third party —
    employer, training body — to confirm its authenticity online.
</p>

<?= email_button('Certificate verification page', $verificationUrl) ?>

<p style="margin:16px 0 0;">
    Thank you for your participation, and we look forward to seeing you at a future session.
</p>

<p style="margin:20px 0 0;">Best regards,<br>
    <strong>The CRA-W training team</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Your certificate of attendance',
    'emailBody' => $emailBody,
    'preheader' => 'Certificate no. ' . (string) $diploma['number'] . ' — attached as PDF.',
]);

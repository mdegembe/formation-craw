<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var string|null $reason
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">Hello <?= e((string) $registration['first_name']) ?>,</p>

<p style="margin:0 0 16px;">
    We confirm the cancellation of your registration
    (reference <strong><?= e((string) $registration['reference']) ?></strong>)
    for the training <strong><?= e((string) $training['title']) ?></strong>.
</p>

<?php if ($reason !== null && $reason !== ''): ?>
    <?= email_note('<strong>Reason:</strong> ' . e($reason), 'warning') ?>
<?php endif; ?>

<p style="margin:0 0 16px;">
    If a payment had already been made, any refund will be handled according to the
    cancellation terms communicated at registration. Our team will get back to you if needed.
</p>

<p style="margin:0 0 16px;">
    Does this cancellation seem wrong to you? Write to us at
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>
    stating your reference, and we will correct it.
</p>

<p style="margin:20px 0 0;">Best regards,<br>
    <strong>The CRA-W training team</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Cancellation of your registration',
    'emailBody' => $emailBody,
    'preheader' => 'Registration ' . (string) $registration['reference'] . ' cancelled.',
]);

<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $payment
 * @var array<string, mixed> $links
 * @var bool $isWaitlist
 * @var bool $isFree
 */
require_once base_path('templates/emails/_components.php');

$name = trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);
$dates = sprintf(
    'from %s to %s',
    en_date((string) $training['starts_on']),
    en_date((string) $training['ends_on'])
);

ob_start();
?>
<p style="margin:0 0 16px;">Hello <?= e((string) $registration['first_name']) ?>,</p>

<?php if ($isWaitlist): ?>
    <p style="margin:0 0 16px;">
        We have received your registration request for the training
        <strong><?= e((string) $training['title']) ?></strong>, <?= e($dates) ?>.
    </p>

    <?= email_note(
        '<strong>The training is full.</strong> Your request has been placed on the '
        . 'waiting list: we will contact you first if a place becomes available. '
        . 'No payment is required at this stage.',
        'warning'
    ) ?>
<?php else: ?>
    <p style="margin:0 0 16px;">
        Your registration for the training <strong><?= e((string) $training['title']) ?></strong>
        has been recorded. It will take place <?= e($dates) ?> at the
        <strong><?= e((string) $training['location_name']) ?></strong>,
        <?= e((string) $training['location_address']) ?>.
    </p>
<?php endif; ?>

<?= email_rows([
    'Registration reference' => (string) $registration['reference'],
    'Participant' => $name,
    'Dates' => $dates,
]) ?>

<?php if (!$isWaitlist && !$isFree): ?>
    <h2 style="margin:28px 0 8px;font-size:17px;">Pay for your registration</h2>
    <p style="margin:0 0 12px;">
        Your registration will be <strong>definitively confirmed once your bank transfer is
        received</strong>. Please state the structured reference below: it allows your payment
        to be automatically matched.
    </p>

    <?= email_rows([
        'Beneficiary' => (string) $payment['beneficiary'],
        'IBAN' => (string) $payment['iban'],
        'BIC' => (string) $payment['bic'],
        'Amount' => (string) $payment['amount'],
        'Structured reference' => (string) $payment['communication'],
    ], true) ?>

    <?= email_button('Pay with a QR code', (string) $links['payment']) ?>

    <p style="margin:0 0 16px;font-size:14px;color:#5c636a;">
        On this page, a European EPC-format QR code pre-fills the transfer from your banking
        app: no need to copy the IBAN or the reference by hand.
    </p>
<?php elseif ($isFree): ?>
    <?= email_note(
        'Participation in this session is <strong>free</strong>: '
        . 'no payment will be requested from you. Your registration is confirmed.',
        'success'
    ) ?>
<?php endif; ?>

<h2 style="margin:28px 0 8px;font-size:17px;">Your personal area</h2>
<p style="margin:0 0 12px;">
    The link below gives you access to your registration, its status and — at the end of the
    training — your certificate. <strong>Keep this email</strong>: no password is needed, but this
    link is personal and must not be shared.
</p>

<?= email_button('Go to my registration', (string) $links['space']) ?>

<?php if ((int) ($registration['id'] ?? 0) > 0 && (bool) ($training['subject_pending'] ?? false)): ?>
    <?= email_note(
        'The <strong>subject and detailed programme</strong> of this session are being '
        . 'finalised. You will receive them by email as soon as they are confirmed.'
    ) ?>
<?php endif; ?>

<p style="margin:24px 0 0;">
    Any question? Simply reply to this message or write to us at
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>.
</p>

<p style="margin:16px 0 0;">Looking forward to welcoming you,<br>
    <strong>The CRA-W training team</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => $isWaitlist
        ? 'Your request has been placed on the waiting list'
        : 'Your registration has been recorded',
    'emailBody' => $emailBody,
    'preheader' => sprintf('Reference %s — %s', (string) $registration['reference'], $dates),
]);

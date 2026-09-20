<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Repository\PaymentRepository;
use Craw\Service\RegistrationService;

/**
 * Participant area, accessible via a signed link (no account) — English.
 *
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>      $training
 * @var array<string, mixed>|null $payment
 * @var array<string, mixed>|null $diploma
 */
View::extend('layouts/public');

$token = (string) $registration['token'];
$status = (string) $registration['status'];
$paymentStatus = (string) ($payment['status'] ?? 'unpaid');
$needsPayment = !in_array($paymentStatus, ['paid', 'waived'], true)
    && (int) $registration['amount_due_cents'] > 0
    && $status !== 'cancelled';

$statusBadge = match ($status) {
    'confirmed' => 'badge--success',
    'pending_payment' => 'badge--warning',
    'waitlist' => 'badge--info',
    'cancelled' => 'badge--danger',
    default => 'badge--neutral',
};
?>

<section class="section">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">My registration</p>
            <h1>Hello <?= e((string) $registration['first_name']) ?></h1>
            <p class="lead">
                Here is the status of your registration for the training
                “<?= e((string) $training['title']) ?>”.
            </p>
        </div>

        <div class="grid grid--3 mb-3">
            <div class="stat stat--accent">
                <span class="stat__label">Reference</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e((string) $registration['reference']) ?>
                </span>
                <span class="stat__hint">
                    Registered on <?= e(en_date((string) $registration['created_at'])) ?>
                </span>
            </div>
            <div class="stat">
                <span class="stat__label">Registration status</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <span class="badge <?= e($statusBadge) ?>">
                        <?= e(RegistrationService::statusLabel($status)) ?>
                    </span>
                </span>
            </div>
            <div class="stat">
                <span class="stat__label">Payment</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e(PaymentRepository::statusLabel($paymentStatus)) ?>
                </span>
                <span class="stat__hint">
                    <?= e(money_en((int) $registration['amount_due_cents'], (string) $training['currency'])) ?>
                </span>
            </div>
        </div>

        <?php if ($status === 'waitlist'): ?>
            <div class="alert alert--info" role="status">
                <div class="alert__body">
                    <strong>You are on the waiting list.</strong>
                    The training is full. We will contact you by email as soon as a place becomes
                    available — no payment is required at this stage.
                </div>
            </div>
        <?php elseif ($status === 'cancelled'): ?>
            <div class="alert alert--warning" role="status">
                <div class="alert__body">
                    <strong>This registration has been cancelled.</strong>
                    For any question, <a href="<?= e(url('/contact')) ?>">contact us</a>.
                </div>
            </div>
        <?php elseif ($needsPayment): ?>
            <div class="alert alert--warning" role="status">
                <div class="alert__body">
                    <strong>Your payment is pending.</strong>
                    <?php if (!empty($payment['due_date'])): ?>
                        Due date: <?= e(en_date((string) $payment['due_date'])) ?>.
                    <?php endif; ?>
                    <a href="<?= e(url('/inscription/paiement/' . $token)) ?>">
                        View payment instructions
                    </a>.
                </div>
            </div>
        <?php elseif ($status === 'confirmed'): ?>
            <div class="alert alert--success" role="status">
                <div class="alert__body">
                    <strong>Your registration is confirmed.</strong>
                    We look forward to seeing you on
                    <?= e(en_date((string) $training['starts_on'], true)) ?>
                    at <?= e((string) $training['location_name']) ?>.
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid--2">
            <div class="panel">
                <h2 style="font-size: 1.125rem;">Your information</h2>
                <dl class="definition-list mt-3">
                    <dt>Name</dt>
                    <dd><?= e(RegistrationService::fullName($registration)) ?></dd>
                    <dt>Email</dt>
                    <dd><?= e((string) $registration['email']) ?></dd>
                    <dt>Phone</dt>
                    <dd><?= e((string) ($registration['phone'] ?? '—')) ?></dd>
                    <?php if (!empty($registration['organisation'])): ?>
                        <dt>Organisation</dt>
                        <dd><?= e((string) $registration['organisation']) ?></dd>
                    <?php endif; ?>
                    <dt>Diet</dt>
                    <dd>
                        <?= e($dietLabel) ?>
                        <?php if ((int) $registration['has_allergies'] === 1): ?>
                            <br><span class="badge badge--warning">Allergies declared</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                <p class="small text-muted mt-3 mb-0">
                    Need to correct something?
                    <a href="<?= e(url('/contact')) ?>">Write to us</a> stating your reference.
                </p>
            </div>

            <div class="panel">
                <h2 style="font-size: 1.125rem;">The training</h2>
                <dl class="definition-list mt-3">
                    <dt>Dates</dt>
                    <dd>
                        From <?= e(en_date((string) $training['starts_on'], true)) ?><br>
                        to <?= e(en_date((string) $training['ends_on'], true)) ?>
                    </dd>
                    <dt>Venue</dt>
                    <dd>
                        <?= e((string) $training['location_name']) ?><br>
                        <span class="text-muted"><?= e((string) $training['location_address']) ?></span>
                    </dd>
                    <dt>Hours</dt>
                    <dd>9:00am – 5:00pm (check-in from 8:30am on the first day)</dd>
                </dl>

                <div class="btn-group mt-3">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/programme')) ?>">Programme</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/infos-pratiques')) ?>">Practical information</a>
                </div>
            </div>
        </div>

        <?php if ($diploma !== null): ?>
            <div class="panel panel--accent mt-3">
                <h2 style="font-size: 1.125rem;">Your certificate of attendance</h2>
                <p class="text-muted">
                    Certificate no. <strong><?= e((string) $diploma['number']) ?></strong>,
                    issued on <?= e(en_date((string) $diploma['generated_at'])) ?>.
                </p>
                <div class="btn-group">
                    <a class="btn btn--primary btn--pill"
                       href="<?= e(url('/mon-inscription/' . $token . '/diplome')) ?>">
                        Download my certificate (PDF)
                    </a>
                    <a class="btn btn--ghost btn--pill"
                       href="<?= e(url('/diplome/verification/' . (string) $diploma['verification_code'])) ?>">
                        Verification page
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($needsPayment): ?>
            <p class="mt-4">
                <a class="btn btn--primary btn--pill btn--lg"
                   href="<?= e(url('/inscription/paiement/' . $token)) ?>">
                    Pay for my registration
                </a>
            </p>
        <?php endif; ?>

        <p class="small text-muted mt-4">
            This page is only accessible via the personal link received by email.
            Do not share it: it gives access to your registration information.
        </p>
    </div>
</section>

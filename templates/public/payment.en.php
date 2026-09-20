<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Repository\PaymentRepository;

/**
 * Bank transfer payment page: details, structured reference and SEPA QR
 * code (English).
 *
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>      $training
 * @var array<string, mixed>|null $payment
 */
View::extend('layouts/public');

$token = (string) $registration['token'];
$status = (string) ($payment['status'] ?? 'unpaid');
$isPaid = in_array($status, ['paid', 'waived'], true);
$amount = money_en($amountCents, (string) $training['currency']);
?>

<section class="section">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">Payment</p>
            <h1>Pay for your registration</h1>
            <p class="lead">
                Registration <strong><?= e((string) $registration['reference']) ?></strong> —
                <?= e(trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name'])) ?>
            </p>
        </div>

        <?php if ($isPaid): ?>
            <div class="alert alert--success" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.73.73 0 0 1-1.065.02L3.217 8.384a.75.75 0 0 1 0-1.06.73.73 0 0 1 1.04 0l3.052 3.093 5.4-6.425z"/>
                </svg>
                <div class="alert__body">
                    <strong>Your payment has been recorded.</strong>
                    Your registration is definitively confirmed. No action is required on your part.
                </div>
            </div>

            <p><a class="btn btn--primary btn--pill" href="<?= e(url('/mon-inscription/' . $token)) ?>">
                View my registration
            </a></p>
        <?php else: ?>
            <div class="alert alert--info" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                </svg>
                <div class="alert__body">
                    Your registration is recorded. It will be <strong>definitively confirmed</strong>
                    once your bank transfer is received.
                    <?php if (!empty($payment['due_date'])): ?>
                        Please make the payment by
                        <strong><?= e(en_date((string) $payment['due_date'])) ?></strong> at the latest.
                    <?php endif; ?>
                    <?php if ($status === 'partial'): ?>
                        <br><strong>A partial payment has been received</strong>
                        (<?= e(money_en((int) ($payment['received_cents'] ?? 0))) ?>):
                        the balance remains due.
                    <?php endif; ?>
                </div>
            </div>

            <div class="payment-panel">
                <div>
                    <h2 style="font-size: 1.25rem;">Bank transfer details</h2>
                    <p class="text-muted">
                        Make the transfer from your bank, copying the structured reference
                        <strong>exactly</strong>: it is what allows your payment to be automatically
                        matched to your registration.
                    </p>

                    <dl class="payment-rows">
                        <div class="payment-row">
                            <div>
                                <dt>Beneficiary</dt>
                                <dd><?= e($beneficiary) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($beneficiary) ?>">
                                <span data-copy-label>Copy</span>
                                <span class="visually-hidden"> the beneficiary name</span>
                            </button>
                        </div>

                        <div class="payment-row">
                            <div>
                                <dt>IBAN</dt>
                                <dd><?= e($iban) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($ibanRaw) ?>">
                                <span data-copy-label>Copy</span>
                                <span class="visually-hidden"> the IBAN</span>
                            </button>
                        </div>

                        <?php if ($bic !== ''): ?>
                            <div class="payment-row">
                                <div>
                                    <dt>BIC</dt>
                                    <dd><?= e($bic) ?></dd>
                                </div>
                                <button type="button" class="copy-btn" data-copy="<?= e($bic) ?>">
                                    <span data-copy-label>Copy</span>
                                    <span class="visually-hidden"> the BIC</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="payment-row">
                            <div>
                                <dt>Amount</dt>
                                <dd><?= e($amount) ?></dd>
                            </div>
                            <button type="button" class="copy-btn"
                                    data-copy="<?= e(number_format($amountCents / 100, 2, '.', '')) ?>">
                                <span data-copy-label>Copy</span>
                                <span class="visually-hidden"> the amount</span>
                            </button>
                        </div>

                        <div class="payment-row" style="border-color: var(--craw-accent);">
                            <div>
                                <dt>Structured reference</dt>
                                <dd><?= e($communication) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($communication) ?>">
                                <span data-copy-label>Copy</span>
                                <span class="visually-hidden"> the structured reference</span>
                            </button>
                        </div>
                    </dl>

                    <div class="btn-group mt-3">
                        <a class="btn btn--outline btn--pill"
                           href="<?= e(url('/inscription/paiement/' . $token . '/instructions.pdf')) ?>">
                            Download the instructions (PDF)
                        </a>
                        <a class="btn btn--ghost btn--pill" href="<?= e(url('/mon-inscription/' . $token)) ?>">
                            My registration
                        </a>
                    </div>
                </div>

                <?php if ($qrAvailable): ?>
                    <aside class="qr-panel">
                        <h2 style="font-size: 1.125rem;">Pay with one scan</h2>
                        <p class="small text-muted">
                            Scan this QR code with your banking app:
                            the transfer is pre-filled.
                        </p>
<?php /* Priority loading: the QR code is the central element of this page. */ ?>
                        <img src="<?= e($qrUrl) ?>" width="420" height="420"
                             fetchpriority="high" decoding="async"
                             alt="SEPA transfer QR code for registration <?= e((string) $registration['reference']) ?>">
                        <p class="small text-muted mt-2 mb-0">
                            Standard European EPC069-12 format, recognised by most Belgian and
                            European banking apps.
                        </p>
                    </aside>
                <?php endif; ?>
            </div>

            <div class="panel mt-4">
                <h2 style="font-size: 1.125rem;">What happens next?</h2>
                <ol class="mt-2" style="padding-left: 1.25rem;">
                    <li>You make the transfer from your bank.</li>
                    <li>CRA-W verifies receipt of the payment (allow 2 to 4 business days).</li>
                    <li>You receive an email definitively confirming your registration.</li>
                    <li>An automatic reminder is sent to you if payment has not arrived by the deadline.</li>
                </ol>
                <p class="small text-muted mb-0">
                    Keep the link received by email: it lets you come back to this page at any
                    time, with no password.
                </p>
            </div>
        <?php endif; ?>

        <p class="small text-muted mt-4">
            Payment status:
            <strong><?= e(PaymentRepository::statusLabel($status)) ?></strong>
        </p>
    </div>
</section>

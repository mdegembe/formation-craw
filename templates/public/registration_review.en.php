<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\Settings;
use Craw\Core\View;

/**
 * Step 3: review and confirmation (English).
 *
 * @var array<string, mixed>  $registration
 * @var array<string, mixed>  $training
 * @var string                $dietLabel
 * @var array<string, string> $errors
 */
View::extend('layouts/public');

$price = (int) $training['price_cents'];
$deadlineDays = (int) Settings::get('payment.deadline_days', 15);
$token = (string) $registration['token'];
?>

<section class="section">
    <div class="container container--narrow">
        <div class="section__header">
            <p class="section__eyebrow">Registration</p>
            <h1>Check your information</h1>
            <p class="lead">
                Review the summary below, then confirm your registration.
                Nothing is finalised until you confirm.
            </p>
        </div>

        <?= View::partial('partials/steps', ['step' => $step]) ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert">
                <div class="alert__body">
                    <?= e((string) reset($errors)) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel mb-3">
            <div class="cluster" style="justify-content: space-between;">
                <h2 style="font-size: 1.125rem;">Identity and contact</h2>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/inscription')) ?>">Edit</a>
            </div>

            <dl class="definition-list mt-3">
                <dt>Full name</dt>
                <dd>
                    <?= e(trim((string) ($registration['civility'] ?? '') . ' '
                        . (string) $registration['first_name'] . ' '
                        . (string) $registration['last_name'])) ?>
                </dd>

                <dt>Email address</dt>
                <dd><?= e((string) $registration['email']) ?></dd>

                <dt>Phone number</dt>
                <dd><?= e((string) ($registration['phone'] ?? '—')) ?></dd>

                <?php if (!empty($registration['organisation'])): ?>
                    <dt>Organisation</dt>
                    <dd>
                        <?= e((string) $registration['organisation']) ?>
                        <?php if (!empty($registration['job_title'])): ?>
                            <span class="text-muted">— <?= e((string) $registration['job_title']) ?></span>
                        <?php endif; ?>
                    </dd>
                <?php endif; ?>

                <?php if ((int) $registration['needs_invoice'] === 1): ?>
                    <dt>Billing address</dt>
                    <dd>
                        <?= e((string) ($registration['street'] ?? '')) ?><br>
                        <?= e((string) ($registration['postal_code'] ?? '')) ?>
                        <?= e((string) ($registration['city'] ?? '')) ?>,
                        <?= e((string) ($registration['country'] ?? '')) ?>
                        <?php if (!empty($registration['vat_number'])): ?>
                            <br>VAT: <?= e((string) $registration['vat_number']) ?>
                        <?php endif; ?>
                    </dd>
                <?php endif; ?>
            </dl>
        </div>

        <div class="panel mb-3">
            <div class="cluster" style="justify-content: space-between;">
                <h2 style="font-size: 1.125rem;">Meals</h2>
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('/inscription/preferences/' . $token)) ?>">Edit</a>
            </div>

            <dl class="definition-list mt-3">
                <dt>Diet</dt>
                <dd>
                    <?= e($dietLabel) ?>
                    <?php if (!empty($registration['diet_comment'])): ?>
                        <br><span class="text-muted small"><?= e((string) $registration['diet_comment']) ?></span>
                    <?php endif; ?>
                </dd>

                <dt>Allergies</dt>
                <dd>
                    <?php if ((int) $registration['has_allergies'] === 1): ?>
                        <span class="badge badge--warning">Declared</span>
                        <br><span class="text-muted small"><?= e((string) $registration['allergies_comment']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">None declared</span>
                    <?php endif; ?>
                </dd>

                <?php if (!empty($registration['logistics_comment'])): ?>
                    <dt>Remarks</dt>
                    <dd class="text-muted"><?= e((string) $registration['logistics_comment']) ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <div class="panel panel--accent mb-3">
            <h2 style="font-size: 1.125rem;">The training</h2>
            <dl class="definition-list mt-3">
                <dt>Dates</dt>
                <dd>
                    From <?= e(en_date((string) $training['starts_on'], true)) ?>
                    to <?= e(en_date((string) $training['ends_on'], true)) ?>
                </dd>
                <dt>Venue</dt>
                <dd>
                    <?= e((string) $training['location_name']) ?><br>
                    <span class="text-muted"><?= e((string) $training['location_address']) ?></span>
                </dd>
                <dt>Amount</dt>
                <dd class="fw-600">
                    <?php if ($price <= 0): ?>
                        Free
                    <?php else: ?>
                        <?= e(money_en($price, (string) $training['currency'])) ?>
                        <span class="text-muted small">— to be paid by bank transfer within <?= e((string) $deadlineDays) ?> days</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>

        <form method="post" action="<?= e(url('/inscription/confirmer/' . $token)) ?>" data-once>
            <?= Csrf::field() ?>

            <fieldset>
                <legend>Consent</legend>

                <div class="stack">
                    <div class="form-check">
                        <input type="checkbox" id="consent_gdpr" name="consent_gdpr" value="1" required>
                        <label for="consent_gdpr">
                            I agree that CRA-W processes my personal data for the purpose of
                            organising this training, in accordance with the
                            <a href="<?= e(url('/confidentialite')) ?>">privacy notice</a>.
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                    </div>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_gdpr']) ?>

                    <div class="form-check">
                        <input type="checkbox" id="consent_terms" name="consent_terms" value="1" required>
                        <label for="consent_terms">
                            I have read the
                            <a href="<?= e(url('/infos-pratiques')) ?>">participation and cancellation terms</a>
                            and I undertake to pay the amount due within the stated deadline.
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                    </div>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_terms']) ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--pill btn--lg">
                    Confirm my registration
                </button>
                <a class="btn btn--ghost btn--pill"
                   href="<?= e(url('/inscription/preferences/' . $token)) ?>">Back</a>
                <p class="small text-muted mb-0">Step 3 of 3</p>
            </div>
        </form>
    </div>
</section>

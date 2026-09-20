<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * Step 1: identity and contact details (English).
 *
 * @var array<string, mixed>  $training
 * @var array<int, string>    $civilities
 * @var array<string, string> $errors
 * @var array<string, mixed>  $old
 */
View::extend('layouts/public');

$value = static fn (string $key, string $default = ''): string => (string) ($old[$key] ?? $default);
$invalid = static fn (string $key): string => isset($errors[$key])
    ? ' aria-invalid="true" aria-describedby="' . $key . '-error"'
    : '';
?>

<section class="section">
    <div class="container container--narrow">
        <div class="section__header">
            <p class="section__eyebrow">Registration</p>
            <h1>Register for the training</h1>
            <p class="lead">
                From <?= e(en_date((string) $training['starts_on'])) ?>
                to <?= e(en_date((string) $training['ends_on'])) ?>,
                at <?= e((string) $training['location_name']) ?>.
                <strong>No account creation is required.</strong>
            </p>
        </div>

        <?= View::partial('partials/steps', ['step' => $step]) ?>

        <?php if (!$isOpen): ?>
            <div class="alert alert--warning" role="alert">
                <div class="alert__body">
                    <strong>Registration is closed.</strong>
                    The registration deadline has passed or the training has already started.
                    <a href="<?= e(url('/contact')) ?>">Contact us</a> if you would like to be
                    informed of upcoming sessions.
                </div>
            </div>
        <?php elseif ($remainingSeats <= 0): ?>
            <div class="alert alert--warning" role="alert">
                <div class="alert__body">
                    <strong>The training is full.</strong>
                    You can still fill in this form to join the waiting list:
                    you will be contacted first if a place becomes available.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert" tabindex="-1" id="form-errors">
                <div class="alert__body">
                    <strong>The form has <?= e((string) count($errors)) ?> error(s).</strong>
                    Please check the fields flagged below.
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/inscription')) ?>" data-once novalidate>
            <?= Csrf::field() ?>

            <!-- Honeypot: left empty by a human. -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Do not fill in this field</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <fieldset>
                <legend>Your identity</legend>

                <div class="form-grid form-grid--2">
                    <div class="form-field">
                        <label class="form-label" for="civility">Title</label>
                        <select class="form-select" id="civility" name="civility">
                            <option value="">— Not specified —</option>
                            <?php foreach ($civilities as $civility): ?>
                                <option value="<?= e($civility) ?>"
                                    <?= $value('civility') === $civility ? 'selected' : '' ?>>
                                    <?= e($civility) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="birth_date">
                            Date of birth
                        </label>
                        <input class="form-control" type="date" id="birth_date" name="birth_date"
                               value="<?= e($value('birth_date')) ?>"<?= $invalid('birth_date') ?>>
                        <p class="form-hint">Optional — used to distinguish same-name participants on the certificate.</p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'birth_date']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="first_name">
                            First name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="first_name" name="first_name"
                               value="<?= e($value('first_name')) ?>" required maxlength="100"
                               autocomplete="given-name"<?= $invalid('first_name') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'first_name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="last_name">
                            Last name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="last_name" name="last_name"
                               value="<?= e($value('last_name')) ?>" required maxlength="100"
                               autocomplete="family-name"<?= $invalid('last_name') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'last_name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="email">
                            Email address <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= e($value('email')) ?>" required maxlength="190"
                               autocomplete="email"<?= $invalid('email') ?>>
                        <p class="form-hint">
                            The confirmation, payment instructions and certificate are sent to this
                            address.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="phone">
                            Phone number <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="tel" id="phone" name="phone"
                               value="<?= e($value('phone')) ?>" required maxlength="30"
                               autocomplete="tel"<?= $invalid('phone') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'phone']) ?>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Your organisation</legend>

                <div class="form-grid form-grid--2">
                    <div class="form-field">
                        <label class="form-label" for="organisation">Organisation or company</label>
                        <input class="form-control" type="text" id="organisation" name="organisation"
                               value="<?= e($value('organisation')) ?>" maxlength="150"
                               autocomplete="organization"<?= $invalid('organisation') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'organisation']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="job_title">Job title</label>
                        <input class="form-control" type="text" id="job_title" name="job_title"
                               value="<?= e($value('job_title')) ?>" maxlength="150"
                               autocomplete="organization-title"<?= $invalid('job_title') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'job_title']) ?>
                    </div>
                </div>

                <div class="form-check mt-2">
                    <input type="checkbox" id="needs_invoice" name="needs_invoice" value="1"
                        <?= $value('needs_invoice') === '1' ? 'checked' : '' ?>>
                    <label for="needs_invoice">
                        I would like an invoice made out to my organisation
                    </label>
                </div>

                <div id="invoice-block" class="mt-3" hidden>
                    <div class="form-grid form-grid--2">
                        <div class="form-field form-field--full">
                            <label class="form-label" for="street">Street and number</label>
                            <input class="form-control" type="text" id="street" name="street"
                                   value="<?= e($value('street')) ?>" maxlength="190"
                                   autocomplete="street-address" data-conditional-required="true"<?= $invalid('street') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'street']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="postal_code">Postal code</label>
                            <input class="form-control" type="text" id="postal_code" name="postal_code"
                                   value="<?= e($value('postal_code')) ?>" maxlength="20"
                                   autocomplete="postal-code" data-conditional-required="true"<?= $invalid('postal_code') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'postal_code']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="city">City</label>
                            <input class="form-control" type="text" id="city" name="city"
                                   value="<?= e($value('city')) ?>" maxlength="120"
                                   autocomplete="address-level2" data-conditional-required="true"<?= $invalid('city') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'city']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="country">Country</label>
                            <input class="form-control" type="text" id="country" name="country"
                                   value="<?= e($value('country', 'Belgium')) ?>" maxlength="80"
                                   autocomplete="country-name">
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="vat_number">VAT number</label>
                            <input class="form-control" type="text" id="vat_number" name="vat_number"
                                   value="<?= e($value('vat_number')) ?>" maxlength="20"
                                   placeholder="BE0123456789"<?= $invalid('vat_number') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'vat_number']) ?>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--pill btn--lg">
                    Continue
                </button>
                <p class="small text-muted mb-0">
                    Step 1 of 3 — your data is processed in accordance with our
                    <a href="<?= e(url('/confidentialite')) ?>">privacy notice</a>.
                </p>
            </div>
        </form>
    </div>
</section>

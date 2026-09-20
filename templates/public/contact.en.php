<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * @var array<string, mixed>|null $training
 * @var array<string, string>     $errors
 * @var array<string, mixed>      $old
 */
View::extend('layouts/public');

$value = static fn (string $key): string => (string) ($old[$key] ?? '');
?>

<section class="section">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">Contact</p>
            <h1>Get in touch</h1>
            <p class="lead">
                A question about the programme, registration or organisation?
                Write to us, we will reply as soon as possible.
            </p>
        </div>

        <div class="grid grid--2">
            <form method="post" action="<?= e(url('/contact')) ?>" data-once novalidate>
                <?= Csrf::field() ?>

                <div class="hp-field" aria-hidden="true">
                    <label for="website">Do not fill in this field</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label" for="name">
                            Your name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="name" name="name"
                               value="<?= e($value('name')) ?>" required maxlength="150" autocomplete="name"
                            <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="email">
                            Your email address <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= e($value('email')) ?>" required maxlength="190" autocomplete="email"
                            <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="subject">
                            Subject <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="subject" name="subject"
                               value="<?= e($value('subject')) ?>" required maxlength="190"
                            <?= isset($errors['subject']) ? 'aria-invalid="true" aria-describedby="subject-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'subject']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="message">
                            Your message <span class="required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="7"
                                  required maxlength="3000"
                            <?= isset($errors['message']) ? 'aria-invalid="true" aria-describedby="message-error"' : '' ?>
                        ><?= e($value('message')) ?></textarea>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'message']) ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary btn--pill btn--lg">Send</button>
                    <p class="small text-muted mb-0">
                        Your data is only used to process your request.
                    </p>
                </div>
            </form>

            <aside>
                <div class="panel panel--accent">
                    <h2 style="font-size: 1.125rem;">Contact details</h2>
                    <dl class="definition-list mt-3" style="grid-template-columns: 1fr;">
                        <?php $contactEmail = (string) setting('contact.email'); ?>
                        <?php if ($contactEmail !== ''): ?>
                            <dt>By email</dt>
                            <dd><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></dd>
                        <?php endif; ?>

                        <?php $contactPhone = (string) setting('contact.phone'); ?>
                        <?php if ($contactPhone !== ''): ?>
                            <dt>By phone</dt>
                            <dd>
                                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contactPhone) ?? '') ?>">
                                    <?= e($contactPhone) ?>
                                </a><br>
                                <span class="text-muted small">Monday to Friday, 9am to 4pm</span>
                            </dd>
                        <?php endif; ?>

                        <?php if ($training !== null): ?>
                            <dt>On site</dt>
                            <dd>
                                <?= e((string) $training['location_name']) ?><br>
                                <?= e((string) $training['location_address']) ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="panel mt-3">
                    <h2 style="font-size: 1.125rem;">Before you write</h2>
                    <p class="text-muted mb-2">
                        Most questions are answered in our practical information:
                        price, access, meals, cancellation terms, certificate.
                    </p>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/infos-pratiques')) ?>">
                        View the FAQ
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

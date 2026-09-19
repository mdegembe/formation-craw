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
            <h1>Nous écrire</h1>
            <p class="lead">
                Une question sur le programme, l'inscription ou l'organisation&nbsp;?
                Écrivez-nous, nous vous répondons dans les meilleurs délais.
            </p>
        </div>

        <div class="grid grid--2">
            <form method="post" action="<?= e(url('/contact')) ?>" data-once novalidate>
                <?= Csrf::field() ?>

                <div class="hp-field" aria-hidden="true">
                    <label for="website">Ne pas remplir ce champ</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label" for="name">
                            Votre nom <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="name" name="name"
                               value="<?= e($value('name')) ?>" required maxlength="150" autocomplete="name"
                            <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="email">
                            Votre adresse e-mail <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= e($value('email')) ?>" required maxlength="190" autocomplete="email"
                            <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="subject">
                            Objet <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="subject" name="subject"
                               value="<?= e($value('subject')) ?>" required maxlength="190"
                            <?= isset($errors['subject']) ? 'aria-invalid="true" aria-describedby="subject-error"' : '' ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'subject']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="message">
                            Votre message <span class="required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="7"
                                  required maxlength="3000"
                            <?= isset($errors['message']) ? 'aria-invalid="true" aria-describedby="message-error"' : '' ?>
                        ><?= e($value('message')) ?></textarea>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'message']) ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--primary btn--pill btn--lg">Envoyer</button>
                    <p class="small text-muted mb-0">
                        Vos données ne servent qu'à traiter votre demande.
                    </p>
                </div>
            </form>

            <aside>
                <div class="panel panel--accent">
                    <h2 style="font-size: 1.125rem;">Coordonnées</h2>
                    <dl class="definition-list mt-3" style="grid-template-columns: 1fr;">
                        <?php $contactEmail = (string) setting('contact.email'); ?>
                        <?php if ($contactEmail !== ''): ?>
                            <dt>Par courriel</dt>
                            <dd><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a></dd>
                        <?php endif; ?>

                        <?php $contactPhone = (string) setting('contact.phone'); ?>
                        <?php if ($contactPhone !== ''): ?>
                            <dt>Par téléphone</dt>
                            <dd>
                                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contactPhone) ?? '') ?>">
                                    <?= e($contactPhone) ?>
                                </a><br>
                                <span class="text-muted small">Du lundi au vendredi, de 9h à 16h</span>
                            </dd>
                        <?php endif; ?>

                        <?php if ($training !== null): ?>
                            <dt>Sur place</dt>
                            <dd>
                                <?= e((string) $training['location_name']) ?><br>
                                <?= e((string) $training['location_address']) ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="panel mt-3">
                    <h2 style="font-size: 1.125rem;">Avant d'écrire</h2>
                    <p class="text-muted mb-2">
                        La plupart des questions trouvent leur réponse dans nos informations pratiques&nbsp;:
                        tarif, accès, repas, conditions d'annulation, diplôme.
                    </p>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/infos-pratiques')) ?>">
                        Consulter la FAQ
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

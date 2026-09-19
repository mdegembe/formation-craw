<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;

/**
 * Etape 1 : identite et coordonnees.
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
            <p class="section__eyebrow">Inscription</p>
            <h1>S'inscrire à la formation</h1>
            <p class="lead">
                Du <?= e(fr_date((string) $training['starts_on'])) ?>
                au <?= e(fr_date((string) $training['ends_on'])) ?>,
                au <?= e((string) $training['location_name']) ?>.
                <strong>Aucune création de compte n'est nécessaire.</strong>
            </p>
        </div>

        <?= View::partial('partials/steps', ['step' => $step]) ?>

        <?php if (!$isOpen): ?>
            <div class="alert alert--warning" role="alert">
                <div class="alert__body">
                    <strong>Les inscriptions sont closes.</strong>
                    La date limite d'inscription est dépassée ou la formation a déjà commencé.
                    <a href="<?= e(url('/contact')) ?>">Contactez-nous</a> si vous souhaitez être
                    informé des prochaines sessions.
                </div>
            </div>
        <?php elseif ($remainingSeats <= 0): ?>
            <div class="alert alert--warning" role="alert">
                <div class="alert__body">
                    <strong>La formation est complète.</strong>
                    Vous pouvez néanmoins remplir ce formulaire pour rejoindre la liste d'attente&nbsp;:
                    vous serez contacté en priorité si une place se libère.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert" tabindex="-1" id="form-errors">
                <div class="alert__body">
                    <strong>Le formulaire comporte <?= e((string) count($errors)) ?> erreur(s).</strong>
                    Merci de vérifier les champs signalés ci-dessous.
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/inscription')) ?>" data-once novalidate>
            <?= Csrf::field() ?>

            <!-- Piège à robots : laissé vide par un humain. -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Ne pas remplir ce champ</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <fieldset>
                <legend>Votre identité</legend>

                <div class="form-grid form-grid--2">
                    <div class="form-field">
                        <label class="form-label" for="civility">Civilité</label>
                        <select class="form-select" id="civility" name="civility">
                            <option value="">— Non précisé —</option>
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
                            Date de naissance
                        </label>
                        <input class="form-control" type="date" id="birth_date" name="birth_date"
                               value="<?= e($value('birth_date')) ?>"<?= $invalid('birth_date') ?>>
                        <p class="form-hint">Facultatif — sert à distinguer les homonymes sur le diplôme.</p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'birth_date']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="first_name">
                            Prénom <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="first_name" name="first_name"
                               value="<?= e($value('first_name')) ?>" required maxlength="100"
                               autocomplete="given-name"<?= $invalid('first_name') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'first_name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="last_name">
                            Nom <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="text" id="last_name" name="last_name"
                               value="<?= e($value('last_name')) ?>" required maxlength="100"
                               autocomplete="family-name"<?= $invalid('last_name') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'last_name']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="email">
                            Adresse e-mail <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="email" id="email" name="email"
                               value="<?= e($value('email')) ?>" required maxlength="190"
                               autocomplete="email"<?= $invalid('email') ?>>
                        <p class="form-hint">
                            C'est à cette adresse que sont envoyés la confirmation, les instructions de
                            paiement et le diplôme.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'email']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="phone">
                            Téléphone <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input class="form-control" type="tel" id="phone" name="phone"
                               value="<?= e($value('phone')) ?>" required maxlength="30"
                               autocomplete="tel"<?= $invalid('phone') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'phone']) ?>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Votre organisation</legend>

                <div class="form-grid form-grid--2">
                    <div class="form-field">
                        <label class="form-label" for="organisation">Organisation ou entreprise</label>
                        <input class="form-control" type="text" id="organisation" name="organisation"
                               value="<?= e($value('organisation')) ?>" maxlength="150"
                               autocomplete="organization"<?= $invalid('organisation') ?>>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'organisation']) ?>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="job_title">Fonction</label>
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
                        Je souhaite recevoir une facture au nom de mon organisation
                    </label>
                </div>

                <div id="invoice-block" class="mt-3" hidden>
                    <div class="form-grid form-grid--2">
                        <div class="form-field form-field--full">
                            <label class="form-label" for="street">Rue et numéro</label>
                            <input class="form-control" type="text" id="street" name="street"
                                   value="<?= e($value('street')) ?>" maxlength="190"
                                   autocomplete="street-address" data-conditional-required="true"<?= $invalid('street') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'street']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="postal_code">Code postal</label>
                            <input class="form-control" type="text" id="postal_code" name="postal_code"
                                   value="<?= e($value('postal_code')) ?>" maxlength="20"
                                   autocomplete="postal-code" data-conditional-required="true"<?= $invalid('postal_code') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'postal_code']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="city">Localité</label>
                            <input class="form-control" type="text" id="city" name="city"
                                   value="<?= e($value('city')) ?>" maxlength="120"
                                   autocomplete="address-level2" data-conditional-required="true"<?= $invalid('city') ?>>
                            <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'city']) ?>
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="country">Pays</label>
                            <input class="form-control" type="text" id="country" name="country"
                                   value="<?= e($value('country', 'Belgique')) ?>" maxlength="80"
                                   autocomplete="country-name">
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="vat_number">Numéro de TVA</label>
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
                    Continuer
                </button>
                <p class="small text-muted mb-0">
                    Étape 1 sur 3 — vos données sont traitées conformément à notre
                    <a href="<?= e(url('/confidentialite')) ?>">déclaration de confidentialité</a>.
                </p>
            </div>
        </form>
    </div>
</section>

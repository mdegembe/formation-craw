<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Domain\DietPreference;

/**
 * Etape 2 : preferences alimentaires.
 *
 * Les allergies relevent des donnees de sante (RGPD art. 9) : elles font
 * l'objet d'un consentement explicite et distinct, avec mention de la finalite.
 *
 * @var array<string, mixed>       $registration
 * @var array<int, DietPreference> $diets
 * @var array<string, string>      $errors
 * @var array<string, mixed>       $old
 */
View::extend('layouts/public');

$value = static fn (string $key, string $default = ''): string => (string) ($old[$key] ?? $registration[$key] ?? $default);
$currentDiet = (string) ($old['diet'] ?? $registration['diet'] ?? 'standard');
$hasAllergies = isset($old['has_allergies'])
    ? (string) $old['has_allergies'] === '1'
    : (int) ($registration['has_allergies'] ?? 0) === 1;
?>

<section class="section">
    <div class="container container--narrow">
        <div class="section__header">
            <p class="section__eyebrow">Inscription</p>
            <h1>Vos préférences alimentaires</h1>
            <p class="lead">
                Les pauses et les repas de midi sont compris. Ces informations sont transmises
                au traiteur pour adapter les repas — et à personne d'autre.
            </p>
        </div>

        <?= View::partial('partials/steps', ['step' => $step]) ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert">
                <div class="alert__body">
                    <strong>Le formulaire comporte <?= e((string) count($errors)) ?> erreur(s).</strong>
                    Merci de vérifier les champs signalés.
                </div>
            </div>
        <?php endif; ?>

        <form method="post"
              action="<?= e(url('/inscription/preferences/' . (string) $registration['token'])) ?>"
              data-once novalidate>
            <?= Csrf::field() ?>

            <fieldset>
                <legend>Votre régime <span class="required" aria-hidden="true">*</span></legend>

                <div class="choice-list">
                    <?php foreach ($diets as $diet): ?>
                        <label class="choice" for="diet-<?= e($diet->value) ?>">
                            <input type="radio" id="diet-<?= e($diet->value) ?>" name="diet"
                                   value="<?= e($diet->value) ?>"
                                <?= $currentDiet === $diet->value ? 'checked' : '' ?> required>
                            <span>
                                <span class="choice__label"><?= e($diet->label()) ?></span>
                                <span class="choice__hint"><?= e($diet->hint()) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'diet']) ?>

                <div id="diet-comment-block" class="form-field mt-3" hidden>
                    <label class="form-label" for="diet_comment">
                        Précisions sur votre régime
                    </label>
                    <textarea class="form-control" id="diet_comment" name="diet_comment"
                              maxlength="500" rows="3" data-conditional-required="true"
                              placeholder="Ex. : viande halal uniquement, poisson accepté."
                        <?= isset($errors['diet_comment']) ? 'aria-invalid="true" aria-describedby="diet_comment-error"' : '' ?>
                    ><?= e($value('diet_comment')) ?></textarea>
                    <p class="form-hint">
                        Décrivez ce dont le traiteur doit tenir compte (halal, casher, régime médical,
                        aliments à éviter…).
                    </p>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'diet_comment']) ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>Allergies et intolérances</legend>

                <div class="form-check">
                    <input type="checkbox" id="has_allergies" name="has_allergies" value="1"
                        <?= $hasAllergies ? 'checked' : '' ?>>
                    <label for="has_allergies">
                        Je souffre d'une ou plusieurs allergies ou intolérances alimentaires
                    </label>
                </div>

                <div id="allergies-block" class="mt-3" hidden>
                    <div class="form-field">
                        <label class="form-label" for="allergies_comment">
                            Détail de vos allergies et intolérances
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control" id="allergies_comment" name="allergies_comment"
                                  maxlength="500" rows="3" data-conditional-required="true"
                                  placeholder="Ex. : allergie aux fruits à coque (arachides, noisettes) ; intolérance au lactose."
                            <?= isset($errors['allergies_comment']) ? 'aria-invalid="true" aria-describedby="allergies_comment-error"' : '' ?>
                        ><?= e($value('allergies_comment')) ?></textarea>
                        <p class="form-hint">
                            Soyez aussi précis que possible&nbsp;: ces informations conditionnent la
                            préparation de vos repas.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'allergies_comment']) ?>
                    </div>

                    <div class="panel panel--accent mt-3">
                        <div class="form-check">
                            <input type="checkbox" id="consent_health" name="consent_health" value="1"
                                   data-conditional-required="true"
                                <?= isset($old['consent_health']) ? 'checked' : '' ?>>
                            <label for="consent_health">
                                <strong>J'autorise le CRA-W à traiter ces informations relatives à ma santé</strong>
                                dans le seul but d'adapter les repas qui me seront servis, et à les
                                communiquer au traiteur chargé de la restauration.
                            </label>
                        </div>
                        <p class="form-hint mt-2 mb-0">
                            Les allergies constituent des données de santé. Elles ne sont accessibles
                            qu'aux personnes chargées de la logistique des repas et sont supprimées à
                            l'issue de la durée de conservation indiquée dans notre
                            <a href="<?= e(url('/confidentialite')) ?>">déclaration de confidentialité</a>.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_health']) ?>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Autres remarques</legend>

                <div class="form-field">
                    <label class="form-label" for="logistics_comment">
                        Remarques d'organisation
                    </label>
                    <textarea class="form-control" id="logistics_comment" name="logistics_comment"
                              maxlength="500" rows="3"
                              placeholder="Ex. : besoin d'un accès PMR, arrivée décalée le lundi matin."
                    ><?= e($value('logistics_comment')) ?></textarea>
                    <p class="form-hint">
                        Accessibilité, horaires, covoiturage… tout ce qui nous aide à bien vous accueillir.
                    </p>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'logistics_comment']) ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--pill btn--lg">Continuer</button>
                <a class="btn btn--ghost btn--pill" href="<?= e(url('/inscription')) ?>">Retour</a>
                <p class="small text-muted mb-0">Étape 2 sur 3</p>
            </div>
        </form>
    </div>
</section>

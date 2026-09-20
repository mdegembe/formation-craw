<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\View;
use Craw\Domain\DietPreference;

/**
 * Step 2: dietary preferences (English).
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
            <p class="section__eyebrow">Registration</p>
            <h1>Your dietary preferences</h1>
            <p class="lead">
                Breaks and lunches are included. This information is passed on to the
                caterer to adapt meals — and to no one else.
            </p>
        </div>

        <?= View::partial('partials/steps', ['step' => $step]) ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert--error" role="alert">
                <div class="alert__body">
                    <strong>The form has <?= e((string) count($errors)) ?> error(s).</strong>
                    Please check the fields flagged.
                </div>
            </div>
        <?php endif; ?>

        <form method="post"
              action="<?= e(url('/inscription/preferences/' . (string) $registration['token'])) ?>"
              data-once novalidate>
            <?= Csrf::field() ?>

            <fieldset>
                <legend>Your diet <span class="required" aria-hidden="true">*</span></legend>

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
                        Details about your diet
                    </label>
                    <textarea class="form-control" id="diet_comment" name="diet_comment"
                              maxlength="500" rows="3" data-conditional-required="true"
                              placeholder="E.g.: halal meat only, fish accepted."
                        <?= isset($errors['diet_comment']) ? 'aria-invalid="true" aria-describedby="diet_comment-error"' : '' ?>
                    ><?= e($value('diet_comment')) ?></textarea>
                    <p class="form-hint">
                        Describe what the caterer needs to take into account (halal, kosher, medical
                        diet, foods to avoid…).
                    </p>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'diet_comment']) ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>Allergies and intolerances</legend>

                <div class="form-check">
                    <input type="checkbox" id="has_allergies" name="has_allergies" value="1"
                        <?= $hasAllergies ? 'checked' : '' ?>>
                    <label for="has_allergies">
                        I have one or more food allergies or intolerances
                    </label>
                </div>

                <div id="allergies-block" class="mt-3" hidden>
                    <div class="form-field">
                        <label class="form-label" for="allergies_comment">
                            Details of your allergies and intolerances
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                        <textarea class="form-control" id="allergies_comment" name="allergies_comment"
                                  maxlength="500" rows="3" data-conditional-required="true"
                                  placeholder="E.g.: allergic to tree nuts (peanuts, hazelnuts); lactose intolerant."
                            <?= isset($errors['allergies_comment']) ? 'aria-invalid="true" aria-describedby="allergies_comment-error"' : '' ?>
                        ><?= e($value('allergies_comment')) ?></textarea>
                        <p class="form-hint">
                            Please be as precise as possible: this information determines how your
                            meals are prepared.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'allergies_comment']) ?>
                    </div>

                    <div class="panel panel--accent mt-3">
                        <div class="form-check">
                            <input type="checkbox" id="consent_health" name="consent_health" value="1"
                                   data-conditional-required="true"
                                <?= isset($old['consent_health']) ? 'checked' : '' ?>>
                            <label for="consent_health">
                                <strong>I authorise CRA-W to process this health-related information</strong>
                                for the sole purpose of adapting the meals served to me, and to share
                                it with the caterer providing the catering.
                            </label>
                        </div>
                        <p class="form-hint mt-2 mb-0">
                            Allergies are health-related data. They are only accessible to staff in
                            charge of meal logistics and are deleted at the end of the retention
                            period stated in our
                            <a href="<?= e(url('/confidentialite')) ?>">privacy notice</a>.
                        </p>
                        <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_health']) ?>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Other remarks</legend>

                <div class="form-field">
                    <label class="form-label" for="logistics_comment">
                        Organisational remarks
                    </label>
                    <textarea class="form-control" id="logistics_comment" name="logistics_comment"
                              maxlength="500" rows="3"
                              placeholder="E.g.: wheelchair access needed, arriving later on Monday morning."
                    ><?= e($value('logistics_comment')) ?></textarea>
                    <p class="form-hint">
                        Accessibility, timing, carpooling… anything that helps us welcome you well.
                    </p>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'logistics_comment']) ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--pill btn--lg">Continue</button>
                <a class="btn btn--ghost btn--pill" href="<?= e(url('/inscription')) ?>">Back</a>
                <p class="small text-muted mb-0">Step 2 of 3</p>
            </div>
        </form>
    </div>
</section>

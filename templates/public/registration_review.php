<?php

declare(strict_types=1);

use Craw\Core\Csrf;
use Craw\Core\Settings;
use Craw\Core\View;

/**
 * Etape 3 : relecture et validation.
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
            <p class="section__eyebrow">Inscription</p>
            <h1>Vérifiez vos informations</h1>
            <p class="lead">
                Relisez le récapitulatif ci-dessous, puis validez votre inscription.
                Rien n'est enregistré définitivement tant que vous n'avez pas validé.
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
                <h2 style="font-size: 1.125rem;">Identité et contact</h2>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/inscription')) ?>">Modifier</a>
            </div>

            <dl class="definition-list mt-3">
                <dt>Nom complet</dt>
                <dd>
                    <?= e(trim((string) ($registration['civility'] ?? '') . ' '
                        . (string) $registration['first_name'] . ' '
                        . (string) $registration['last_name'])) ?>
                </dd>

                <dt>Adresse e-mail</dt>
                <dd><?= e((string) $registration['email']) ?></dd>

                <dt>Téléphone</dt>
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
                    <dt>Adresse de facturation</dt>
                    <dd>
                        <?= e((string) ($registration['street'] ?? '')) ?><br>
                        <?= e((string) ($registration['postal_code'] ?? '')) ?>
                        <?= e((string) ($registration['city'] ?? '')) ?>,
                        <?= e((string) ($registration['country'] ?? '')) ?>
                        <?php if (!empty($registration['vat_number'])): ?>
                            <br>TVA&nbsp;: <?= e((string) $registration['vat_number']) ?>
                        <?php endif; ?>
                    </dd>
                <?php endif; ?>
            </dl>
        </div>

        <div class="panel mb-3">
            <div class="cluster" style="justify-content: space-between;">
                <h2 style="font-size: 1.125rem;">Repas</h2>
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('/inscription/preferences/' . $token)) ?>">Modifier</a>
            </div>

            <dl class="definition-list mt-3">
                <dt>Régime</dt>
                <dd>
                    <?= e($dietLabel) ?>
                    <?php if (!empty($registration['diet_comment'])): ?>
                        <br><span class="text-muted small"><?= e((string) $registration['diet_comment']) ?></span>
                    <?php endif; ?>
                </dd>

                <dt>Allergies</dt>
                <dd>
                    <?php if ((int) $registration['has_allergies'] === 1): ?>
                        <span class="badge badge--warning">Déclarées</span>
                        <br><span class="text-muted small"><?= e((string) $registration['allergies_comment']) ?></span>
                    <?php else: ?>
                        <span class="text-muted">Aucune déclarée</span>
                    <?php endif; ?>
                </dd>

                <?php if (!empty($registration['logistics_comment'])): ?>
                    <dt>Remarques</dt>
                    <dd class="text-muted"><?= e((string) $registration['logistics_comment']) ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <div class="panel panel--accent mb-3">
            <h2 style="font-size: 1.125rem;">La formation</h2>
            <dl class="definition-list mt-3">
                <dt>Dates</dt>
                <dd>
                    Du <?= e(fr_date((string) $training['starts_on'], true)) ?>
                    au <?= e(fr_date((string) $training['ends_on'], true)) ?>
                </dd>
                <dt>Lieu</dt>
                <dd>
                    <?= e((string) $training['location_name']) ?><br>
                    <span class="text-muted"><?= e((string) $training['location_address']) ?></span>
                </dd>
                <dt>Montant</dt>
                <dd class="fw-600">
                    <?php if ($price <= 0): ?>
                        Gratuit
                    <?php else: ?>
                        <?= e(money($price, (string) $training['currency'])) ?>
                        <span class="text-muted small">— à régler par virement dans les <?= e((string) $deadlineDays) ?> jours</span>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>

        <form method="post" action="<?= e(url('/inscription/confirmer/' . $token)) ?>" data-once>
            <?= Csrf::field() ?>

            <fieldset>
                <legend>Consentements</legend>

                <div class="stack">
                    <div class="form-check">
                        <input type="checkbox" id="consent_gdpr" name="consent_gdpr" value="1" required>
                        <label for="consent_gdpr">
                            J'accepte que le CRA-W traite mes données personnelles aux fins de
                            l'organisation de cette formation, conformément à la
                            <a href="<?= e(url('/confidentialite')) ?>">déclaration de confidentialité</a>.
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                    </div>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_gdpr']) ?>

                    <div class="form-check">
                        <input type="checkbox" id="consent_terms" name="consent_terms" value="1" required>
                        <label for="consent_terms">
                            J'ai pris connaissance des
                            <a href="<?= e(url('/infos-pratiques')) ?>">conditions de participation et d'annulation</a>
                            et je m'engage à régler le montant dû dans le délai indiqué.
                            <span class="required" aria-hidden="true">*</span>
                        </label>
                    </div>
                    <?= View::partial('partials/field_error', ['errors' => $errors, 'field' => 'consent_terms']) ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary btn--pill btn--lg">
                    Valider mon inscription
                </button>
                <a class="btn btn--ghost btn--pill"
                   href="<?= e(url('/inscription/preferences/' . $token)) ?>">Retour</a>
                <p class="small text-muted mb-0">Étape 3 sur 3</p>
            </div>
        </form>
    </div>
</section>

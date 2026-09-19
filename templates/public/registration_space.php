<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Repository\PaymentRepository;
use Craw\Service\RegistrationService;

/**
 * Espace participant, accessible par lien signe (aucun compte).
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
            <p class="section__eyebrow">Mon inscription</p>
            <h1>Bonjour <?= e((string) $registration['first_name']) ?></h1>
            <p class="lead">
                Voici le suivi de votre inscription à la formation
                « <?= e((string) $training['title']) ?> ».
            </p>
        </div>

        <div class="grid grid--3 mb-3">
            <div class="stat stat--accent">
                <span class="stat__label">Référence</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e((string) $registration['reference']) ?>
                </span>
                <span class="stat__hint">
                    Inscrit le <?= e(fr_date((string) $registration['created_at'])) ?>
                </span>
            </div>
            <div class="stat">
                <span class="stat__label">Statut de l'inscription</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <span class="badge <?= e($statusBadge) ?>">
                        <?= e(RegistrationService::statusLabel($status)) ?>
                    </span>
                </span>
            </div>
            <div class="stat">
                <span class="stat__label">Paiement</span>
                <span class="stat__value" style="font-size: 1.25rem;">
                    <?= e(PaymentRepository::statusLabel($paymentStatus)) ?>
                </span>
                <span class="stat__hint">
                    <?= e(money((int) $registration['amount_due_cents'], (string) $training['currency'])) ?>
                </span>
            </div>
        </div>

        <?php if ($status === 'waitlist'): ?>
            <div class="alert alert--info" role="status">
                <div class="alert__body">
                    <strong>Vous figurez sur la liste d'attente.</strong>
                    La formation affiche complet. Nous vous contacterons par courriel dès qu'une
                    place se libère — aucun paiement ne vous est demandé pour l'instant.
                </div>
            </div>
        <?php elseif ($status === 'cancelled'): ?>
            <div class="alert alert--warning" role="status">
                <div class="alert__body">
                    <strong>Cette inscription a été annulée.</strong>
                    Pour toute question, <a href="<?= e(url('/contact')) ?>">contactez-nous</a>.
                </div>
            </div>
        <?php elseif ($needsPayment): ?>
            <div class="alert alert--warning" role="status">
                <div class="alert__body">
                    <strong>Votre paiement est attendu.</strong>
                    <?php if (!empty($payment['due_date'])): ?>
                        Échéance&nbsp;: <?= e(fr_date((string) $payment['due_date'])) ?>.
                    <?php endif; ?>
                    <a href="<?= e(url('/inscription/paiement/' . $token)) ?>">
                        Voir les instructions de paiement
                    </a>.
                </div>
            </div>
        <?php elseif ($status === 'confirmed'): ?>
            <div class="alert alert--success" role="status">
                <div class="alert__body">
                    <strong>Votre inscription est confirmée.</strong>
                    Nous vous attendons le
                    <?= e(fr_date((string) $training['starts_on'], true)) ?>
                    à <?= e((string) $training['location_name']) ?>.
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid--2">
            <div class="panel">
                <h2 style="font-size: 1.125rem;">Vos informations</h2>
                <dl class="definition-list mt-3">
                    <dt>Nom</dt>
                    <dd><?= e(RegistrationService::fullName($registration)) ?></dd>
                    <dt>E-mail</dt>
                    <dd><?= e((string) $registration['email']) ?></dd>
                    <dt>Téléphone</dt>
                    <dd><?= e((string) ($registration['phone'] ?? '—')) ?></dd>
                    <?php if (!empty($registration['organisation'])): ?>
                        <dt>Organisation</dt>
                        <dd><?= e((string) $registration['organisation']) ?></dd>
                    <?php endif; ?>
                    <dt>Régime</dt>
                    <dd>
                        <?= e($dietLabel) ?>
                        <?php if ((int) $registration['has_allergies'] === 1): ?>
                            <br><span class="badge badge--warning">Allergies signalées</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                <p class="small text-muted mt-3 mb-0">
                    Une correction à apporter&nbsp;?
                    <a href="<?= e(url('/contact')) ?>">Écrivez-nous</a> en indiquant votre référence.
                </p>
            </div>

            <div class="panel">
                <h2 style="font-size: 1.125rem;">La formation</h2>
                <dl class="definition-list mt-3">
                    <dt>Dates</dt>
                    <dd>
                        Du <?= e(fr_date((string) $training['starts_on'], true)) ?><br>
                        au <?= e(fr_date((string) $training['ends_on'], true)) ?>
                    </dd>
                    <dt>Lieu</dt>
                    <dd>
                        <?= e((string) $training['location_name']) ?><br>
                        <span class="text-muted"><?= e((string) $training['location_address']) ?></span>
                    </dd>
                    <dt>Horaires</dt>
                    <dd>9h00 – 17h00 (accueil dès 8h30 le premier jour)</dd>
                </dl>

                <div class="btn-group mt-3">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/programme')) ?>">Programme</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/infos-pratiques')) ?>">Infos pratiques</a>
                </div>
            </div>
        </div>

        <?php if ($diploma !== null): ?>
            <div class="panel panel--accent mt-3">
                <h2 style="font-size: 1.125rem;">Votre diplôme de participation</h2>
                <p class="text-muted">
                    Diplôme n° <strong><?= e((string) $diploma['number']) ?></strong>,
                    délivré le <?= e(fr_date((string) $diploma['generated_at'])) ?>.
                </p>
                <div class="btn-group">
                    <a class="btn btn--primary btn--pill"
                       href="<?= e(url('/mon-inscription/' . $token . '/diplome')) ?>">
                        Télécharger mon diplôme (PDF)
                    </a>
                    <a class="btn btn--ghost btn--pill"
                       href="<?= e(url('/diplome/verification/' . (string) $diploma['verification_code'])) ?>">
                        Page de vérification
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($needsPayment): ?>
            <p class="mt-4">
                <a class="btn btn--primary btn--pill btn--lg"
                   href="<?= e(url('/inscription/paiement/' . $token)) ?>">
                    Régler mon inscription
                </a>
            </p>
        <?php endif; ?>

        <p class="small text-muted mt-4">
            Cette page est accessible uniquement via le lien personnel reçu par courriel.
            Ne le partagez pas&nbsp;: il donne accès à vos informations d'inscription.
        </p>
    </div>
</section>

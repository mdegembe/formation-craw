<?php

declare(strict_types=1);

use Craw\Core\View;
use Craw\Repository\PaymentRepository;

/**
 * Page de paiement par virement : coordonnees, communication structuree et
 * QR code SEPA.
 *
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>      $training
 * @var array<string, mixed>|null $payment
 */
View::extend('layouts/public');

$token = (string) $registration['token'];
$status = (string) ($payment['status'] ?? 'unpaid');
$isPaid = in_array($status, ['paid', 'waived'], true);
$amount = money($amountCents, (string) $training['currency']);
?>

<section class="section">
    <div class="container">
        <div class="section__header">
            <p class="section__eyebrow">Paiement</p>
            <h1>Régler votre inscription</h1>
            <p class="lead">
                Inscription <strong><?= e((string) $registration['reference']) ?></strong> —
                <?= e(trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name'])) ?>
            </p>
        </div>

        <?php if ($isPaid): ?>
            <div class="alert alert--success" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M12.736 3.97a.73.73 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.73.73 0 0 1-1.065.02L3.217 8.384a.75.75 0 0 1 0-1.06.73.73 0 0 1 1.04 0l3.052 3.093 5.4-6.425z"/>
                </svg>
                <div class="alert__body">
                    <strong>Votre paiement a bien été enregistré.</strong>
                    Votre inscription est définitivement confirmée. Aucune action de votre part n'est requise.
                </div>
            </div>

            <p><a class="btn btn--primary btn--pill" href="<?= e(url('/mon-inscription/' . $token)) ?>">
                Voir mon inscription
            </a></p>
        <?php else: ?>
            <div class="alert alert--info" role="status">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                </svg>
                <div class="alert__body">
                    Votre inscription est enregistrée. Elle sera <strong>définitivement confirmée</strong>
                    à réception de votre virement.
                    <?php if (!empty($payment['due_date'])): ?>
                        Merci d'effectuer le paiement pour le
                        <strong><?= e(fr_date((string) $payment['due_date'])) ?></strong> au plus tard.
                    <?php endif; ?>
                    <?php if ($status === 'partial'): ?>
                        <br><strong>Un paiement partiel a été reçu</strong>
                        (<?= e(money((int) ($payment['received_cents'] ?? 0))) ?>) :
                        le solde reste dû.
                    <?php endif; ?>
                </div>
            </div>

            <div class="payment-panel">
                <div>
                    <h2 style="font-size: 1.25rem;">Coordonnées du virement</h2>
                    <p class="text-muted">
                        Effectuez le virement depuis votre banque en reprenant <strong>exactement</strong>
                        la communication structurée : c'est elle qui permet de rapprocher automatiquement
                        votre paiement de votre inscription.
                    </p>

                    <dl class="payment-rows">
                        <div class="payment-row">
                            <div>
                                <dt>Bénéficiaire</dt>
                                <dd><?= e($beneficiary) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($beneficiary) ?>">
                                <span data-copy-label>Copier</span>
                                <span class="visually-hidden"> le nom du bénéficiaire</span>
                            </button>
                        </div>

                        <div class="payment-row">
                            <div>
                                <dt>IBAN</dt>
                                <dd><?= e($iban) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($ibanRaw) ?>">
                                <span data-copy-label>Copier</span>
                                <span class="visually-hidden"> l'IBAN</span>
                            </button>
                        </div>

                        <?php if ($bic !== ''): ?>
                            <div class="payment-row">
                                <div>
                                    <dt>BIC</dt>
                                    <dd><?= e($bic) ?></dd>
                                </div>
                                <button type="button" class="copy-btn" data-copy="<?= e($bic) ?>">
                                    <span data-copy-label>Copier</span>
                                    <span class="visually-hidden"> le BIC</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="payment-row">
                            <div>
                                <dt>Montant</dt>
                                <dd><?= e($amount) ?></dd>
                            </div>
                            <button type="button" class="copy-btn"
                                    data-copy="<?= e(number_format($amountCents / 100, 2, '.', '')) ?>">
                                <span data-copy-label>Copier</span>
                                <span class="visually-hidden"> le montant</span>
                            </button>
                        </div>

                        <div class="payment-row" style="border-color: var(--craw-accent);">
                            <div>
                                <dt>Communication structurée</dt>
                                <dd><?= e($communication) ?></dd>
                            </div>
                            <button type="button" class="copy-btn" data-copy="<?= e($communication) ?>">
                                <span data-copy-label>Copier</span>
                                <span class="visually-hidden"> la communication structurée</span>
                            </button>
                        </div>
                    </dl>

                    <div class="btn-group mt-3">
                        <a class="btn btn--outline btn--pill"
                           href="<?= e(url('/inscription/paiement/' . $token . '/instructions.pdf')) ?>">
                            Télécharger les instructions (PDF)
                        </a>
                        <a class="btn btn--ghost btn--pill" href="<?= e(url('/mon-inscription/' . $token)) ?>">
                            Mon inscription
                        </a>
                    </div>
                </div>

                <?php if ($qrAvailable): ?>
                    <aside class="qr-panel">
                        <h2 style="font-size: 1.125rem;">Payer en un scan</h2>
                        <p class="small text-muted">
                            Scannez ce QR code avec votre application bancaire&nbsp;:
                            le virement est pré-rempli.
                        </p>
                        <img src="<?= e($qrUrl) ?>" width="420" height="420" loading="lazy"
                             alt="QR code de virement SEPA pour l'inscription <?= e((string) $registration['reference']) ?>">
                        <p class="small text-muted mt-2 mb-0">
                            Format standard européen EPC069-12, reconnu par la plupart des
                            applications bancaires belges et européennes.
                        </p>
                    </aside>
                <?php endif; ?>
            </div>

            <div class="panel mt-4">
                <h2 style="font-size: 1.125rem;">Et ensuite&nbsp;?</h2>
                <ol class="mt-2" style="padding-left: 1.25rem;">
                    <li>Vous effectuez le virement depuis votre banque.</li>
                    <li>Le CRA-W vérifie la réception du paiement (comptez 2 à 4 jours ouvrables).</li>
                    <li>Vous recevez un courriel confirmant définitivement votre inscription.</li>
                    <li>Un rappel automatique vous est envoyé si le paiement n'est pas parvenu à l'échéance.</li>
                </ol>
                <p class="small text-muted mb-0">
                    Conservez le lien reçu par courriel&nbsp;: il vous permet de revenir sur cette page
                    à tout moment, sans mot de passe.
                </p>
            </div>
        <?php endif; ?>

        <p class="small text-muted mt-4">
            Statut du paiement&nbsp;:
            <strong><?= e(PaymentRepository::statusLabel($status)) ?></strong>
        </p>
    </div>
</section>

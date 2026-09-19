<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * Instructions de paiement a imprimer ou a transmettre au service comptable.
 *
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>      $training
 * @var array<string, mixed>|null $payment
 * @var string                    $qrDataUri
 */
$name = trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Instructions de paiement — <?= e((string) $registration['reference']) ?></title>
    <style><?= View::partial('pdf/_styles') ?></style>
</head>
<body>

<div class="brand-bar">
    <span class="brand-name">CRA-W — Formation</span><br>
    <span class="brand-sub">Centre wallon de Recherches agronomiques</span>
</div>

<h1>Instructions de paiement</h1>
<p class="muted">
    Inscription <strong><?= e((string) $registration['reference']) ?></strong> —
    document généré le <?= e(fr_date(date('Y-m-d'))) ?>
</p>

<h2>Participant</h2>
<table class="data">
    <tr>
        <th style="width: 35%;">Nom</th>
        <td><?= e($name) ?></td>
    </tr>
    <tr>
        <th>Adresse e-mail</th>
        <td><?= e((string) $registration['email']) ?></td>
    </tr>
    <?php if (!empty($registration['organisation'])): ?>
        <tr>
            <th>Organisation</th>
            <td><?= e((string) $registration['organisation']) ?></td>
        </tr>
    <?php endif; ?>
    <?php if ((int) $registration['needs_invoice'] === 1): ?>
        <tr>
            <th>Adresse de facturation</th>
            <td>
                <?= e((string) ($registration['street'] ?? '')) ?><br>
                <?= e((string) ($registration['postal_code'] ?? '')) ?>
                <?= e((string) ($registration['city'] ?? '')) ?> —
                <?= e((string) ($registration['country'] ?? '')) ?>
                <?php if (!empty($registration['vat_number'])): ?>
                    <br>TVA : <?= e((string) $registration['vat_number']) ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
</table>

<h2>Formation</h2>
<table class="data">
    <tr>
        <th style="width: 35%;">Intitulé</th>
        <td><?= e((string) $training['title']) ?></td>
    </tr>
    <tr>
        <th>Dates</th>
        <td>
            Du <?= e(fr_date((string) $training['starts_on'], true)) ?>
            au <?= e(fr_date((string) $training['ends_on'], true)) ?>
        </td>
    </tr>
    <tr>
        <th>Lieu</th>
        <td>
            <?= e((string) $training['location_name']) ?><br>
            <?= e((string) $training['location_address']) ?>
        </td>
    </tr>
</table>

<h2>Virement bancaire</h2>

<table class="kv">
    <tr>
        <td class="label">Bénéficiaire</td>
        <td class="value"><?= e($beneficiary) ?></td>
    </tr>
    <tr>
        <td class="label">IBAN</td>
        <td class="value"><?= e($iban) ?></td>
    </tr>
    <?php if ($bic !== ''): ?>
        <tr>
            <td class="label">BIC</td>
            <td class="value"><?= e($bic) ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <td class="label">Montant à verser</td>
        <td class="value"><?= e(money($amountCents, (string) $training['currency'])) ?></td>
    </tr>
    <tr class="highlight">
        <td class="label">Communication structurée</td>
        <td class="value"><?= e($communication) ?></td>
    </tr>
    <?php if (!empty($payment['due_date'])): ?>
        <tr>
            <td class="label">À régler pour le</td>
            <td class="value"><?= e(fr_date((string) $payment['due_date'])) ?></td>
        </tr>
    <?php endif; ?>
</table>

<div class="note warning">
    <strong>Important :</strong> reprenez la communication structurée telle quelle, sans la modifier.
    Elle permet le rapprochement automatique de votre paiement avec votre inscription. Un virement
    sans cette communication peut retarder la confirmation de votre inscription.
</div>

<?php if ($qrDataUri !== ''): ?>
    <h2>Payer en un scan</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 42%; vertical-align: top;">
                <img src="<?= $qrDataUri ?>" alt="QR code de virement SEPA" style="width: 150pt; height: 150pt;">
            </td>
            <td style="vertical-align: top; padding-left: 12pt;">
                <p>
                    Scannez ce code avec votre application bancaire&nbsp;: le bénéficiaire, l'IBAN,
                    le montant et la communication sont automatiquement pré-remplis.
                </p>
                <p class="small muted">
                    Code au format européen <strong>EPC069-12</strong>, reconnu par la plupart des
                    applications bancaires belges et européennes. Si votre application ne le prend pas
                    en charge, encodez le virement manuellement à l'aide des informations ci-dessus.
                </p>
            </td>
        </tr>
    </table>
<?php endif; ?>

<div class="footer-note">
    <?= e((string) setting('payment.beneficiary')) ?> — Rue de Liroux 9, 5030 Gembloux, Belgique<br>
    <?= e((string) setting('contact.email')) ?> — <?= e((string) setting('contact.phone')) ?><br>
    Suivi de votre inscription : <?= e(url('/mon-inscription/' . (string) $registration['token'])) ?>
</div>

</body>
</html>

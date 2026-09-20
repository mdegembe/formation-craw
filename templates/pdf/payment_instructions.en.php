<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * Payment instructions to print or forward to accounting (English).
 *
 * @var array<string, mixed>      $registration
 * @var array<string, mixed>      $training
 * @var array<string, mixed>|null $payment
 * @var string                    $qrDataUri
 */
$name = trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment instructions — <?= e((string) $registration['reference']) ?></title>
    <style><?= View::partial('pdf/_styles') ?></style>
</head>
<body>

<div class="brand-bar">
    <span class="brand-name">CRA-W — Training</span><br>
    <span class="brand-sub">Walloon Agricultural Research Centre</span>
</div>

<h1>Payment instructions</h1>
<p class="muted">
    Registration <strong><?= e((string) $registration['reference']) ?></strong> —
    document generated on <?= e(en_date(date('Y-m-d'))) ?>
</p>

<h2>Participant</h2>
<table class="data">
    <tr>
        <th style="width: 35%;">Name</th>
        <td><?= e($name) ?></td>
    </tr>
    <tr>
        <th>Email address</th>
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
            <th>Billing address</th>
            <td>
                <?= e((string) ($registration['street'] ?? '')) ?><br>
                <?= e((string) ($registration['postal_code'] ?? '')) ?>
                <?= e((string) ($registration['city'] ?? '')) ?> —
                <?= e((string) ($registration['country'] ?? '')) ?>
                <?php if (!empty($registration['vat_number'])): ?>
                    <br>VAT: <?= e((string) $registration['vat_number']) ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
</table>

<h2>Training</h2>
<table class="data">
    <tr>
        <th style="width: 35%;">Title</th>
        <td><?= e((string) $training['title']) ?></td>
    </tr>
    <tr>
        <th>Dates</th>
        <td>
            From <?= e(en_date((string) $training['starts_on'], true)) ?>
            to <?= e(en_date((string) $training['ends_on'], true)) ?>
        </td>
    </tr>
    <tr>
        <th>Venue</th>
        <td>
            <?= e((string) $training['location_name']) ?><br>
            <?= e((string) $training['location_address']) ?>
        </td>
    </tr>
</table>

<h2>Bank transfer</h2>

<table class="kv">
    <tr>
        <td class="label">Beneficiary</td>
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
        <td class="label">Amount to pay</td>
        <td class="value"><?= e(money_en($amountCents, (string) $training['currency'])) ?></td>
    </tr>
    <tr class="highlight">
        <td class="label">Structured reference</td>
        <td class="value"><?= e($communication) ?></td>
    </tr>
    <?php if (!empty($payment['due_date'])): ?>
        <tr>
            <td class="label">Due by</td>
            <td class="value"><?= e(en_date((string) $payment['due_date'])) ?></td>
        </tr>
    <?php endif; ?>
</table>

<div class="note warning">
    <strong>Important:</strong> use the structured reference exactly as shown, without changing it.
    It allows your payment to be automatically matched to your registration. A transfer without
    this reference may delay the confirmation of your registration.
</div>

<?php if ($qrDataUri !== ''): ?>
    <h2>Pay with one scan</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 42%; vertical-align: top;">
                <img src="<?= $qrDataUri ?>" alt="SEPA transfer QR code" style="width: 150pt; height: 150pt;">
            </td>
            <td style="vertical-align: top; padding-left: 12pt;">
                <p>
                    Scan this code with your banking app: the beneficiary, IBAN, amount and
                    reference are automatically pre-filled.
                </p>
                <p class="small muted">
                    Code in the European <strong>EPC069-12</strong> format, recognised by most
                    Belgian and European banking apps. If your app does not support it, enter the
                    transfer manually using the information above.
                </p>
            </td>
        </tr>
    </table>
<?php endif; ?>

<div class="footer-note">
    <?= e((string) setting('payment.beneficiary')) ?> — Rue de Liroux 9, 5030 Gembloux, Belgium<br>
    <?= e((string) setting('contact.email')) ?> — <?= e((string) setting('contact.phone')) ?><br>
    Track your registration: <?= e(url('/mon-inscription/' . (string) $registration['token'])) ?>
</div>

</body>
</html>

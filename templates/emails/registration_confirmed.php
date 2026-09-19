<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $payment
 * @var array<string, mixed> $links
 * @var bool $isWaitlist
 * @var bool $isFree
 */
require_once base_path('templates/emails/_components.php');

$name = trim((string) $registration['first_name'] . ' ' . (string) $registration['last_name']);
$dates = sprintf(
    'du %s au %s',
    fr_date((string) $training['starts_on']),
    fr_date((string) $training['ends_on'])
);

ob_start();
?>
<p style="margin:0 0 16px;">Bonjour <?= e((string) $registration['first_name']) ?>,</p>

<?php if ($isWaitlist): ?>
    <p style="margin:0 0 16px;">
        Nous avons bien reçu votre demande d'inscription à la formation
        <strong><?= e((string) $training['title']) ?></strong>, <?= e($dates) ?>.
    </p>

    <?= email_note(
        '<strong>La formation affiche complet.</strong> Votre demande est enregistrée en '
        . 'liste d\'attente : nous vous contacterons en priorité si une place se libère. '
        . 'Aucun paiement ne vous est demandé à ce stade.',
        'warning'
    ) ?>
<?php else: ?>
    <p style="margin:0 0 16px;">
        Votre inscription à la formation <strong><?= e((string) $training['title']) ?></strong>
        est enregistrée. Elle se tiendra <?= e($dates) ?> au
        <strong><?= e((string) $training['location_name']) ?></strong>,
        <?= e((string) $training['location_address']) ?>.
    </p>
<?php endif; ?>

<?= email_rows([
    'Référence de votre inscription' => (string) $registration['reference'],
    'Participant' => $name,
    'Dates' => $dates,
]) ?>

<?php if (!$isWaitlist && !$isFree): ?>
    <h2 style="margin:28px 0 8px;font-size:17px;">Régler votre inscription</h2>
    <p style="margin:0 0 12px;">
        Votre inscription sera <strong>définitivement confirmée à réception de votre virement</strong>.
        Merci d'indiquer la communication structurée ci-dessous&nbsp;: elle permet de rapprocher
        automatiquement votre paiement.
    </p>

    <?= email_rows([
        'Bénéficiaire' => (string) $payment['beneficiary'],
        'IBAN' => (string) $payment['iban'],
        'BIC' => (string) $payment['bic'],
        'Montant' => (string) $payment['amount'],
        'Communication structurée' => (string) $payment['communication'],
    ], true) ?>

    <?= email_button('Payer avec un QR code', (string) $links['payment']) ?>

    <p style="margin:0 0 16px;font-size:14px;color:#5c636a;">
        Sur cette page, un QR code au format européen EPC pré-remplit le virement depuis votre
        application bancaire&nbsp;: plus besoin de recopier l'IBAN ni la communication.
    </p>
<?php elseif ($isFree): ?>
    <?= email_note(
        'La participation à cette session est <strong>gratuite</strong> : '
        . 'aucun paiement ne vous sera demandé. Votre inscription est confirmée.',
        'success'
    ) ?>
<?php endif; ?>

<h2 style="margin:28px 0 8px;font-size:17px;">Votre espace personnel</h2>
<p style="margin:0 0 12px;">
    Le lien ci-dessous vous donne accès à votre inscription, à son statut et — à l'issue de la
    formation — à votre diplôme. <strong>Conservez ce courriel</strong>&nbsp;: aucun mot de passe
    n'est nécessaire, mais ce lien est personnel et ne doit pas être partagé.
</p>

<?= email_button('Accéder à mon inscription', (string) $links['space']) ?>

<?php if ((int) ($registration['id'] ?? 0) > 0 && (bool) ($training['subject_pending'] ?? false)): ?>
    <?= email_note(
        'Le <strong>sujet et le programme détaillé</strong> de cette session sont en cours de '
        . 'finalisation. Vous les recevrez par courriel dès leur validation.'
    ) ?>
<?php endif; ?>

<p style="margin:24px 0 0;">
    Une question&nbsp;? Répondez simplement à ce message ou écrivez-nous à
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>.
</p>

<p style="margin:16px 0 0;">Au plaisir de vous accueillir,<br>
    <strong>L'équipe formation du CRA-W</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => $isWaitlist
        ? 'Votre demande est enregistrée en liste d\'attente'
        : 'Votre inscription est enregistrée',
    'emailBody' => $emailBody,
    'preheader' => sprintf('Référence %s — %s', (string) $registration['reference'], $dates),
]);

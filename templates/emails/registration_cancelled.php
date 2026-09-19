<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var string|null $reason
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">Bonjour <?= e((string) $registration['first_name']) ?>,</p>

<p style="margin:0 0 16px;">
    Nous vous confirmons l'annulation de votre inscription
    (référence <strong><?= e((string) $registration['reference']) ?></strong>)
    à la formation <strong><?= e((string) $training['title']) ?></strong>.
</p>

<?php if ($reason !== null && $reason !== ''): ?>
    <?= email_note('<strong>Motif :</strong> ' . e($reason), 'warning') ?>
<?php endif; ?>

<p style="margin:0 0 16px;">
    Si un paiement avait déjà été effectué, son éventuel remboursement sera traité selon les
    conditions d'annulation communiquées lors de l'inscription. Notre équipe reviendra vers vous
    si nécessaire.
</p>

<p style="margin:0 0 16px;">
    Cette annulation vous semble erronée&nbsp;? Écrivez-nous à
    <a href="mailto:<?= e((string) $contact['email']) ?>" style="color:#0b6ea8;">
        <?= e((string) $contact['email']) ?></a>
    en rappelant votre référence, et nous rectifierons.
</p>

<p style="margin:20px 0 0;">Bien à vous,<br>
    <strong>L'équipe formation du CRA-W</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Annulation de votre inscription',
    'emailBody' => $emailBody,
    'preheader' => 'Inscription ' . (string) $registration['reference'] . ' annulée.',
]);

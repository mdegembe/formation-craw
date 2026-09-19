<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * Copie interne d'un message envoye depuis le formulaire de contact.
 *
 * @var string $senderName
 * @var string $senderEmail
 * @var string $messageSubject
 * @var string $messageBody
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">
    Un message a été envoyé depuis le formulaire de contact du site de la formation.
</p>

<?= email_rows([
    'Expéditeur' => $senderName,
    'Adresse e-mail' => $senderEmail,
    'Objet' => $messageSubject,
]) ?>

<h2 style="margin:24px 0 8px;font-size:17px;">Message</h2>
<div style="padding:16px;background-color:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;white-space:pre-wrap;">
<?= e($messageBody) ?>
</div>

<p style="margin:20px 0 0;font-size:14px;color:#5c636a;">
    Pour répondre, écrivez directement à
    <a href="mailto:<?= e($senderEmail) ?>" style="color:#0b6ea8;"><?= e($senderEmail) ?></a>.
</p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Nouveau message de contact',
    'emailBody' => $emailBody,
    'preheader' => $senderName . ' — ' . $messageSubject,
]);

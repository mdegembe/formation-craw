<?php

declare(strict_types=1);

use Craw\Core\View;

/**
 * @var array<string, mixed> $registration
 * @var array<string, mixed> $training
 * @var array<string, mixed> $diploma
 * @var string $verificationUrl
 */
require_once base_path('templates/emails/_components.php');

ob_start();
?>
<p style="margin:0 0 16px;">Bonjour <?= e((string) $registration['first_name']) ?>,</p>

<p style="margin:0 0 16px;">
    Vous avez suivi la formation <strong><?= e((string) $training['title']) ?></strong>,
    qui s'est tenue du <?= e(fr_date((string) $training['starts_on'])) ?>
    au <?= e(fr_date((string) $training['ends_on'])) ?>
    au <?= e((string) $training['location_name']) ?>.
</p>

<?= email_note(
    '<strong>Votre diplôme de participation est joint à ce message</strong>, au format PDF. '
    . 'Nous vous invitons à le conserver.',
    'success'
) ?>

<?= email_rows([
    'Numéro du diplôme' => (string) $diploma['number'],
    'Taux de présence' => \Craw\Domain\AttendanceCalculator::format((float) $diploma['attendance_rate']),
    'Date de délivrance' => fr_date((string) $diploma['generated_at']),
]) ?>

<p style="margin:0 0 16px;">
    Le diplôme porte un QR code et un code de vérification permettant à tout tiers — employeur,
    organisme de formation — d'en attester l'authenticité en ligne.
</p>

<?= email_button('Page de vérification du diplôme', $verificationUrl) ?>

<p style="margin:16px 0 0;">
    Merci de votre participation, et au plaisir de vous revoir lors d'une prochaine session.
</p>

<p style="margin:20px 0 0;">Bien à vous,<br>
    <strong>L'équipe formation du CRA-W</strong></p>
<?php
$emailBody = (string) ob_get_clean();

echo View::partial('emails/layout', [
    'emailTitle' => 'Votre diplôme de participation',
    'emailBody' => $emailBody,
    'preheader' => 'Diplôme n° ' . (string) $diploma['number'] . ' — joint en PDF.',
]);

<?php

declare(strict_types=1);

/**
 * Gabarit commun des courriels.
 *
 * Tableaux et styles en ligne : c'est la seule mise en forme qu'interpretent
 * de facon fiable Outlook, Gmail et consorts. Les couleurs reprennent la
 * charte du CRA-W.
 *
 * @var string $emailTitle
 * @var string $emailBody   HTML deja construit et echappe
 * @var string $preheader   Texte d'apercu affiche dans la liste des messages
 */
$preheader = $preheader ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($emailTitle) ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#212529;line-height:1.6;">

<?php if ($preheader !== ''): ?>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        <?= e($preheader) ?>
    </div>
<?php endif; ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:#f5f5f5;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                   style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #dee2e6;border-radius:8px;overflow:hidden;">

                <tr>
                    <td style="background-color:#198754;padding:20px 28px;">
                        <span style="display:inline-block;color:#ffffff;font-size:18px;font-weight:700;">
                            CRA-W — Formation
                        </span><br>
                        <span style="color:#d1e7dd;font-size:13px;">
                            Centre wallon de Recherches agronomiques
                        </span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px;">
                        <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#212529;">
                            <?= e($emailTitle) ?>
                        </h1>
                        <?= $emailBody ?>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#212529;padding:20px 28px;color:#adb5bd;font-size:13px;">
                        <p style="margin:0 0 8px;color:#ffffff;font-weight:600;">
                            Centre wallon de Recherches agronomiques
                        </p>
                        <p style="margin:0 0 8px;">
                            Rue de Liroux 9 — 5030 Gembloux, Belgique<br>
                            <?php $contactEmail = (string) setting('contact.email'); ?>
                            <?php if ($contactEmail !== ''): ?>
                                <a href="mailto:<?= e($contactEmail) ?>" style="color:#75b798;">
                                    <?= e($contactEmail) ?></a> —
                            <?php endif; ?>
                            <?= e((string) setting('contact.phone')) ?>
                        </p>
                        <p style="margin:0;font-size:12px;">
                            Ce message vous est adressé dans le cadre de votre inscription à une formation
                            du CRA-W. Vos données sont traitées conformément à notre
                            <a href="<?= e(url('/confidentialite')) ?>" style="color:#75b798;">
                                déclaration de confidentialité</a>.
                        </p>
                    </td>
                </tr>
            </table>

            <p style="max-width:600px;margin:16px auto 0;color:#6c757d;font-size:12px;text-align:center;">
                <?= e((string) setting('payment.beneficiary')) ?> —
                <a href="<?= e(url('/')) ?>" style="color:#6c757d;"><?= e(url('/')) ?></a>
            </p>
        </td>
    </tr>
</table>

</body>
</html>

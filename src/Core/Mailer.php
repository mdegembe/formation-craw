<?php

declare(strict_types=1);

namespace Craw\Core;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envoi d'un courriel. Trois transports :
 *  - smtp : production ;
 *  - mail : fonction mail() de PHP, en repli ;
 *  - log  : ecrit le message dans storage/logs/mails, pour le developpement.
 */
final class Mailer
{
    /**
     * @param array<int, array{path: string, name: string}> $attachments
     *
     * @throws MailException
     */
    public static function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        array $attachments = []
    ): void {
        $transport = Config::get('MAIL_TRANSPORT', 'smtp');

        if ($transport === 'log') {
            self::writeToLog($toEmail, $toName, $subject, $htmlBody, $textBody, $attachments);

            return;
        }

        $mailer = new PHPMailer(true);
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->Encoding = PHPMailer::ENCODING_BASE64;

        if ($transport === 'smtp') {
            $mailer->isSMTP();
            $mailer->Host = (string) Config::get('MAIL_HOST', 'localhost');
            $mailer->Port = Config::int('MAIL_PORT', 587);

            $username = Config::get('MAIL_USERNAME');
            if ($username !== null && $username !== '') {
                $mailer->SMTPAuth = true;
                $mailer->Username = $username;
                $mailer->Password = (string) Config::get('MAIL_PASSWORD', '');
            }

            $encryption = Config::get('MAIL_ENCRYPTION', 'tls');
            if ($encryption === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mailer->SMTPSecure = '';
                $mailer->SMTPAutoTLS = false;
            }

            $mailer->Timeout = 20;
        } else {
            $mailer->isMail();
        }

        $mailer->setFrom(
            (string) Config::get('MAIL_FROM_ADDRESS', 'no-reply@cra.wallonie.be'),
            (string) Config::get('MAIL_FROM_NAME', 'CRA-W')
        );

        $replyTo = Config::get('MAIL_REPLY_TO');
        if ($replyTo !== null && $replyTo !== '') {
            $mailer->addReplyTo($replyTo);
        }

        $mailer->addAddress($toEmail, $toName);
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody;

        foreach ($attachments as $attachment) {
            if (is_file($attachment['path'])) {
                $mailer->addAttachment($attachment['path'], $attachment['name']);
            }
        }

        $mailer->send();
    }

    /**
     * @param array<int, array{path: string, name: string}> $attachments
     */
    private static function writeToLog(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody,
        array $attachments
    ): void {
        $directory = storage_path('logs/mails');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        $filename = sprintf(
            '%s/%s-%s.html',
            $directory,
            date('Ymd-His'),
            substr(preg_replace('/[^a-z0-9]+/i', '-', $toEmail) ?? 'mail', 0, 40)
        );

        $header = sprintf(
            "<!-- A: %s <%s>\n     Objet: %s\n     Pieces jointes: %s\n     Texte brut:\n%s\n-->\n",
            $toName,
            $toEmail,
            $subject,
            $attachments === [] ? 'aucune' : implode(', ', array_column($attachments, 'name')),
            $textBody
        );

        file_put_contents($filename, $header . $htmlBody);
    }
}

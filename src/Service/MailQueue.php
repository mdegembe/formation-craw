<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Database;
use Craw\Core\Locale;
use Craw\Core\Logger;
use Craw\Core\Mailer;
use Craw\Core\View;

/**
 * File d'attente d'envoi : l'utilisateur n'attend jamais le serveur SMTP, et un
 * echec temporaire est rejoue automatiquement.
 */
final class MailQueue
{
    private const MAX_ATTEMPTS = 3;

    /**
     * @param array<string, mixed>                          $payload
     * @param array<int, array{path: string, name: string}> $attachments
     */
    public static function push(
        string $toEmail,
        string $toName,
        string $template,
        string $subject,
        array $payload = [],
        array $attachments = [],
        ?int $registrationId = null,
        ?string $scheduledAt = null
    ): int {
        return Database::insert('email_queue', [
            'registration_id' => $registrationId,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'template' => $template,
            'subject' => $subject,
            'payload' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE),
            'attachments' => $attachments === [] ? null : (string) json_encode($attachments, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => $scheduledAt ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Traite la file. Retourne [envoyes, echoues].
     *
     * @return array{sent: int, failed: int}
     */
    public static function process(int $batchSize = 25): array
    {
        $messages = Database::select(
            "SELECT * FROM email_queue
             WHERE status = 'pending' AND scheduled_at <= :now AND attempts < :max
             ORDER BY id ASC
             LIMIT {$batchSize}",
            ['now' => date('Y-m-d H:i:s'), 'max' => self::MAX_ATTEMPTS]
        );

        $sent = 0;
        $failed = 0;

        foreach ($messages as $message) {
            $id = (int) $message['id'];

            // Verrou optimiste : evite un double envoi si deux crons se croisent.
            $locked = Database::run(
                "UPDATE email_queue SET status = 'sending', attempts = attempts + 1
                 WHERE id = :id AND status = 'pending'",
                ['id' => $id]
            )->rowCount();

            if ($locked === 0) {
                continue;
            }

            try {
                self::deliver($message);

                Database::update('email_queue', [
                    'status' => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                    'last_error' => null,
                ], ['id' => $id]);

                self::log($message, 'sent', null);
                $sent++;
            } catch (\Throwable $exception) {
                $attempts = (int) $message['attempts'] + 1;
                $isFinal = $attempts >= self::MAX_ATTEMPTS;

                Database::update('email_queue', [
                    'status' => $isFinal ? 'failed' : 'pending',
                    // Attente croissante entre deux tentatives.
                    'scheduled_at' => date('Y-m-d H:i:s', time() + 300 * $attempts),
                    'last_error' => mb_substr($exception->getMessage(), 0, 500),
                ], ['id' => $id]);

                if ($isFinal) {
                    self::log($message, 'failed', $exception->getMessage());
                    $failed++;
                }

                Logger::error('Echec d\'envoi de courriel.', [
                    'queue_id' => $id,
                    'template' => $message['template'],
                    'attempt' => $attempts,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * @param array<string, mixed> $message
     */
    private static function deliver(array $message): void
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) ($message['payload'] ?? '{}'), true) ?: [];

        /** @var array<int, array{path: string, name: string}> $attachments */
        $attachments = json_decode((string) ($message['attachments'] ?? '[]'), true) ?: [];

        $template = (string) $message['template'];
        $subject = (string) $message['subject'];

        $payload['subject'] = $subject;
        $payload['recipientName'] = (string) ($message['to_name'] ?? '');

        // La langue est celle figee au moment de la mise en file (voir
        // Notifier::payload()), pas celle de la requete en cours : ce code
        // tourne souvent depuis le cron, hors de toute requete web.
        Locale::set((string) ($payload['locale'] ?? Locale::DEFAULT));

        $html = View::render('emails/' . $template, $payload);
        $text = View::partial('emails/text/' . $template, $payload);

        Mailer::send(
            (string) $message['to_email'],
            (string) ($message['to_name'] ?? ''),
            $subject,
            $html,
            $text,
            $attachments
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    private static function log(array $message, string $status, ?string $error): void
    {
        Database::insert('email_log', [
            'registration_id' => $message['registration_id'] ?? null,
            'to_email' => (string) $message['to_email'],
            'template' => (string) $message['template'],
            'subject' => (string) $message['subject'],
            'status' => $status,
            'error' => $error === null ? null : mb_substr($error, 0, 500),
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Historique des envois d'une inscription.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function historyFor(int $registrationId): array
    {
        return Database::select(
            'SELECT * FROM email_log WHERE registration_id = :id ORDER BY sent_at DESC',
            ['id' => $registrationId]
        );
    }

    /** Nombre de messages encore en attente. */
    public static function pendingCount(): int
    {
        return (int) Database::scalar("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
    }
}

<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Settings;
use Craw\Domain\SepaQrPayload;

/**
 * Composition des courriels transactionnels : un seul endroit ou le contenu
 * des messages est decide, la mise en file etant deleguee a MailQueue.
 */
final class Notifier
{
    /**
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     */
    public static function registrationConfirmed(array $registration, array $training): void
    {
        $isWaitlist = (string) $registration['status'] === 'waitlist';
        $isFree = (int) $registration['amount_due_cents'] <= 0;

        $subject = $isWaitlist
            ? 'Votre demande d\'inscription est enregistrée (liste d\'attente)'
            : 'Confirmation de votre inscription — ' . (string) $training['title'];

        MailQueue::push(
            (string) $registration['email'],
            RegistrationService::fullName($registration),
            'registration_confirmed',
            $subject,
            self::payload($registration, $training) + [
                'isWaitlist' => $isWaitlist,
                'isFree' => $isFree,
            ],
            [],
            (int) $registration['id']
        );
    }

    /**
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     */
    public static function paymentReminder(array $registration, array $training, bool $isFinal = false): void
    {
        MailQueue::push(
            (string) $registration['email'],
            RegistrationService::fullName($registration),
            'payment_reminder',
            $isFinal
                ? 'Dernier rappel — paiement de votre inscription'
                : 'Rappel — paiement de votre inscription à la formation',
            self::payload($registration, $training) + ['isFinal' => $isFinal],
            [],
            (int) $registration['id']
        );
    }

    /**
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     */
    public static function paymentConfirmed(array $registration, array $training): void
    {
        MailQueue::push(
            (string) $registration['email'],
            RegistrationService::fullName($registration),
            'payment_confirmed',
            'Paiement reçu — votre inscription est confirmée',
            self::payload($registration, $training),
            [],
            (int) $registration['id']
        );
    }

    /**
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     */
    public static function registrationCancelled(array $registration, array $training, ?string $reason = null): void
    {
        MailQueue::push(
            (string) $registration['email'],
            RegistrationService::fullName($registration),
            'registration_cancelled',
            'Annulation de votre inscription',
            self::payload($registration, $training) + ['reason' => $reason],
            [],
            (int) $registration['id']
        );
    }

    /**
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     * @param array<string, mixed> $diploma
     */
    public static function diplomaIssued(array $registration, array $training, array $diploma, string $pdfPath): void
    {
        MailQueue::push(
            (string) $registration['email'],
            RegistrationService::fullName($registration),
            'diploma_issued',
            'Votre diplôme de participation',
            self::payload($registration, $training) + [
                'diploma' => $diploma,
                'verificationUrl' => url('/diplome/verification/' . (string) $diploma['verification_code']),
            ],
            [[
                'path' => $pdfPath,
                'name' => sprintf(
                    'diplome-%s.pdf',
                    preg_replace('/[^A-Za-z0-9]+/', '-', RegistrationService::fullName($registration)) ?? 'participant'
                ),
            ]],
            (int) $registration['id']
        );
    }

    /**
     * Données communes a tous les gabarits de courriel.
     *
     * @param array<string, mixed> $registration
     * @param array<string, mixed> $training
     *
     * @return array<string, mixed>
     */
    private static function payload(array $registration, array $training): array
    {
        $iban = (string) Settings::get('payment.iban', '');
        $amountCents = (int) $registration['amount_due_cents'];
        $communication = (string) ($registration['structured_communication'] ?? '');

        return [
            'registration' => [
                'id' => (int) $registration['id'],
                'reference' => (string) $registration['reference'],
                'token' => (string) $registration['token'],
                'first_name' => (string) $registration['first_name'],
                'last_name' => (string) $registration['last_name'],
                'email' => (string) $registration['email'],
                'status' => (string) $registration['status'],
                'amount_due_cents' => $amountCents,
                'structured_communication' => $communication,
            ],
            'training' => [
                'title' => (string) $training['title'],
                'subject' => $training['subject'] ?? null,
                'subject_pending' => (bool) ($training['subject_pending'] ?? true),
                'starts_on' => (string) $training['starts_on'],
                'ends_on' => (string) $training['ends_on'],
                'location_name' => (string) $training['location_name'],
                'location_address' => (string) $training['location_address'],
            ],
            'payment' => [
                'beneficiary' => (string) Settings::get('payment.beneficiary', ''),
                'iban' => SepaQrPayload::formatIban($iban),
                'bic' => (string) Settings::get('payment.bic', ''),
                'amount' => money($amountCents),
                'communication' => $communication,
            ],
            'links' => [
                'space' => url('/mon-inscription/' . (string) $registration['token']),
                'payment' => url('/inscription/paiement/' . (string) $registration['token']),
                'site' => url('/'),
                'contact' => url('/contact'),
            ],
            'contact' => [
                'email' => (string) Settings::get('contact.email', ''),
                'phone' => (string) Settings::get('contact.phone', ''),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Settings;
use Craw\Domain\SepaQrPayload;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\PdfService;
use Craw\Service\QrService;

/**
 * Page de paiement par virement : coordonnees bancaires, communication
 * structuree et QR code SEPA pre-remplissant le virement.
 */
final class PaymentController extends BaseController
{
    public function show(Request $request, array $params): Response
    {
        [$registration, $training, $payment] = $this->context((string) $params['token']);

        $iban = (string) Settings::get('payment.iban', '');
        $amountCents = (int) $registration['amount_due_cents'];
        $communication = (string) ($registration['structured_communication'] ?? '');

        return $this->view('public/payment', [
            'title' => t('meta.payment_title'),
            'description' => t('meta.payment_description'),
            'canonical' => url('/'),
            'noindex' => true,
            'registration' => $registration,
            'training' => $training,
            'payment' => $payment,
            'beneficiary' => (string) Settings::get('payment.beneficiary', ''),
            'iban' => SepaQrPayload::formatIban($iban),
            'ibanRaw' => SepaQrPayload::normaliseIban($iban),
            'bic' => (string) Settings::get('payment.bic', ''),
            'amountCents' => $amountCents,
            'communication' => $communication,
            'qrUrl' => url('/inscription/paiement/' . (string) $registration['token'] . '/qr.png'),
            'qrAvailable' => SepaQrPayload::isValidIban($iban) && $amountCents > 0,
            'dueDate' => $payment['due_date'] ?? null,
        ]);
    }

    /**
     * Image du QR code SEPA.
     *
     * Servie par un contrôleur plutôt qu'en data URI : l'image reste
     * cacheable par le navigateur et le HTML de la page reste léger.
     */
    public function qrCode(Request $request, array $params): Response
    {
        [$registration] = $this->context((string) $params['token']);

        $iban = (string) Settings::get('payment.iban', '');
        $amountCents = (int) $registration['amount_due_cents'];

        if (!SepaQrPayload::isValidIban($iban) || $amountCents <= 0) {
            $this->notFound(t('notfound.qr_unavailable'));
        }

        $png = QrService::sepaPng(
            (string) Settings::get('payment.beneficiary', ''),
            $iban,
            $amountCents,
            $this->remittance($registration),
            (string) Settings::get('payment.bic', ''),
            420
        );

        return Response::inline($png, 'virement-sepa.png', 'image/png')
            ->withHeader('Cache-Control', 'private, max-age=3600');
    }

    /** Instructions de paiement au format PDF, a imprimer ou a transmettre. */
    public function instructions(Request $request, array $params): Response
    {
        [$registration, $training, $payment] = $this->context((string) $params['token']);

        $iban = (string) Settings::get('payment.iban', '');
        $amountCents = (int) $registration['amount_due_cents'];

        $pdf = PdfService::fromTemplate('pdf/payment_instructions', [
            'registration' => $registration,
            'training' => $training,
            'payment' => $payment,
            'beneficiary' => (string) Settings::get('payment.beneficiary', ''),
            'iban' => SepaQrPayload::formatIban($iban),
            'bic' => (string) Settings::get('payment.bic', ''),
            'amountCents' => $amountCents,
            'communication' => (string) ($registration['structured_communication'] ?? ''),
            'qrDataUri' => SepaQrPayload::isValidIban($iban) && $amountCents > 0
                ? QrService::sepaDataUri(
                    (string) Settings::get('payment.beneficiary', ''),
                    $iban,
                    $amountCents,
                    $this->remittance($registration),
                    (string) Settings::get('payment.bic', ''),
                    260
                )
                : '',
        ]);

        return Response::inline(
            $pdf,
            sprintf('instructions-paiement-%s.pdf', (string) $registration['reference']),
            'application/pdf'
        );
    }

    /**
     * Communication portee par le QR code.
     *
     * La norme EPC n'encode pas de facon interoperable une communication
     * structuree belge : on l'ecrit dans le champ de communication libre,
     * ce que toutes les applications bancaires savent lire.
     *
     * @param array<string, mixed> $registration
     */
    private function remittance(array $registration): string
    {
        $communication = (string) ($registration['structured_communication'] ?? '');

        return $communication !== ''
            ? $communication
            : 'Inscription ' . (string) $registration['reference'];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null}
     */
    private function context(string $token): array
    {
        $registration = (new RegistrationRepository())->findByToken($token);

        if ($registration === null || (string) $registration['status'] === 'draft') {
            $this->notFound(t('notfound.payment_link'));
        }

        $training = (new TrainingRepository())->find((int) $registration['training_id']);

        if ($training === null) {
            $this->notFound();
        }

        return [
            $registration,
            $training,
            (new PaymentRepository())->findByRegistration((int) $registration['id']),
        ];
    }
}

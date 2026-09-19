<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Repository\PaymentRepository;
use Craw\Service\PaymentService;

final class PaymentAdminController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $repository = new PaymentRepository();

        return $this->adminView('admin/payments', [
            'title' => 'Paiements — Administration',
            'activeAdminNav' => 'payments',
            'pending' => $repository->pending($trainingId),
            'totals' => $repository->totals($trainingId),
        ]);
    }

    public function confirm(Request $request, array $params): Response
    {
        $registrationId = (int) $params['id'];

        // Montant saisi en euros dans le formulaire, converti en centimes :
        // on evite toute arithmetique en virgule flottante sur de l'argent.
        $receivedInput = $request->input('received');
        $receivedCents = null;

        if ($receivedInput !== null && $receivedInput !== '') {
            $normalised = str_replace([' ', "\u{202F}", ','], ['', '', '.'], $receivedInput);

            if (!is_numeric($normalised)) {
                Session::flash('error', 'Le montant reçu doit être un nombre (ex. 450,00).');

                return $this->redirect('/admin/paiements');
            }

            $receivedCents = (int) round(((float) $normalised) * 100);
        }

        $valueDate = $request->input('value_date');

        if ($valueDate !== null && $valueDate !== ''
            && \DateTimeImmutable::createFromFormat('Y-m-d', $valueDate) === false
        ) {
            $valueDate = null;
        }

        $ok = (new PaymentService())->confirm(
            $registrationId,
            $receivedCents,
            $valueDate === '' ? null : $valueDate,
            $request->input('note'),
            Auth::id()
        );

        if (!$ok) {
            Session::flash('error', 'Inscription ou paiement introuvable.');
        } else {
            $payment = (new PaymentRepository())->findByRegistration($registrationId);

            if ($payment !== null && (string) $payment['status'] === 'partial') {
                Session::flash(
                    'warning',
                    'Paiement enregistré comme PARTIEL : le montant reçu est inférieur au montant dû. '
                    . 'L\'inscription reste en attente de confirmation.'
                );
            } else {
                Session::flash('success', 'Paiement confirmé, le participant a été prévenu par courriel.');
            }
        }

        return $this->redirect($this->backTo($request, $registrationId));
    }

    public function waive(Request $request, array $params): Response
    {
        $registrationId = (int) $params['id'];

        $ok = (new PaymentService())->waive($registrationId, $request->input('note'), Auth::id());

        Session::flash(
            $ok ? 'success' : 'error',
            $ok ? 'Inscription exonérée de paiement et confirmée.' : 'Inscription introuvable.'
        );

        return $this->redirect($this->backTo($request, $registrationId));
    }

    public function revert(Request $request, array $params): Response
    {
        Auth::requireAdmin();

        $registrationId = (int) $params['id'];

        $ok = (new PaymentService())->revert($registrationId, Auth::id());

        Session::flash(
            $ok ? 'success' : 'error',
            $ok
                ? 'Confirmation de paiement annulée : l\'inscription repasse en attente.'
                : 'Inscription introuvable.'
        );

        return $this->redirect($this->backTo($request, $registrationId));
    }

    /** Met en file les relances dues, sans attendre le passage du cron. */
    public function remind(Request $request, array $params): Response
    {
        $training = $this->training();

        $result = (new PaymentService())->queueReminders((int) $training['id']);

        Session::flash(
            $result['reminders'] > 0 ? 'success' : 'info',
            $result['reminders'] > 0
                ? sprintf('%d relance(s) mise(s) en file d\'envoi.', $result['reminders'])
                : 'Aucune relance à envoyer pour l\'instant : soit les échéances ne sont pas atteintes, '
                  . 'soit les participants concernés ont déjà été relancés récemment.'
        );

        return $this->redirect('/admin/paiements');
    }

    /** Revient sur l'ecran d'ou l'action a ete declenchee. */
    private function backTo(Request $request, int $registrationId): string
    {
        return $request->input('from') === 'detail'
            ? '/admin/inscriptions/' . $registrationId
            : '/admin/paiements';
    }
}

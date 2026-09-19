<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Domain\DietPreference;
use Craw\Repository\DiplomaRepository;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Service\ExportService;
use Craw\Service\MailQueue;
use Craw\Service\Notifier;
use Craw\Service\RegistrationService;

final class RegistrationAdminController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $filters = [
            'status' => (string) ($request->input('statut') ?? ''),
            'payment' => (string) ($request->input('paiement') ?? ''),
            'diet' => (string) ($request->input('regime') ?? ''),
            'search' => (string) ($request->input('q') ?? ''),
        ];

        $repository = new RegistrationRepository();

        return $this->adminView('admin/registrations', [
            'title' => 'Inscriptions — Administration',
            'activeAdminNav' => 'registrations',
            'registrations' => $repository->search($trainingId, $filters),
            'total' => $repository->countSearch($trainingId, $filters),
            'filters' => $filters,
            'diets' => DietPreference::cases(),
            'capacity' => (int) $training['capacity'],
        ]);
    }

    public function show(Request $request, array $params): Response
    {
        $registration = $this->find((int) $params['id']);

        return $this->adminView('admin/registration_show', [
            'title' => 'Inscription ' . (string) $registration['reference'],
            'activeAdminNav' => 'registrations',
            'registration' => $registration,
            'payment' => (new PaymentRepository())->findByRegistration((int) $registration['id']),
            'diploma' => (new DiplomaRepository())->latestForRegistration((int) $registration['id']),
            'dietLabel' => DietPreference::tryLabel((string) $registration['diet']),
            'emails' => MailQueue::historyFor((int) $registration['id']),
        ]);
    }

    public function cancel(Request $request, array $params): Response
    {
        $registration = $this->find((int) $params['id']);
        $reason = $request->input('reason');
        $notify = $request->boolean('notify');

        $service = new RegistrationService();
        $service->cancel((int) $registration['id'], $reason);

        Audit::record('registration.cancel', 'registration', (int) $registration['id'], [
            'reason' => $reason,
        ], $request->ipHash());

        if ($notify) {
            $fresh = (new RegistrationRepository())->find((int) $registration['id']);

            if ($fresh !== null) {
                Notifier::registrationCancelled($fresh, $this->training(), $reason);
            }
        }

        // Une place se libere : on promeut le premier de la liste d'attente.
        $promoted = $service->promoteFromWaitlist((int) $registration['training_id']);

        if ($promoted !== null) {
            Notifier::registrationConfirmed($promoted, $this->training());

            Session::flash('info', sprintf(
                'Inscription annulée. %s a été promu(e) depuis la liste d\'attente et prévenu(e) par courriel.',
                RegistrationService::fullName($promoted)
            ));
        } else {
            Session::flash('success', 'Inscription annulée.');
        }

        return $this->redirect('/admin/inscriptions/' . (int) $registration['id']);
    }

    /** Renvoie le courriel de confirmation et ses instructions de paiement. */
    public function resend(Request $request, array $params): Response
    {
        $registration = $this->find((int) $params['id']);

        Notifier::registrationConfirmed($registration, $this->training());

        Audit::record('registration.resend', 'registration', (int) $registration['id'], [], $request->ipHash());
        Session::flash('success', 'Le courriel de confirmation a été remis en file d\'envoi.');

        return $this->redirect('/admin/inscriptions/' . (int) $registration['id']);
    }

    public function destroy(Request $request, array $params): Response
    {
        Auth::requireAdmin();

        $registration = $this->find((int) $params['id']);

        // Une inscription confirmee ne se supprime pas : elle s'annule, pour
        // conserver la trace comptable et le lien avec un eventuel diplome.
        if ((string) $registration['status'] !== 'cancelled') {
            Session::flash('error', 'Annulez d\'abord l\'inscription avant de la supprimer définitivement.');

            return $this->redirect('/admin/inscriptions/' . (int) $registration['id']);
        }

        Audit::record('registration.delete', 'registration', (int) $registration['id'], [
            'reference' => (string) $registration['reference'],
        ], $request->ipHash());

        (new RegistrationRepository())->delete((int) $registration['id']);

        Session::flash('success', 'Inscription supprimée définitivement.');

        return $this->redirect('/admin/inscriptions');
    }

    public function export(Request $request, array $params): Response
    {
        $training = $this->training();

        $filters = [
            'status' => (string) ($request->input('statut') ?? ''),
            'payment' => (string) ($request->input('paiement') ?? ''),
            'diet' => (string) ($request->input('regime') ?? ''),
            'search' => (string) ($request->input('q') ?? ''),
        ];

        // Les donnees de sante sont exclues par defaut de l'export general :
        // elles ne sont ajoutees que sur demande explicite (minimisation RGPD).
        $includeHealth = $request->boolean('sante');

        $rows = (new RegistrationRepository())->search((int) $training['id'], $filters, 5000);

        Audit::record('registration.export', 'training', (int) $training['id'], [
            'rows' => count($rows),
            'health_data' => $includeHealth,
        ], $request->ipHash());

        return Response::download(
            ExportService::registrationsCsv($rows, $includeHealth),
            ExportService::filename('inscriptions-formation-craw', 'csv'),
            'text/csv; charset=UTF-8'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function find(int $id): array
    {
        $registration = (new RegistrationRepository())->find($id);

        if ($registration === null) {
            $this->notFound('Inscription introuvable.');
        }

        return $registration;
    }
}

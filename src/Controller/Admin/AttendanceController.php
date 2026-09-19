<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Settings;
use Craw\Repository\AttendanceRepository;
use Craw\Service\AttendanceService;
use Craw\Service\ExportService;
use Craw\Service\PdfService;

/**
 * Pointage des presences « a l'envers » : tout le monde est presume present,
 * l'administrateur ne saisit que les absences.
 */
final class AttendanceController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $service = new AttendanceService();
        $grid = $service->grid($trainingId);

        return $this->adminView('admin/attendance', [
            'title' => 'Présences — Administration',
            'activeAdminNav' => 'attendance',
            'days' => $grid['days'],
            'participants' => $grid['participants'],
            'absences' => $grid['absences'],
            'summary' => $service->summary($trainingId),
            'statistics' => $service->statistics($trainingId),
            'minRate' => Settings::minAttendanceRate(),
        ]);
    }

    /**
     * Enregistre la grille.
     *
     * Le formulaire envoie les cases COCHEES, c'est-a-dire les presents. On en
     * deduit les absents par difference : ainsi, ne rien cocher pour un creneau
     * signifie bien « tout le monde absent », et non « rien a enregistrer ».
     */
    public function save(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $service = new AttendanceService();
        $grid = $service->grid($trainingId);

        $participantIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $grid['participants']
        );

        $repository = new AttendanceRepository();

        // Le formulaire peut ne porter que sur un creneau (colonne) : on ne
        // touche qu'aux creneaux effectivement soumis.
        $submittedDays = $request->inputArray('days');
        $dayIds = array_map(
            static fn (array $day): int => (int) $day['id'],
            $grid['days']
        );

        if ($submittedDays !== []) {
            $dayIds = array_values(array_intersect(
                $dayIds,
                array_map('intval', $submittedDays)
            ));
        }

        $totalAbsences = 0;

        foreach ($dayIds as $dayId) {
            /** @var array<int, string> $present */
            $present = $request->inputArray('present_' . $dayId);
            $presentIds = array_map('intval', $present);

            $absentIds = array_values(array_diff($participantIds, $presentIds));

            $repository->replaceDayAbsences($dayId, $absentIds, Auth::id());
            $totalAbsences += count($absentIds);
        }

        Audit::record('attendance.save', 'training', $trainingId, [
            'days' => count($dayIds),
            'absences' => $totalAbsences,
        ], $request->ipHash());

        Session::flash('success', sprintf(
            'Présences enregistrées : %d absence(s) sur %d créneau(x).',
            $totalAbsences,
            count($dayIds)
        ));

        return $this->redirect('/admin/presences');
    }

    public function export(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $service = new AttendanceService();
        $grid = $service->grid($trainingId);

        return Response::download(
            ExportService::attendanceCsv($service->summary($trainingId), $grid['days'], $grid['absences']),
            ExportService::filename('presences-formation-craw', 'csv'),
            'text/csv; charset=UTF-8'
        );
    }

    /** Feuille d'emargement papier, une page par creneau. */
    public function signatureSheet(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $grid = (new AttendanceService())->grid($trainingId);
        $dayFilter = $request->integer('jour', 0);

        $days = $grid['days'];

        if ($dayFilter > 0) {
            $days = array_values(array_filter(
                $days,
                static fn (array $day): bool => (int) $day['id'] === $dayFilter
            ));
        }

        if ($days === []) {
            $this->notFound('Aucun créneau à imprimer.');
        }

        $pdf = PdfService::fromTemplate('pdf/attendance_sheet', [
            'training' => $training,
            'days' => $days,
            'participants' => $grid['participants'],
        ]);

        return Response::inline($pdf, ExportService::filename('emargement', 'pdf'), 'application/pdf');
    }
}

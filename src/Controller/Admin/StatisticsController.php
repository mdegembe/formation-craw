<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Service\ExportService;
use Craw\Service\PdfService;
use Craw\Service\StatisticsService;

final class StatisticsController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $service = new StatisticsService();
        $breakdown = $service->dietBreakdown($trainingId);

        return $this->adminView('admin/statistics', [
            'title' => 'Statistiques alimentaires — Administration',
            'activeAdminNav' => 'statistics',
            'headline' => $service->headline($trainingId),
            'breakdown' => $breakdown,
            // Echelle du graphique : on rapporte chaque barre au plus grand
            // effectif, sinon les petits regimes deviennent illisibles.
            'maxTotal' => max(1, max(array_column($breakdown, 'total'))),
            'allergies' => $service->allergies($trainingId),
            'dietComments' => $service->dietComments($trainingId),
            'logisticsComments' => $service->logisticsComments($trainingId),
        ]);
    }

    public function export(Request $request, array $params): Response
    {
        $training = $this->training();

        $breakdown = (new StatisticsService())->dietBreakdown((int) $training['id']);

        return Response::download(
            ExportService::dietStatisticsCsv($breakdown),
            ExportService::filename('statistiques-alimentaires', 'csv'),
            'text/csv; charset=UTF-8'
        );
    }

    /**
     * Fiche a transmettre au traiteur.
     *
     * Elle ne contient que ce dont le traiteur a besoin : effectifs par regime
     * et contraintes individuelles. Aucune coordonnee n'y figure.
     */
    public function catererSheet(Request $request, array $params): Response
    {
        $training = $this->training();

        $sheet = (new StatisticsService())->catererSheet((int) $training['id']);

        Audit::record('statistics.caterer_sheet', 'training', (int) $training['id'], [
            'allergies' => count($sheet['allergies']),
        ], $request->ipHash());

        $pdf = PdfService::fromTemplate('pdf/caterer_sheet', [
            'training' => $training,
            'sheet' => $sheet,
        ]);

        return Response::inline(
            $pdf,
            ExportService::filename('fiche-traiteur', 'pdf'),
            'application/pdf'
        );
    }
}

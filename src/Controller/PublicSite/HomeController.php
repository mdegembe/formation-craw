<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Domain\Markdown;
use Craw\Repository\NewsRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\SeoService;

final class HomeController extends BaseController
{
    public function index(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound('Aucune formation n\'est actuellement publiée.');
        }

        $trainingId = (int) $training['id'];
        $remaining = $trainings->remainingSeats($trainingId);

        return $this->view('public/home', [
            'title' => (string) ($training['meta_title'] ?? $training['title']),
            'description' => (string) ($training['meta_description'] ?? $training['summary'] ?? ''),
            'canonical' => url('/'),
            'activeNav' => 'accueil',
            'training' => $training,
            'descriptionHtml' => Markdown::toHtml((string) ($training['description'] ?? '')),
            'days' => $trainings->daysGrouped($trainingId),
            'remainingSeats' => $remaining,
            'capacity' => (int) $training['capacity'],
            'isOpen' => $trainings->isOpen($trainingId),
            'news' => (new NewsRepository())->published(3),
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::trainingJsonLd($training, $remaining)),
                SeoService::jsonLdTag([
                    '@context' => 'https://schema.org',
                    ...SeoService::organization(),
                ]),
            ],
        ]);
    }
}

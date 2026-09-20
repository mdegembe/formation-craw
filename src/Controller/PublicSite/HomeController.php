<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Locale;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Domain\Markdown;
use Craw\Repository\NewsRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\SeoService;

final class HomeController extends BaseController
{
    /** Point d'entree sans langue : redirige vers la langue preferee du visiteur. */
    public function redirectToLocale(Request $request, array $params): Response
    {
        $accepted = strtolower((string) $request->header('Accept-Language', ''));
        $locale = str_starts_with($accepted, 'en') ? 'en' : Locale::DEFAULT;

        return Response::redirect(url('/', $locale));
    }

    public function index(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound(t('notfound.training'));
        }

        $trainingId = (int) $training['id'];
        $remaining = $trainings->remainingSeats($trainingId);

        return $this->view('public/home', [
            'title' => (string) (localized_field($training, 'meta_title') ?? $training['title']),
            'description' => (string) (localized_field($training, 'meta_description') ?? localized_field($training, 'summary') ?? ''),
            'canonical' => url('/'),
            'activeNav' => 'accueil',
            'training' => $training,
            'descriptionHtml' => Markdown::toHtml((string) (localized_field($training, 'description') ?? '')),
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

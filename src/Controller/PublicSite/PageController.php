<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Locale;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Settings;
use Craw\Domain\Markdown;
use Craw\Repository\TrainingRepository;
use Craw\Service\SeoService;

final class PageController extends BaseController
{
    public function programme(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        $dateFormatter = Locale::current() === 'en' ? 'en_date' : 'fr_date';

        return $this->view('public/programme', [
            'title' => t('meta.programme_title'),
            'description' => t('meta.programme_description', [
                'start' => $dateFormatter((string) $training['starts_on']),
                'end' => $dateFormatter((string) $training['ends_on']),
                'location' => (string) $training['location_name'],
            ]),
            'canonical' => url('/programme'),
            'activeNav' => 'programme',
            'training' => $training,
            'days' => $trainings->daysGrouped((int) $training['id']),
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => t('nav.home'), 'url' => '/'],
                    ['label' => t('nav.programme'), 'url' => '/programme'],
                ])),
            ],
        ]);
    }

    public function practical(Request $request, array $params): Response
    {
        $trainings = new TrainingRepository();
        $training = $trainings->current();

        if ($training === null) {
            $this->notFound();
        }

        $faq = $this->faq($training);

        return $this->view('public/practical', [
            'title' => t('meta.practical_title'),
            'description' => t('meta.practical_description'),
            'canonical' => url('/infos-pratiques'),
            'activeNav' => 'infos',
            'training' => $training,
            'practicalHtml' => Markdown::toHtml((string) (localized_field($training, 'practical_info') ?? '')),
            'faq' => $faq,
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::faqJsonLd($faq)),
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => t('nav.home'), 'url' => '/'],
                    ['label' => t('nav.practical'), 'url' => '/infos-pratiques'],
                ])),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $training
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private function faq(array $training): array
    {
        $price = (int) $training['price_cents'];
        $deadlineDays = (int) Settings::get('payment.deadline_days', 15);

        return [
            [
                'question' => t('faq.account.question'),
                'answer' => t('faq.account.answer'),
            ],
            [
                'question' => t('faq.payment.question'),
                'answer' => $price <= 0
                    ? t('faq.payment.answer_free')
                    : t('faq.payment.answer', ['days' => $deadlineDays]),
            ],
            [
                'question' => t('faq.meals.question'),
                'answer' => t('faq.meals.answer'),
            ],
            [
                'question' => t('faq.diploma.question'),
                'answer' => t('faq.diploma.answer', [
                    'rate' => \Craw\Domain\AttendanceCalculator::format(Settings::minAttendanceRate()),
                ]),
            ],
            [
                'question' => t('faq.cancel.question'),
                'answer' => t('faq.cancel.answer'),
            ],
        ];
    }

    public function legal(Request $request, array $params): Response
    {
        return $this->view('public/legal', [
            'title' => t('meta.legal_title'),
            'description' => t('meta.legal_description'),
            'canonical' => url('/mentions-legales'),
        ]);
    }

    public function privacy(Request $request, array $params): Response
    {
        return $this->view('public/privacy', [
            'title' => t('meta.privacy_title'),
            'description' => t('meta.privacy_description'),
            'canonical' => url('/confidentialite'),
            'retentionYears' => (int) Settings::get('privacy.retention_years', 3),
        ]);
    }

    public function accessibility(Request $request, array $params): Response
    {
        return $this->view('public/accessibility', [
            'title' => t('meta.accessibility_title'),
            'description' => t('meta.accessibility_description'),
            'canonical' => url('/accessibilite'),
        ]);
    }
}

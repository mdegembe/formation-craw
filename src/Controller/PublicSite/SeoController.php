<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Repository\NewsRepository;
use Craw\Repository\TrainingRepository;

final class SeoController extends BaseController
{
    public function sitemap(Request $request, array $params): Response
    {
        $training = (new TrainingRepository())->current();
        $trainingUpdated = $training === null
            ? date('Y-m-d')
            : substr((string) ($training['updated_at'] ?? $training['created_at']), 0, 10);

        $entries = [
            ['/', '1.0', 'weekly', $trainingUpdated],
            ['/programme', '0.9', 'weekly', $trainingUpdated],
            ['/infos-pratiques', '0.8', 'monthly', $trainingUpdated],
            ['/inscription', '0.9', 'weekly', $trainingUpdated],
            ['/actualites', '0.7', 'weekly', date('Y-m-d')],
            ['/contact', '0.5', 'yearly', $trainingUpdated],
            ['/confidentialite', '0.3', 'yearly', $trainingUpdated],
            ['/accessibilite', '0.3', 'yearly', $trainingUpdated],
            ['/mentions-legales', '0.3', 'yearly', $trainingUpdated],
        ];

        foreach ((new NewsRepository())->published(200) as $article) {
            $entries[] = [
                '/actualites/' . (string) $article['slug'],
                '0.6',
                'monthly',
                substr((string) ($article['updated_at'] ?? $article['published_at']), 0, 10),
            ];
        }

        $urls = [];

        foreach ($entries as [$path, $priority, $frequency, $lastmod]) {
            $urls[] = sprintf(
                "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n"
                . "    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
                e(url($path)),
                e($lastmod),
                $frequency,
                $priority
            );
        }

        $xml = sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n%s\n</urlset>\n",
            implode("\n", $urls)
        );

        return Response::text($xml, 200, 'application/xml');
    }

    public function robots(Request $request, array $params): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Espaces privés et pages personnelles : jamais indexés.',
            'Disallow: /admin',
            'Disallow: /mon-inscription/',
            'Disallow: /inscription/paiement/',
            'Disallow: /inscription/recapitulatif/',
            'Disallow: /inscription/preferences/',
            'Disallow: /diplome/',
            'Disallow: /cron/',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
            '',
        ];

        return Response::text(implode("\n", $lines));
    }
}

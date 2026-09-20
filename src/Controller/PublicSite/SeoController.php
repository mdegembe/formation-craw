<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Locale;
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

        // Chaque page publique existe en francais et en anglais (voir Locale) :
        // les deux URLs sont listees, chacune renvoyant vers son alternative
        // par un lien hreflang, comme recommande par Google.
        $urls = [];

        foreach ($entries as [$path, $priority, $frequency, $lastmod]) {
            $alternates = '';
            foreach (Locale::SUPPORTED as $altLocale) {
                $alternates .= sprintf(
                    "\n    <xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\"/>",
                    $altLocale,
                    e(url($path, $altLocale))
                );
            }

            foreach (Locale::SUPPORTED as $locale) {
                $urls[] = sprintf(
                    "  <url>\n    <loc>%s</loc>%s\n    <lastmod>%s</lastmod>\n"
                    . "    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
                    e(url($path, $locale)),
                    $alternates,
                    e($lastmod),
                    $frequency,
                    $priority
                );
            }
        }

        $xml = sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" "
            . "xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n%s\n</urlset>\n",
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
            '# Espaces privés et pages personnelles : jamais indexés (les deux langues).',
            'Disallow: /admin',
            'Disallow: /cron/',
        ];

        $private = [
            '/mon-inscription/',
            '/inscription/paiement/',
            '/inscription/recapitulatif/',
            '/inscription/preferences/',
            '/diplome/',
        ];

        foreach (Locale::SUPPORTED as $locale) {
            foreach ($private as $path) {
                $lines[] = 'Disallow: /' . $locale . $path;
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        $lines[] = '';

        return Response::text(implode("\n", $lines));
    }
}

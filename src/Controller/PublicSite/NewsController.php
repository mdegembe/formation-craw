<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Domain\Markdown;
use Craw\Repository\NewsRepository;
use Craw\Service\SeoService;

final class NewsController extends BaseController
{
    private const PER_PAGE = 9;

    public function index(Request $request, array $params): Response
    {
        $repository = new NewsRepository();

        $page = max(1, $request->integer('page', 1));
        $total = $repository->countPublished();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        if ($page > $pages) {
            $page = $pages;
        }

        return $this->view('public/news_index', [
            'title' => t('meta.news_title'),
            'description' => t('meta.news_description'),
            'canonical' => url('/actualites' . ($page > 1 ? '?page=' . $page : '')),
            'activeNav' => 'actualites',
            'articles' => $repository->published(self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'page' => $page,
            'pages' => $pages,
            // Une page de liste paginee ne doit pas concurrencer la page 1.
            'noindex' => $page > 1,
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => t('nav.home'), 'url' => '/'],
                    ['label' => t('nav.news'), 'url' => '/actualites'],
                ])),
            ],
        ]);
    }

    public function show(Request $request, array $params): Response
    {
        $article = (new NewsRepository())->findPublishedBySlug((string) $params['slug']);

        if ($article === null) {
            $this->notFound(t('notfound.news_article'));
        }

        $title = (string) localized_field($article, 'title');
        $description = (string) (localized_field($article, 'meta_description') ?? localized_field($article, 'excerpt') ?? '');
        $body = (string) localized_field($article, 'body');

        if ($description === '') {
            $description = str_excerpt(Markdown::toText($body), 155);
        }

        return $this->view('public/news_show', [
            'title' => (string) ($article['meta_title'] ?? $title),
            'description' => $description,
            'canonical' => url('/actualites/' . (string) $article['slug']),
            'activeNav' => 'actualites',
            'ogImage' => $article['cover_path'] !== null
                ? url('/media/news/' . (string) $article['cover_path'])
                : url('assets/img/og-default.svg'),
            'article' => $article,
            'articleTitle' => $title,
            'bodyHtml' => Markdown::toHtml($body),
            'related' => array_slice(array_filter(
                (new NewsRepository())->published(4),
                static fn (array $item): bool => (int) $item['id'] !== (int) $article['id']
            ), 0, 3),
            'jsonLd' => [
                SeoService::jsonLdTag(SeoService::newsJsonLd($article)),
                SeoService::jsonLdTag(SeoService::breadcrumbJsonLd([
                    ['label' => t('nav.home'), 'url' => '/'],
                    ['label' => t('nav.news'), 'url' => '/actualites'],
                    ['label' => $title, 'url' => '/actualites/' . (string) $article['slug']],
                ])),
            ],
        ]);
    }

    public function rss(Request $request, array $params): Response
    {
        $articles = (new NewsRepository())->published(20);

        $items = [];

        foreach ($articles as $article) {
            $link = url('/actualites/' . (string) $article['slug']);

            $items[] = sprintf(
                "    <item>\n"
                . "      <title>%s</title>\n"
                . "      <link>%s</link>\n"
                . "      <guid isPermaLink=\"true\">%s</guid>\n"
                . "      <pubDate>%s</pubDate>\n"
                . "      <description>%s</description>\n"
                . "    </item>",
                e((string) $article['title']),
                e($link),
                e($link),
                date(DATE_RSS, strtotime((string) $article['published_at'])),
                e((string) ($article['excerpt'] ?? str_excerpt(Markdown::toText((string) $article['body']), 300)))
            );
        }

        $xml = sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n"
            . "  <channel>\n"
            . "    <title>Actualités — Formation CRA-W</title>\n"
            . "    <link>%s</link>\n"
            . "    <atom:link href=\"%s\" rel=\"self\" type=\"application/rss+xml\"/>\n"
            . "    <description>Actualités de la formation organisée par le Centre wallon de Recherches agronomiques.</description>\n"
            . "    <language>fr-be</language>\n"
            . "    <lastBuildDate>%s</lastBuildDate>\n%s\n"
            . "  </channel>\n"
            . "</rss>\n",
            e(url('/actualites')),
            e(url('/actualites/rss')),
            date(DATE_RSS),
            implode("\n", $items)
        );

        return Response::text($xml, 200, 'application/rss+xml');
    }
}

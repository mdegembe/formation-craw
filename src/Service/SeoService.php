<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Locale;
use Craw\Core\Settings;
use Craw\Domain\Markdown;

/**
 * Donnees structurees schema.org et metadonnees sociales.
 *
 * Objectif : rendre la formation eligible aux resultats enrichis « evenement »
 * de Google, et donner un partage social propre sur les reseaux.
 */
final class SeoService
{
    /**
     * Fiche EducationEvent, avec l'offre et le lieu.
     *
     * @param array<string, mixed> $training
     *
     * @return array<string, mixed>
     */
    public static function trainingJsonLd(array $training, int $remainingSeats): array
    {
        $isFree = (int) $training['price_cents'] <= 0;
        $subjectPending = (bool) $training['subject_pending'];

        $name = $subjectPending || ($training['subject'] ?? '') === ''
            ? (string) $training['title']
            : (string) localized_field($training, 'subject');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationEvent',
            'name' => $name,
            'description' => str_excerpt(
                Markdown::toText((string) (localized_field($training, 'summary') ?? localized_field($training, 'description') ?? '')),
                300
            ),
            'startDate' => (string) $training['starts_on'] . 'T09:00:00+01:00',
            'endDate' => (string) $training['ends_on'] . 'T17:00:00+01:00',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'inLanguage' => Locale::current() === 'en' ? 'en-US' : 'fr-BE',
            'url' => url('/'),
            'location' => [
                '@type' => 'Place',
                'name' => (string) $training['location_name'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => explode(',', (string) $training['location_address'])[0],
                    'postalCode' => '5030',
                    'addressLocality' => 'Gembloux',
                    'addressCountry' => 'BE',
                ],
            ],
            'organizer' => self::organization(),
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format((int) $training['price_cents'] / 100, 2, '.', ''),
                'priceCurrency' => (string) $training['currency'],
                'availability' => $remainingSeats > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/SoldOut',
                'url' => url('/inscription'),
                'validFrom' => substr((string) $training['created_at'], 0, 10),
            ],
        ];

        if (!empty($training['location_latitude']) && !empty($training['location_longitude'])) {
            $data['location']['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (string) $training['location_latitude'],
                'longitude' => (string) $training['location_longitude'],
            ];
        }

        if ($isFree) {
            $data['isAccessibleForFree'] = true;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            'name' => 'Centre wallon de Recherches agronomiques',
            'alternateName' => 'CRA-W',
            'url' => 'https://www.cra.wallonie.be/fr',
            'email' => (string) Settings::get('contact.email', ''),
            'telephone' => (string) Settings::get('contact.phone', ''),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Rue de Liroux 9',
                'postalCode' => '5030',
                'addressLocality' => 'Gembloux',
                'addressCountry' => 'BE',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $article
     *
     * @return array<string, mixed>
     */
    public static function newsJsonLd(array $article): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => str_excerpt((string) localized_field($article, 'title'), 110),
            'description' => (string) (localized_field($article, 'excerpt') ?? ''),
            'datePublished' => date('c', strtotime((string) $article['published_at'])),
            'dateModified' => date('c', strtotime((string) ($article['updated_at'] ?? $article['published_at']))),
            'inLanguage' => Locale::current() === 'en' ? 'en-US' : 'fr-BE',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url('/actualites/' . (string) $article['slug']),
            ],
            'publisher' => self::organization(),
        ];

        if (!empty($article['author_name'])) {
            $data['author'] = ['@type' => 'Person', 'name' => (string) $article['author_name']];
        } else {
            $data['author'] = self::organization();
        }

        if (!empty($article['cover_path'])) {
            $data['image'] = [url('/media/news/' . (string) $article['cover_path'])];
        }

        return $data;
    }

    /**
     * @param array<int, array{label: string, url: string}> $items
     *
     * @return array<string, mixed>
     */
    public static function breadcrumbJsonLd(array $items): array
    {
        $elements = [];

        foreach ($items as $index => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                'item' => url($item['url']),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * @param array<int, array{question: string, answer: string}> $entries
     *
     * @return array<string, mixed>
     */
    public static function faqJsonLd(array $entries): array
    {
        $questions = [];

        foreach ($entries as $entry) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $entry['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $entry['answer']],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Balise <script type="application/ld+json"> prete a inserer.
     *
     * @param array<string, mixed> $data
     */
    public static function jsonLdTag(array $data): string
    {
        return sprintf(
            '<script type="application/ld+json">%s</script>',
            (string) json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
            )
        );
    }
}

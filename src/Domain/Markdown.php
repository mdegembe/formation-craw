<?php

declare(strict_types=1);

namespace Craw\Domain;

/**
 * Sous-ensemble de Markdown volontairement restreint, rendu en HTML sur.
 *
 * Aucun HTML brut n'est accepte : le texte est echappe d'abord, puis seules les
 * constructions autorisees sont converties. Cela evite toute injection via le
 * corps d'une actualite.
 *
 * Supporte : titres (## ###), gras, italique, code en ligne, liens, listes a
 * puces et numerotees, citations, lignes horizontales, paragraphes.
 */
final class Markdown
{
    public static function toHtml(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);

        $html = [];
        $listType = null;
        $inQuote = false;
        $paragraph = [];

        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }

            $html[] = '<p>' . self::inline(implode(' ', $paragraph)) . '</p>';
            $paragraph = [];
        };

        $closeList = static function () use (&$listType, &$html): void {
            if ($listType !== null) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $listType = null;
            }
        };

        $closeQuote = static function () use (&$inQuote, &$html): void {
            if ($inQuote) {
                $html[] = '</blockquote>';
                $inQuote = false;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flushParagraph();
                $closeList();
                $closeQuote();
                continue;
            }

            // Ligne horizontale
            if (preg_match('/^(-{3,}|\*{3,})$/', $trimmed) === 1) {
                $flushParagraph();
                $closeList();
                $closeQuote();
                $html[] = '<hr>';
                continue;
            }

            // Titres : on demarre au niveau 2, le h1 etant celui de la page.
            if (preg_match('/^(#{2,4})\s+(.*)$/', $trimmed, $matches) === 1) {
                $flushParagraph();
                $closeList();
                $closeQuote();
                $level = strlen($matches[1]);
                $html[] = sprintf('<h%d>%s</h%d>', $level, self::inline($matches[2]), $level);
                continue;
            }

            // Citation
            if (preg_match('/^>\s?(.*)$/', $trimmed, $matches) === 1) {
                $flushParagraph();
                $closeList();
                if (!$inQuote) {
                    $html[] = '<blockquote>';
                    $inQuote = true;
                }
                $html[] = '<p>' . self::inline($matches[1]) . '</p>';
                continue;
            }

            // Liste a puces
            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $matches) === 1) {
                $flushParagraph();
                $closeQuote();
                if ($listType !== 'ul') {
                    $closeList();
                    $html[] = '<ul>';
                    $listType = 'ul';
                }
                $html[] = '<li>' . self::inline($matches[1]) . '</li>';
                continue;
            }

            // Liste numerotee
            if (preg_match('/^\d+[.)]\s+(.*)$/', $trimmed, $matches) === 1) {
                $flushParagraph();
                $closeQuote();
                if ($listType !== 'ol') {
                    $closeList();
                    $html[] = '<ol>';
                    $listType = 'ol';
                }
                $html[] = '<li>' . self::inline($matches[1]) . '</li>';
                continue;
            }

            $closeList();
            $closeQuote();
            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $closeList();
        $closeQuote();

        return implode("\n", $html);
    }

    /**
     * Rendu des constructions en ligne. Le texte est echappe en premier, de
     * sorte qu'aucun HTML de l'auteur ne puisse survivre.
     */
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Code en ligne : protege du reste des transformations.
        $codeBlocks = [];
        $text = (string) preg_replace_callback(
            '/`([^`]+)`/',
            static function (array $matches) use (&$codeBlocks): string {
                $codeBlocks[] = '<code>' . $matches[1] . '</code>';

                return "\u{0000}CODE" . (count($codeBlocks) - 1) . "\u{0000}";
            },
            $text
        );

        // Liens [texte](url) — schemas http, https et mailto uniquement.
        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $matches): string {
                $url = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

                if (preg_match('#^(https?://|mailto:|/)#i', $url) !== 1) {
                    return $matches[1];
                }

                $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $external = preg_match('#^https?://#i', $url) === 1;

                return sprintf(
                    '<a href="%s"%s>%s</a>',
                    $safeUrl,
                    $external ? ' target="_blank" rel="noopener noreferrer"' : '',
                    $matches[1]
                );
            },
            $text
        );

        $text = (string) preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = (string) preg_replace('/(?<![\w*])\*([^*\n]+)\*(?![\w*])/', '<em>$1</em>', $text);

        foreach ($codeBlocks as $index => $code) {
            $text = str_replace("\u{0000}CODE{$index}\u{0000}", $code, $text);
        }

        return $text;
    }

    /** Texte brut, pour les meta descriptions et les extraits. */
    public static function toText(string $markdown): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags(self::toHtml($markdown))));
    }
}

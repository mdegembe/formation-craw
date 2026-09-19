<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\View;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Rendu HTML vers PDF. Un pipeline unique pour tous les documents produits :
 * instructions de paiement, feuilles d'emargement, diplomes.
 */
final class PdfService
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromTemplate(
        string $template,
        array $data = [],
        string $paper = 'A4',
        string $orientation = 'portrait'
    ): string {
        return self::fromHtml(View::render($template, $data), $paper, $orientation);
    }

    public static function fromHtml(string $html, string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);       // aucun appel reseau depuis un PDF
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);          // jamais de PHP dans un gabarit PDF
        $options->set('defaultFont', 'DejaVu Sans');   // accents fiables
        $options->set('chroot', [base_path('storage'), base_path('public')]);
        $options->set('tempDir', storage_path('tmp'));
        $options->set('fontDir', storage_path('cache/fonts'));
        $options->set('fontCache', storage_path('cache/fonts'));

        foreach ([storage_path('tmp'), storage_path('cache/fonts')] as $directory) {
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * Concatene plusieurs documents HTML en un seul PDF, un par page.
     *
     * Utilise pour l'impression groupee des diplomes : un seul fichier a
     * envoyer a l'imprimante.
     *
     * @param array<int, string> $htmlPages Corps HTML de chaque page.
     */
    public static function combine(
        array $htmlPages,
        string $styles = '',
        string $paper = 'A4',
        string $orientation = 'landscape'
    ): string {
        $body = [];
        $lastIndex = count($htmlPages) - 1;

        foreach ($htmlPages as $index => $page) {
            $body[] = sprintf(
                '<div class="pdf-page"%s>%s</div>',
                $index < $lastIndex ? ' style="page-break-after: always;"' : '',
                $page
            );
        }

        $html = sprintf(
            '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
            . '<style>@page { margin: 0; } body { margin: 0; padding: 0; } %s</style>'
            . '</head><body>%s</body></html>',
            $styles,
            implode("\n", $body)
        );

        return self::fromHtml($html, $paper, $orientation);
    }
}

<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;

/**
 * Sert les images deposees par l'administrateur.
 *
 * Les fichiers vivent hors de la racine web : ils ne peuvent donc jamais etre
 * executes par le serveur, meme si un fichier deguise passait les controles.
 */
final class MediaController extends BaseController
{
    public function news(Request $request, array $params): Response
    {
        $file = (string) $params['file'];

        // Ceinture et bretelles : la route filtre deja les caracteres, on
        // verifie en plus qu'aucune remontee de repertoire n'est possible.
        if (basename($file) !== $file || str_contains($file, '..')) {
            $this->notFound();
        }

        $path = storage_path('uploads/news/' . $file);

        if (!is_file($path)) {
            $this->notFound('Image introuvable.');
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };

        if ($mime === null) {
            $this->notFound();
        }

        $etag = '"' . substr((string) md5_file($path), 0, 16) . '"';

        if ($request->header('If-None-Match') === $etag) {
            return new Response('', 304, ['ETag' => $etag]);
        }

        return Response::inline((string) file_get_contents($path), $file, $mime)
            ->withHeader('Cache-Control', 'public, max-age=2592000')
            ->withHeader('ETag', $etag);
    }
}

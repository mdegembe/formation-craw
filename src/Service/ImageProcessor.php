<?php

declare(strict_types=1);

namespace Craw\Service;

use RuntimeException;

/**
 * Traitement des images deposees par l'administrateur.
 *
 * L'image est systematiquement re-encodee : c'est la seule facon fiable de
 * garantir qu'un fichier deguise en image ne contient pas de charge utile.
 * Le nom de fichier est genere, jamais repris de l'envoi.
 */
final class ImageProcessor
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @param array<string, mixed> $file Entree de $_FILES
     *
     * @return array{path: string, width: int, height: int}
     */
    public static function store(
        array $file,
        string $destinationDirectory,
        int $maxWidth = 1600,
        int $maxHeight = 1600
    ): array {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($error));
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_readable($tmpName)) {
            throw new RuntimeException('Fichier téléversé illisible.');
        }

        // is_uploaded_file protege contre un chemin arbitraire injecte.
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier téléversé invalide.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('Le fichier dépasse la taille maximale autorisée (8 Mo).');
        }

        $info = @getimagesize($tmpName);

        if ($info === false) {
            throw new RuntimeException('Le fichier n\'est pas une image valide.');
        }

        $mime = (string) ($info['mime'] ?? '');

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new RuntimeException('Format non supporté. Utilisez JPEG, PNG ou WebP.');
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpName),
            'image/png' => @imagecreatefrompng($tmpName),
            'image/webp' => @imagecreatefromwebp($tmpName),
            default => false,
        };

        if ($source === false) {
            throw new RuntimeException('Impossible de décoder l\'image.');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);

            $ratio = min($maxWidth / $width, $maxHeight / $height, 1.0);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

            // Fond blanc : evite un aplat noir sur les PNG transparents en JPEG.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height
            );

            if (!is_dir($destinationDirectory)
                && !mkdir($destinationDirectory, 0775, true)
                && !is_dir($destinationDirectory)
            ) {
                throw new RuntimeException('Impossible de créer le répertoire de destination.');
            }

            $filename = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.jpg';
            $absolute = rtrim($destinationDirectory, '/') . '/' . $filename;

            if (!imagejpeg($canvas, $absolute, 82)) {
                throw new RuntimeException('Échec de l\'enregistrement de l\'image.');
            }

            imagedestroy($canvas);

            return ['path' => $filename, 'width' => $targetWidth, 'height' => $targetHeight];
        } finally {
            imagedestroy($source);
        }
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux.',
            UPLOAD_ERR_PARTIAL => 'Le téléversement a été interrompu.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été envoyé.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Erreur serveur lors de l\'enregistrement du fichier.',
            UPLOAD_ERR_EXTENSION => 'Le téléversement a été bloqué par le serveur.',
            default => 'Échec du téléversement.',
        };
    }
}

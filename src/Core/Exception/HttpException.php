<?php

declare(strict_types=1);

namespace Craw\Core\Exception;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $status,
        string $message = '',
        private readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'Requete invalide.',
            401 => 'Authentification requise.',
            403 => 'Acces refuse.',
            404 => 'Page introuvable.',
            405 => 'Methode non autorisee.',
            419 => 'Votre session a expire, veuillez recommencer.',
            429 => 'Trop de requetes. Merci de reessayer plus tard.',
            default => 'Une erreur est survenue.',
        };
    }
}

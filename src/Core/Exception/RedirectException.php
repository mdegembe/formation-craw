<?php

declare(strict_types=1);

namespace Craw\Core\Exception;

use RuntimeException;

/**
 * Permet a une methode utilitaire, appelee au fond d'un controleur, de
 * detourner la requete vers une autre URL sans avoir a remonter un Response
 * a travers toute la pile d'appels.
 */
final class RedirectException extends RuntimeException
{
    public function __construct(
        private readonly string $location,
        private readonly int $status = 302,
    ) {
        parent::__construct('Redirection vers ' . $location);
    }

    public function location(): string
    {
        return $this->location;
    }

    public function status(): int
    {
        return $this->status;
    }
}

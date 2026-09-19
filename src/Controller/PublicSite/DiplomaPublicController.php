<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Repository\DiplomaRepository;

/**
 * Verification publique d'un diplome a partir du QR code qu'il porte.
 *
 * La page ne divulgue que le strict necessaire pour attester l'authenticite :
 * nom du titulaire, formation, dates et numero. Aucune donnee de contact.
 */
final class DiplomaPublicController extends BaseController
{
    public function verify(Request $request, array $params): Response
    {
        $diploma = (new DiplomaRepository())->findByVerificationCode((string) $params['code']);

        return $this->view('public/diploma_verify', [
            'title' => $diploma === null ? 'Diplôme non reconnu' : 'Vérification d\'un diplôme',
            'description' => 'Vérification de l\'authenticité d\'un diplôme délivré par le CRA-W.',
            'canonical' => url('/'),
            'noindex' => true,
            'diploma' => $diploma,
        ], $diploma === null ? 404 : 200);
    }
}

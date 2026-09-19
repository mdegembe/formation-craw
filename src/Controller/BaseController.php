<?php

declare(strict_types=1);

namespace Craw\Controller;

use Craw\Core\Exception\HttpException;
use Craw\Core\Exception\RedirectException;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\View;

abstract class BaseController
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        // Les erreurs et valeurs de la requete precedente sont toujours
        // disponibles dans les vues, sans avoir a les passer a chaque fois.
        $data['errors'] ??= Session::takeErrors();
        $data['old'] ??= Session::takeOldInput();

        return Response::html(View::render($template, $data), $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    /**
     * Redirige en conservant les erreurs et les valeurs saisies.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed>  $input
     */
    protected function back(string $path, array $errors, array $input, ?string $message = null): Response
    {
        Session::flashErrors($errors);
        Session::flashInput($input);

        if ($message !== null) {
            Session::flash('error', $message);
        }

        return $this->redirect($path);
    }

    protected function notFound(string $message = 'Page introuvable.'): never
    {
        throw new HttpException(404, $message);
    }

    protected function forbidden(string $message = 'Accès refusé.'): never
    {
        throw new HttpException(403, $message);
    }

    protected function badRequest(string $message = 'Requête invalide.'): never
    {
        throw new HttpException(400, $message);
    }

    /** Detourne la requete depuis n'importe quelle profondeur d'appel. */
    protected function redirectAway(string $path): never
    {
        throw new RedirectException(url($path));
    }
}

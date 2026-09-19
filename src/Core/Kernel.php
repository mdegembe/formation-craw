<?php

declare(strict_types=1);

namespace Craw\Core;

use Craw\Core\Exception\HttpException;
use Craw\Core\Exception\RedirectException;
use Throwable;

/**
 * Point d'entree applicatif : en-tetes de securite, resolution de la route,
 * execution des middlewares, puis rendu de la reponse.
 */
final class Kernel
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $this->applySecurityHeaders();

            $match = $this->router->match($request->method, $request->path);

            foreach ($match['middleware'] as $middleware) {
                $response = $this->runMiddleware($middleware, $request);

                if ($response instanceof Response) {
                    return $response;
                }
            }

            // Toute requete d'ecriture est protegee contre le CSRF.
            Csrf::verify($request);

            [$class, $method] = $match['handler'];
            $controller = new $class();

            /** @var Response $response */
            $response = $controller->{$method}($request, $match['params']);

            return $response;
        } catch (RedirectException $exception) {
            return Response::redirect($exception->location(), $exception->status());
        } catch (HttpException $exception) {
            return $this->renderError($request, $exception->status(), $exception->getMessage(), $exception->headers());
        } catch (Throwable $exception) {
            Logger::exception($exception);

            $message = Config::isDebug()
                ? $exception->getMessage() . ' — ' . $exception->getFile() . ':' . $exception->getLine()
                : 'Une erreur interne est survenue. L\'équipe technique a été prévenue.';

            return $this->renderError($request, 500, $message);
        }
    }

    private function runMiddleware(string $name, Request $request): ?Response
    {
        return match ($name) {
            'auth' => $this->authMiddleware($request),
            'admin' => $this->adminMiddleware(),
            'throttle' => $this->throttleMiddleware($request),
            default => null,
        };
    }

    private function authMiddleware(Request $request): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        if ($request->wantsJson()) {
            throw new HttpException(401);
        }

        Session::flash('warning', 'Veuillez vous connecter pour accéder à cette page.');

        return Response::redirect(url('/admin/connexion'));
    }

    private function adminMiddleware(): ?Response
    {
        Auth::requireAdmin();

        return null;
    }

    private function throttleMiddleware(Request $request): ?Response
    {
        if (!$request->isPost()) {
            return null;
        }

        $key = 'throttle:' . $request->path . ':' . $request->ipHash();

        if (RateLimiter::tooManyAttempts($key, 10, 900)) {
            throw new HttpException(429, 'Trop de tentatives. Merci de patienter quelques minutes.');
        }

        RateLimiter::hit($key, 900);

        return null;
    }

    /**
     * En-tetes de securite.
     *
     * La CSP interdit tout script distant ; les quelques scripts en ligne du
     * site (application du theme avant rendu) sont autorises par 'unsafe-inline'
     * — acceptable ici car aucune donnee utilisateur n'est jamais interpolee
     * dans un bloc <script>.
     */
    private function applySecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "connect-src 'self'",
        ]);

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header_remove('X-Powered-By');

        if (Config::isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function renderError(Request $request, int $status, string $message, array $headers = []): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => $message], $status)->withHeader('X-Error', 'true');
        }

        $isAdmin = str_starts_with($request->path, '/admin');

        try {
            $html = View::render('public/error', [
                'status' => $status,
                'message' => $message,
                'title' => match ($status) {
                    404 => 'Page introuvable',
                    403 => 'Accès refusé',
                    419 => 'Session expirée',
                    429 => 'Trop de requêtes',
                    default => 'Erreur',
                },
                'isAdmin' => $isAdmin,
                'noindex' => true,
            ]);
        } catch (Throwable) {
            $html = sprintf(
                '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur %d</title></head>'
                . '<body><h1>Erreur %d</h1><p>%s</p></body></html>',
                $status,
                $status,
                e($message)
            );
        }

        $response = Response::html($html, $status);

        foreach ($headers as $name => $value) {
            $response->withHeader($name, $value);
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Craw\Core;

use Craw\Core\Exception\HttpException;

/**
 * Routeur a table explicite. Les segments dynamiques s'ecrivent {nom} ou
 * {nom:regex}. Chaque route peut porter des middlewares nommes.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: array{0: class-string, 1: string}, middleware: array<int, string>, name: string|null}> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<int, string>                $middleware
     */
    public function add(
        string $method,
        string $pattern,
        array $handler,
        array $middleware = [],
        ?string $name = null
    ): self {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->compile($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
            'name' => $name,
        ];

        return $this;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<int, string>                $middleware
     */
    public function get(string $pattern, array $handler, array $middleware = [], ?string $name = null): self
    {
        return $this->add('GET', $pattern, $handler, $middleware, $name);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<int, string>                $middleware
     */
    public function post(string $pattern, array $handler, array $middleware = [], ?string $name = null): self
    {
        return $this->add('POST', $pattern, $handler, $middleware, $name);
    }

    /**
     * Compile un motif de route en expression reguliere.
     *
     * La contrainte d'un segment peut contenir ses propres accolades de
     * quantificateur — {token:[a-f0-9]{64}} — : elles sont donc reconnues
     * explicitement, faute de quoi la contrainte serait tronquee a la
     * premiere accolade fermante.
     */
    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::((?:[^{}]|\{\d+(?:,\d*)?\})+))?\}/',
            static function (array $matches): string {
                $name = $matches[1];
                $constraint = ($matches[2] ?? '') !== '' ? $matches[2] : '[^/]+';

                return sprintf('(?P<%s>%s)', $name, $constraint);
            },
            $pattern
        );

        $compiled = '#^' . $regex . '$#u';

        // Un motif invalide est une erreur de programmation : mieux vaut le
        // detecter au demarrage qu'obtenir un 404 inexplicable en production.
        if (@preg_match($compiled, '') === false) {
            throw new \RuntimeException(sprintf('Motif de route invalide : "%s".', $pattern));
        }

        return $compiled;
    }

    /**
     * @return array{handler: array{0: class-string, 1: string}, params: array<string, string>, middleware: array<int, string>}
     *
     * @throws HttpException
     */
    public function match(string $method, string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            return [
                'handler' => $route['handler'],
                'params' => $params,
                'middleware' => $route['middleware'],
            ];
        }

        if ($allowedMethods !== []) {
            throw new HttpException(405, 'Methode non autorisee.', ['Allow' => implode(', ', array_unique($allowedMethods))]);
        }

        throw new HttpException(404, 'Page introuvable.');
    }

    /**
     * @return array<int, array{method: string, pattern: string, regex: string, handler: array{0: class-string, 1: string}, middleware: array<int, string>, name: string|null}>
     */
    public function routes(): array
    {
        return $this->routes;
    }
}

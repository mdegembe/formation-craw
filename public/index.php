<?php

declare(strict_types=1);

/**
 * Front controller : unique fichier PHP expose par le serveur web.
 */

use Craw\Core\Kernel;
use Craw\Core\Request;
use Craw\Core\Session;

/*
 * Serveur de developpement intégré (php -S ... public/index.php) : ce script
 * est appele pour CHAQUE requete, y compris les fichiers statiques. En
 * retournant false, on laisse le serveur servir le fichier lui-meme, comme le
 * fait la regle de reecriture d'Apache en production.
 */
if (PHP_SAPI === 'cli-server') {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    if (is_string($path) && $path !== '/' && !str_contains($path, '..')) {
        $file = __DIR__ . '/' . ltrim($path, '/');

        if (is_file($file)) {
            return false;
        }
    }
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

Session::start();

// Tampon de sortie : permet a Response::send() d'ecarter toute sortie parasite
// qui corromprait une reponse binaire (QR code, PDF, CSV).
ob_start();

/** @var \Craw\Core\Router $router */
$router = require dirname(__DIR__) . '/src/routes.php';

(new Kernel($router))
    ->handle(Request::capture())
    ->send();

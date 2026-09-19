<?php

declare(strict_types=1);

/**
 * Front controller : unique fichier PHP expose par le serveur web.
 */

use Craw\Core\Kernel;
use Craw\Core\Request;
use Craw\Core\Session;

require_once dirname(__DIR__) . '/src/bootstrap.php';

Session::start();

/** @var \Craw\Core\Router $router */
$router = require dirname(__DIR__) . '/src/routes.php';

(new Kernel($router))
    ->handle(Request::capture())
    ->send();

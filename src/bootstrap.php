<?php

declare(strict_types=1);

use Craw\Core\Config;
use Craw\Core\Logger;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Config::load(dirname(__DIR__));

date_default_timezone_set(Config::get('APP_TIMEZONE', 'Europe/Brussels') ?? 'Europe/Brussels');

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

/*
 * Les depreciations sont exclues du rapport d'erreurs.
 *
 * Elles proviennent des dependances, pas du code applicatif, et certaines sont
 * emises a la compilation d'un fichier : elles echappent alors au gestionnaire
 * d'erreurs et s'inserteraient dans la reponse, corrompant une image ou un PDF.
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// En production, aucune erreur n'est affichee : elles partent dans le journal.
ini_set('display_errors', Config::isDebug() ? '1' : '0');

ini_set('log_errors', '1');

/**
 * Les avertissements et erreurs du code applicatif deviennent des exceptions :
 * mieux vaut un echec net qu'une donnee silencieusement fausse.
 *
 * Les depreciations font exception : elles proviennent presque toujours d'une
 * dependance qui n'a pas encore rattrape la derniere version de PHP, et ne
 * doivent pas interrompre une requete. Elles sont journalisees.
 */
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }

    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        Logger::log('deprecated', $message, ['file' => $file . ':' . $line]);

        return true;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        Logger::error('Erreur fatale : ' . $error['message'], [
            'file' => $error['file'] . ':' . $error['line'],
        ]);
    }
});

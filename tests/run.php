<?php

declare(strict_types=1);

/**
 * Lanceur de tests.
 *
 *   php tests/run.php            tests du domaine uniquement
 *   php tests/run.php --http     ajoute les tests fonctionnels HTTP
 *
 * Les tests HTTP demarrent leur propre serveur et leur propre base SQLite :
 * ils ne touchent jamais aux donnees de developpement.
 */

use Craw\Tests\TestCase;

if (PHP_SAPI !== 'cli') {
    exit("Les tests s'executent en ligne de commande.\n");
}

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

$withHttp = in_array('--http', $argv, true);

fwrite(STDOUT, "\n\033[1m═══ Formation CRA-W — suite de tests ═══\033[0m\n");

require __DIR__ . '/domain_test.php';

if ($withHttp) {
    require __DIR__ . '/http_test.php';
} else {
    fwrite(STDOUT, "\n  \033[33mTests HTTP ignorés (relancer avec --http).\033[0m\n");
}

exit(TestCase::summary());

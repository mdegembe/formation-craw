<?php

declare(strict_types=1);

/**
 * Console d'administration.
 *
 *   php bin/console.php migrate            Applique les migrations en attente
 *   php bin/console.php migrate:fresh      Recree la base (destructif)
 *   php bin/console.php seed [--demo]      Charge les donnees initiales
 *   php bin/console.php create-admin       Cree un compte administrateur
 *   php bin/console.php cron               Relances, envois, purges
 *   php bin/console.php mail:flush         Vide la file d'attente des courriels
 *   php bin/console.php routes             Liste les routes declarees
 */

use Craw\Core\Database;
use Craw\Core\Migrator;
use Craw\Core\Settings;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\MailQueue;
use Craw\Service\PaymentService;

if (PHP_SAPI !== 'cli') {
    exit("Cette commande doit etre lancee en ligne de commande.\n");
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$command = $argv[1] ?? 'help';
$options = array_slice($argv, 2);

/** @return never */
function fail(string $message): void
{
    fwrite(STDERR, "\033[31m✗ {$message}\033[0m\n");
    exit(1);
}

function done(string $message): void
{
    fwrite(STDOUT, "\033[32m✓ {$message}\033[0m\n");
}

function info(string $message): void
{
    fwrite(STDOUT, "  {$message}\n");
}

try {
    switch ($command) {
        case 'migrate':
            $count = Migrator::migrate();
            done($count === 0 ? 'Base deja a jour.' : "{$count} migration(s) appliquee(s).");
            break;

        case 'migrate:fresh':
            if (!in_array('--force', $options, true)) {
                fail('Commande destructive. Relancez avec --force pour confirmer.');
            }
            $count = Migrator::fresh();
            done("Base recreee, {$count} migration(s) appliquee(s).");
            break;

        case 'seed':
            Migrator::migrate(false);
            $seeder = require dirname(__DIR__) . '/database/seeds/seed.php';
            $seeder(in_array('--demo', $options, true));
            done('Donnees initiales chargees.');

            if (in_array('--demo', $options, true)) {
                info('Jeu de demonstration inclus (24 inscriptions, paiements et absences).');
            }
            break;

        case 'create-admin':
            $email = $options[0] ?? null;
            $name = $options[1] ?? null;
            $password = $options[2] ?? null;

            if ($email === null) {
                fwrite(STDOUT, 'Adresse e-mail : ');
                $email = trim((string) fgets(STDIN));
            }

            if ($name === null) {
                fwrite(STDOUT, 'Nom affiche : ');
                $name = trim((string) fgets(STDIN));
            }

            if ($password === null) {
                fwrite(STDOUT, 'Mot de passe (min. 12 caracteres) : ');
                $password = trim((string) fgets(STDIN));
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                fail('Adresse e-mail invalide.');
            }

            if (strlen($password) < 12) {
                fail('Le mot de passe doit comporter au moins 12 caracteres.');
            }

            $existing = Database::selectOne('SELECT id FROM admin_users WHERE email = :email', [
                'email' => mb_strtolower($email),
            ]);

            if ($existing !== null) {
                Database::update('admin_users', [
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'display_name' => $name,
                    'is_active' => 1,
                    'failed_attempts' => 0,
                    'locked_until' => null,
                ], ['id' => (int) $existing['id']]);

                done("Compte mis a jour : {$email}");
                break;
            }

            Database::insert('admin_users', [
                'email' => mb_strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $name,
                'role' => 'admin',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            done("Compte administrateur cree : {$email}");
            break;

        case 'cron':
            $trainings = new TrainingRepository();
            $training = $trainings->current();

            if ($training !== null) {
                $result = (new PaymentService())->queueReminders((int) $training['id']);
                info("Relances de paiement mises en file : {$result['reminders']}");
            }

            $purged = (new RegistrationRepository())->purgeExpiredDrafts();
            info("Brouillons expires supprimes : {$purged}");

            $mail = MailQueue::process(50);
            info("Courriels envoyes : {$mail['sent']}, en echec definitif : {$mail['failed']}");

            Database::run('DELETE FROM rate_limits WHERE expires_at < :now', ['now' => date('Y-m-d H:i:s')]);

            done('Taches planifiees terminees.');
            break;

        case 'mail:flush':
            $result = MailQueue::process(200);
            done("Courriels envoyes : {$result['sent']}, en echec : {$result['failed']}");
            break;

        case 'routes':
            $router = require dirname(__DIR__) . '/src/routes.php';

            foreach ($router->routes() as $route) {
                fwrite(STDOUT, sprintf(
                    "  %-7s %-45s %s::%s\n",
                    $route['method'],
                    $route['pattern'],
                    $route['handler'][0],
                    $route['handler'][1]
                ));
            }
            break;

        case 'settings':
            foreach (Settings::DEFAULTS as $key => [$default, $type]) {
                $value = Settings::get($key);
                $display = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                fwrite(STDOUT, sprintf("  %-38s %s\n", $key, $display));
            }
            break;

        case 'help':
        default:
            fwrite(STDOUT, <<<'TXT'

  Console — Formation CRA-W

    migrate                     Applique les migrations en attente
    migrate:fresh --force       Recree la base (DESTRUCTIF)
    seed [--demo]               Charge les donnees initiales
    create-admin [email nom mdp]  Cree ou met a jour un administrateur
    cron                        Relances, envois de courriels, purges
    mail:flush                  Vide la file d'attente des courriels
    routes                      Liste les routes declarees
    settings                    Affiche les parametres metier


TXT);
            break;
    }
} catch (\Throwable $exception) {
    fail($exception->getMessage() . "\n  " . $exception->getFile() . ':' . $exception->getLine());
}

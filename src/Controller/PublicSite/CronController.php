<?php

declare(strict_types=1);

namespace Craw\Controller\PublicSite;

use Craw\Controller\BaseController;
use Craw\Core\Config;
use Craw\Core\Database;
use Craw\Core\Logger;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;
use Craw\Service\MailQueue;
use Craw\Service\PaymentService;

/**
 * Declenchement des taches planifiees par appel HTTP, pour les hebergements
 * mutualises ou l'on ne dispose pas de cron systeme.
 *
 * Protege par un jeton secret compare a temps constant.
 */
final class CronController extends BaseController
{
    public function run(Request $request, array $params): Response
    {
        $expected = (string) Config::get('CRON_TOKEN', '');
        $provided = (string) $params['token'];

        if ($expected === '' || !hash_equals($expected, $provided)) {
            Logger::warning('Tentative d\'appel du cron avec un jeton invalide.', ['ip' => $request->ipHash()]);

            $this->notFound();
        }

        $report = [
            'reminders' => 0,
            'drafts_purged' => 0,
            'mails_sent' => 0,
            'mails_failed' => 0,
        ];

        $training = (new TrainingRepository())->current();

        if ($training !== null) {
            $report['reminders'] = (new PaymentService())->queueReminders((int) $training['id'])['reminders'];
        }

        $report['drafts_purged'] = (new RegistrationRepository())->purgeExpiredDrafts();

        $mail = MailQueue::process(50);
        $report['mails_sent'] = $mail['sent'];
        $report['mails_failed'] = $mail['failed'];

        Database::run('DELETE FROM rate_limits WHERE expires_at < :now', ['now' => date('Y-m-d H:i:s')]);

        Logger::info('Taches planifiees executees.', $report);

        return Response::json(['status' => 'ok', 'report' => $report])
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Robots-Tag', 'noindex');
    }
}

<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Database;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Settings;
use Craw\Repository\PaymentRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Service\AttendanceService;
use Craw\Service\MailQueue;
use Craw\Service\StatisticsService;

final class DashboardController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $registrations = new RegistrationRepository();
        $counters = $registrations->dashboardCounters($trainingId);
        $payments = (new PaymentRepository())->totals($trainingId);
        $diet = (new StatisticsService())->headline($trainingId);

        $capacity = (int) $training['capacity'];
        $fillRate = $capacity > 0 ? min(100, (int) round($counters['active'] / $capacity * 100)) : 0;

        // Le bloc « presences » n'a de sens qu'une fois la formation commencee.
        $hasStarted = strtotime((string) $training['starts_on']) <= time();

        return $this->adminView('admin/dashboard', [
            'title' => 'Tableau de bord — Administration',
            'activeAdminNav' => 'dashboard',
            'training' => $training,
            'counters' => $counters,
            'payments' => $payments,
            'diet' => $diet,
            'capacity' => $capacity,
            'fillRate' => $fillRate,
            'remainingSeats' => $this->trainings->remainingSeats($trainingId),
            'hasStarted' => $hasStarted,
            'attendance' => $hasStarted ? (new AttendanceService())->statistics($trainingId) : null,
            'minRate' => Settings::minAttendanceRate(),
            'pendingMails' => MailQueue::pendingCount(),
            'recent' => Database::select(
                "SELECT r.id, r.reference, r.first_name, r.last_name, r.organisation,
                        r.status, r.created_at, p.status AS payment_status
                 FROM registrations r
                 LEFT JOIN payments p ON p.registration_id = r.id
                 WHERE r.training_id = :training AND r.status <> 'draft'
                 ORDER BY r.created_at DESC LIMIT 8",
                ['training' => $trainingId]
            ),
            'unhandledMessages' => (int) Database::scalar(
                'SELECT COUNT(*) FROM contact_messages WHERE handled_at IS NULL'
            ),
        ]);
    }
}

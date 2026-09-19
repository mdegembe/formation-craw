<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Settings;
use Craw\Domain\SepaQrPayload;

final class SettingsController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();

        return $this->adminView('admin/settings', [
            'title' => 'Configuration — Administration',
            'activeAdminNav' => 'settings',
            'training' => $training,
            'days' => $this->trainings->days((int) $training['id']),
            'minRate' => Settings::minAttendanceRate(),
        ]);
    }

    public function save(Request $request, array $params): Response
    {
        Auth::requireAdmin();

        $training = $this->training();
        $trainingId = (int) $training['id'];

        // ------------------------------------------------------- formation
        $startsOn = (string) ($request->input('starts_on') ?? $training['starts_on']);
        $endsOn = (string) ($request->input('ends_on') ?? $training['ends_on']);

        if (strtotime($endsOn) < strtotime($startsOn)) {
            Session::flash('error', 'La date de fin ne peut pas précéder la date de début.');

            return $this->redirect('/admin/configuration');
        }

        $priceInput = str_replace([' ', ','], ['', '.'], (string) ($request->input('price') ?? '0'));
        $priceCents = is_numeric($priceInput) ? (int) round(((float) $priceInput) * 100) : (int) $training['price_cents'];

        $subjectPending = $request->boolean('subject_pending');
        $subject = trim((string) ($request->input('subject') ?? ''));

        $this->trainings->update($trainingId, [
            'title' => mb_substr((string) ($request->input('title') ?? $training['title']), 0, 190),
            'subject' => $subject === '' ? null : mb_substr($subject, 0, 190),
            // Un sujet vide vaut « en cours de definition », quoi qu'indique la case.
            'subject_pending' => ($subjectPending || $subject === '') ? 1 : 0,
            'summary' => $request->input('summary'),
            'description' => $request->input('description'),
            'practical_info' => $request->input('practical_info'),
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'location_name' => mb_substr((string) ($request->input('location_name') ?? ''), 0, 190),
            'location_address' => mb_substr((string) ($request->input('location_address') ?? ''), 0, 255),
            'price_cents' => max(0, $priceCents),
            'capacity' => max(1, $request->integer('capacity', (int) $training['capacity'])),
            'registration_deadline' => $request->input('registration_deadline') ?: null,
            'status' => in_array((string) $request->input('status'), ['draft', 'open', 'closed', 'archived'], true)
                ? (string) $request->input('status')
                : (string) $training['status'],
            'meta_title' => $request->input('meta_title'),
            'meta_description' => $request->input('meta_description'),
        ]);

        // ------------------------------------------------------- parametres
        // Le seuil est saisi en pourcentage : plus parlant pour l'administrateur.
        $threshold = (float) str_replace(',', '.', (string) ($request->input('min_attendance') ?? '80'));
        $threshold = max(0.0, min(100.0, $threshold)) / 100;
        Settings::set('diploma.min_attendance', $threshold, 'float');

        Settings::set('diploma.number_prefix', mb_substr(
            (string) ($request->input('diploma_prefix') ?? 'CRAW'),
            0,
            10
        ), 'string');

        Settings::set(
            'attendance.justified_counts_present',
            $request->boolean('justified_counts_present'),
            'bool'
        );

        $iban = (string) ($request->input('iban') ?? '');

        if ($iban !== '' && !SepaQrPayload::isValidIban($iban)) {
            Session::flash(
                'warning',
                'L\'IBAN saisi est enregistré mais ne passe pas la validation (clé de contrôle). '
                . 'Le QR code de paiement ne sera pas proposé tant qu\'il n\'est pas corrigé.'
            );
        }

        Settings::set('payment.iban', SepaQrPayload::normaliseIban($iban), 'string');
        Settings::set('payment.bic', strtoupper(trim((string) ($request->input('bic') ?? ''))), 'string');
        Settings::set('payment.beneficiary', (string) ($request->input('beneficiary') ?? ''), 'string');
        Settings::set('payment.deadline_days', max(1, $request->integer('deadline_days', 15)), 'int');
        Settings::set('payment.reminder_days', (string) ($request->input('reminder_days') ?? '7,3'), 'string');

        Settings::set('contact.email', (string) ($request->input('contact_email') ?? ''), 'string');
        Settings::set('contact.phone', (string) ($request->input('contact_phone') ?? ''), 'string');
        Settings::set('privacy.retention_years', max(1, $request->integer('retention_years', 3)), 'int');
        Settings::set('seo.default_description', (string) ($request->input('seo_description') ?? ''), 'string');

        Audit::record('settings.save', 'training', $trainingId, [
            'min_attendance' => $threshold,
            'price_cents' => $priceCents,
        ], $request->ipHash());

        Session::flash('success', 'Configuration enregistrée.');

        return $this->redirect('/admin/configuration');
    }

    /** Edition du programme : intitule, intervenant et horaires de chaque creneau. */
    public function saveProgramme(Request $request, array $params): Response
    {
        $training = $this->training();
        $days = $this->trainings->days((int) $training['id']);

        $updated = 0;

        foreach ($days as $day) {
            $id = (int) $day['id'];

            $topic = $request->input('topic_' . $id);
            $speaker = $request->input('speaker_' . $id);
            $startsAt = $request->input('starts_at_' . $id);
            $endsAt = $request->input('ends_at_' . $id);

            $this->trainings->updateDay($id, [
                'topic' => $topic === null || $topic === '' ? null : mb_substr($topic, 0, 190),
                'speaker' => $speaker === null || $speaker === '' ? null : mb_substr($speaker, 0, 190),
                'starts_at' => preg_match('/^\d{2}:\d{2}$/', (string) $startsAt) === 1 ? $startsAt : $day['starts_at'],
                'ends_at' => preg_match('/^\d{2}:\d{2}$/', (string) $endsAt) === 1 ? $endsAt : $day['ends_at'],
                'counts_for_attendance' => $request->boolean('counts_' . $id) ? 1 : 0,
            ]);

            $updated++;
        }

        Audit::record('settings.programme', 'training', (int) $training['id'], [
            'slots' => $updated,
        ], $request->ipHash());

        Session::flash('success', sprintf('Programme enregistré (%d créneaux).', $updated));

        return $this->redirect('/admin/configuration');
    }
}

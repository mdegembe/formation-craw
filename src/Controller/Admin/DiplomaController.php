<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Settings;
use Craw\Repository\DiplomaRepository;
use Craw\Service\AttendanceService;
use Craw\Service\DiplomaService;
use Craw\Service\ExportService;
use Craw\Service\ImageProcessor;

final class DiplomaController extends AdminController
{
    public function index(Request $request, array $params): Response
    {
        $training = $this->training();
        $trainingId = (int) $training['id'];

        $repository = new DiplomaRepository();
        $summary = (new AttendanceService())->summary($trainingId);
        $existing = $repository->latestMapForTraining($trainingId);

        $eligible = array_values(array_filter($summary, static fn (array $r): bool => $r['eligible'] === true));
        $generated = array_values(array_filter(
            $summary,
            static fn (array $r): bool => isset($existing[(int) $r['id']])
        ));

        return $this->adminView('admin/diplomas', [
            'title' => 'Diplômes — Administration',
            'activeAdminNav' => 'diplomas',
            'summary' => $summary,
            'diplomas' => $existing,
            'eligibleCount' => count($eligible),
            'generatedCount' => count($generated),
            'pendingCount' => count($eligible) - count(array_filter(
                $eligible,
                static fn (array $r): bool => isset($existing[(int) $r['id']])
            )),
            'template' => $repository->activeTemplate(),
            'minRate' => Settings::minAttendanceRate(),
        ]);
    }

    public function generate(Request $request, array $params): Response
    {
        $training = $this->training();
        $regenerate = $request->boolean('regenerate');

        $result = (new DiplomaService())->generateBatch((int) $training['id'], Auth::id(), $regenerate);

        if ($result['generated'] === 0 && $result['errors'] === []) {
            Session::flash('info', 'Aucun nouveau diplôme à générer : tous les participants éligibles en ont déjà un.');
        } else {
            Session::flash('success', sprintf(
                '%d diplôme(s) généré(s). %d participant(s) non éligible(s) ou déjà pourvu(s).',
                $result['generated'],
                $result['skipped']
            ));
        }

        if ($result['errors'] !== []) {
            Session::flash('error', sprintf(
                '%d diplôme(s) n\'ont pas pu être générés. Consultez le journal applicatif.',
                count($result['errors'])
            ));
        }

        return $this->redirect('/admin/diplomes');
    }

    public function send(Request $request, array $params): Response
    {
        $ok = (new DiplomaService())->sendByEmail((int) $params['id']);

        Session::flash(
            $ok ? 'success' : 'error',
            $ok
                ? 'Le diplôme a été mis en file d\'envoi avec le PDF en pièce jointe.'
                : 'Diplôme introuvable ou fichier PDF manquant.'
        );

        return $this->redirect('/admin/diplomes');
    }

    /** Envoie par courriel tous les diplomes generes et non encore envoyes. */
    public function sendAll(Request $request, array $params): Response
    {
        $training = $this->training();
        $repository = new DiplomaRepository();
        $service = new DiplomaService();

        $resend = $request->boolean('resend');
        $sent = 0;

        foreach ($repository->latestMapForTraining((int) $training['id']) as $diploma) {
            if (!$resend && $diploma['sent_at'] !== null) {
                continue;
            }

            if ($service->sendByEmail((int) $diploma['id'])) {
                $sent++;
            }
        }

        Session::flash(
            $sent > 0 ? 'success' : 'info',
            $sent > 0
                ? sprintf('%d diplôme(s) mis en file d\'envoi.', $sent)
                : 'Aucun diplôme à envoyer : ils ont tous déjà été transmis.'
        );

        return $this->redirect('/admin/diplomes');
    }

    /** Un seul PDF reprenant tous les diplomes, pour l'impression en serie. */
    public function printBundle(Request $request, array $params): Response
    {
        $training = $this->training();

        try {
            $pdf = (new DiplomaService())->printBundle((int) $training['id']);
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());

            return $this->redirect('/admin/diplomes');
        }

        Audit::record('diploma.print_bundle', 'training', (int) $training['id'], [], $request->ipHash());

        return Response::inline(
            $pdf,
            ExportService::filename('diplomes-impression', 'pdf'),
            'application/pdf'
        );
    }

    public function download(Request $request, array $params): Response
    {
        $diploma = \Craw\Core\Database::selectOne(
            'SELECT * FROM diplomas WHERE id = :id',
            ['id' => (int) $params['id']]
        );

        if ($diploma === null) {
            $this->notFound('Diplôme introuvable.');
        }

        $path = storage_path((string) $diploma['file_path']);

        if (!is_file($path)) {
            $this->notFound('Le fichier PDF du diplôme est introuvable.');
        }

        return Response::inline(
            (string) file_get_contents($path),
            sprintf('diplome-%s.pdf', (string) $diploma['number']),
            'application/pdf'
        );
    }

    // ------------------------------------------------------------ gabarits

    public function templateForm(Request $request, array $params): Response
    {
        $repository = new DiplomaRepository();

        return $this->adminView('admin/diploma_template', [
            'title' => 'Modèle de diplôme — Administration',
            'activeAdminNav' => 'diplomas',
            'templates' => $repository->templates(),
            'active' => $repository->activeTemplate(),
            'placeholders' => DiplomaService::PLACEHOLDERS,
        ]);
    }

    public function templateSave(Request $request, array $params): Response
    {
        $repository = new DiplomaRepository();

        $mode = (string) ($request->input('mode') ?? 'background');

        if (!in_array($mode, ['background', 'html'], true)) {
            $this->badRequest('Mode de gabarit inconnu.');
        }

        $name = trim((string) ($request->input('name') ?? ''));

        if ($name === '') {
            Session::flash('error', 'Donnez un nom à ce modèle.');

            return $this->redirect('/admin/diplomes/modele');
        }

        $orientation = (string) ($request->input('orientation') ?? 'landscape');
        $orientation = in_array($orientation, ['landscape', 'portrait'], true) ? $orientation : 'landscape';

        $data = [
            'name' => mb_substr($name, 0, 150),
            'mode' => $mode,
            'orientation' => $orientation,
            'uploaded_by' => Auth::id(),
        ];

        if ($mode === 'html') {
            $html = (string) ($request->input('html_body') ?? '');

            if (trim($html) === '') {
                Session::flash('error', 'Le gabarit HTML ne peut pas être vide.');

                return $this->redirect('/admin/diplomes/modele');
            }

            // Un gabarit ne doit jamais pouvoir executer de script ni appeler
            // une ressource distante : on retire les balises dangereuses.
            $html = (string) preg_replace(
                '#<\s*(script|iframe|object|embed|link|meta)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
                '',
                $html
            );
            $html = (string) preg_replace('#<\s*(script|iframe|object|embed|link|meta)\b[^>]*/?>#i', '', $html);

            $data['html_body'] = $html;
            $data['field_layout'] = null;
        } else {
            $file = $request->file('background');

            if ($file !== null) {
                try {
                    $stored = ImageProcessor::store(
                        $file,
                        storage_path('uploads/templates'),
                        2480, // A4 paysage a 210 dpi : suffisant pour l'impression
                        2480
                    );
                    $data['background_path'] = 'uploads/templates/' . $stored['path'];
                } catch (\RuntimeException $exception) {
                    Session::flash('error', $exception->getMessage());

                    return $this->redirect('/admin/diplomes/modele');
                }
            }

            $layout = $this->parseLayout($request);
            $data['field_layout'] = (string) json_encode($layout, JSON_UNESCAPED_UNICODE);
            $data['html_body'] = null;
        }

        $templateId = $request->integer('template_id');

        if ($templateId > 0 && $repository->findTemplate($templateId) !== null) {
            // Sans nouveau fichier, on conserve le fond deja en place.
            if (!isset($data['background_path'])) {
                unset($data['background_path']);
            }

            $repository->updateTemplate($templateId, $data);
            $id = $templateId;
        } else {
            if ($mode === 'background' && !isset($data['background_path'])) {
                Session::flash('error', 'Déposez une image de fond pour créer un modèle.');

                return $this->redirect('/admin/diplomes/modele');
            }

            $id = $repository->createTemplate($data);
        }

        $repository->activateTemplate($id);

        Audit::record('diploma.template_save', 'diploma_template', $id, [
            'mode' => $mode,
        ], $request->ipHash());

        Session::flash('success', 'Modèle enregistré et activé. Prévisualisez-le avant de générer les diplômes.');

        return $this->redirect('/admin/diplomes/modele');
    }

    public function templateActivate(Request $request, array $params): Response
    {
        $repository = new DiplomaRepository();
        $id = (int) $params['id'];

        if ($repository->findTemplate($id) === null) {
            $this->notFound('Modèle introuvable.');
        }

        $repository->activateTemplate($id);
        Session::flash('success', 'Modèle activé.');

        return $this->redirect('/admin/diplomes/modele');
    }

    public function templateDelete(Request $request, array $params): Response
    {
        Auth::requireAdmin();

        $repository = new DiplomaRepository();
        $id = (int) $params['id'];
        $template = $repository->findTemplate($id);

        if ($template === null) {
            $this->notFound('Modèle introuvable.');
        }

        $repository->deleteTemplate($id);

        // Le fichier de fond n'est plus reference : on le retire du disque.
        $background = $template['background_path'] ?? null;

        if (is_string($background) && $background !== '' && is_file(storage_path($background))) {
            @unlink(storage_path($background));
        }

        Session::flash('success', 'Modèle supprimé.');

        return $this->redirect('/admin/diplomes/modele');
    }

    /** Previsualisation avec un participant fictif. */
    public function templatePreview(Request $request, array $params): Response
    {
        $training = $this->training();

        $templateId = $request->integer('modele');
        $template = $templateId > 0
            ? (new DiplomaRepository())->findTemplate($templateId)
            : null;

        try {
            $pdf = (new DiplomaService())->preview((int) $training['id'], $template);
        } catch (\Throwable $exception) {
            Session::flash('error', 'Prévisualisation impossible : ' . $exception->getMessage());

            return $this->redirect('/admin/diplomes/modele');
        }

        return Response::inline($pdf, 'apercu-diplome.pdf', 'application/pdf');
    }

    /**
     * Construit le calque a partir des champs du formulaire.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseLayout(Request $request): array
    {
        $layout = [];

        foreach (array_keys(DiplomaService::PLACEHOLDERS) as $key) {
            $field = str_replace('.', '_', $key);

            if (!$request->boolean('field_' . $field . '_enabled')) {
                continue;
            }

            $layout[] = [
                'key' => $key,
                'top' => max(0.0, min(300.0, (float) ($request->input('field_' . $field . '_top') ?? 0))),
                'left' => max(0.0, min(300.0, (float) ($request->input('field_' . $field . '_left') ?? 0))),
                'width' => max(5.0, min(300.0, (float) ($request->input('field_' . $field . '_width') ?? 100))),
                'size' => max(6.0, min(80.0, (float) ($request->input('field_' . $field . '_size') ?? 12))),
                'align' => (string) ($request->input('field_' . $field . '_align') ?? 'left'),
                'color' => (string) ($request->input('field_' . $field . '_color') ?? '#212529'),
                'bold' => $request->boolean('field_' . $field . '_bold'),
                'prefix' => $request->input('field_' . $field . '_prefix'),
            ];
        }

        return $layout;
    }
}

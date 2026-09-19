<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Audit;
use Craw\Core\Logger;
use Craw\Core\Settings;
use Craw\Core\View;
use Craw\Domain\AttendanceCalculator;
use Craw\Repository\DiplomaRepository;
use Craw\Repository\RegistrationRepository;
use Craw\Repository\TrainingRepository;
use RuntimeException;

/**
 * Generation des diplomes.
 *
 * Deux modes de gabarit, fournis par l'administrateur :
 *  - « background » : une image de fond A4 deposee par l'administrateur, sur
 *    laquelle des blocs de texte positionnables sont superposes ;
 *  - « html » : un gabarit HTML/CSS avec des variables {{...}}.
 *
 * Les deux passent par le meme moteur (Dompdf), ce qui garantit un rendu
 * identique a l'ecran de previsualisation et a l'impression groupee.
 */
final class DiplomaService
{
    /** Champs disponibles dans un gabarit. */
    public const PLACEHOLDERS = [
        'participant.nom_complet' => 'Nom et prénom du participant',
        'participant.nom' => 'Nom de famille',
        'participant.prenom' => 'Prénom',
        'participant.organisation' => 'Organisation',
        'formation.titre' => 'Intitulé de la formation',
        'formation.sujet' => 'Sujet de la formation',
        'formation.dates' => 'Dates (ex. du 15 au 19 mars 2027)',
        'formation.lieu' => 'Lieu de la formation',
        'presence.taux' => 'Taux de présence (ex. 90 %)',
        'diplome.numero' => 'Numéro unique du diplôme',
        'diplome.date_emission' => 'Date d\'émission',
        'qr_verification' => 'QR code de vérification (image)',
    ];

    public function __construct(
        private readonly DiplomaRepository $diplomas = new DiplomaRepository(),
        private readonly RegistrationRepository $registrations = new RegistrationRepository(),
        private readonly TrainingRepository $trainings = new TrainingRepository(),
        private readonly AttendanceService $attendance = new AttendanceService(),
    ) {
    }

    /**
     * Genere le diplome d'un participant et retourne la ligne creee.
     *
     * @param array<string, mixed> $summaryRow Ligne issue de AttendanceService::summary()
     *
     * @return array<string, mixed>
     */
    public function generate(array $summaryRow, int $trainingId, ?int $adminUserId, bool $force = false): array
    {
        if ($summaryRow['eligible'] !== true && !$force) {
            throw new RuntimeException((string) $summaryRow['eligibility_message']);
        }

        $training = $this->trainings->find($trainingId);

        if ($training === null) {
            throw new RuntimeException('Formation introuvable.');
        }

        $registrationId = (int) $summaryRow['id'];
        $version = $this->diplomas->nextVersion($registrationId);
        $prefix = (string) Settings::get('diploma.number_prefix', 'CRAW');
        $year = (int) date('Y', strtotime((string) $training['starts_on']));

        $number = $this->diplomas->nextNumber($prefix, $year);
        $verificationCode = strtoupper(bin2hex(random_bytes(8)));

        $template = $this->diplomas->activeTemplate();

        $html = $this->renderHtml($summaryRow, $training, [
            'number' => $number,
            'verification_code' => $verificationCode,
            'issued_on' => date('Y-m-d'),
        ], $template);

        $orientation = $template === null ? 'landscape' : (string) $template['orientation'];
        $pdf = PdfService::fromHtml($html, 'A4', $orientation);

        $directory = storage_path('diplomas/' . $year);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le répertoire de stockage des diplômes.');
        }

        $filename = sprintf('%s/%s-v%d.pdf', $directory, $number, $version);
        file_put_contents($filename, $pdf);

        $relativePath = 'diplomas/' . $year . '/' . basename($filename);

        $id = $this->diplomas->create([
            'registration_id' => $registrationId,
            'template_id' => $template === null ? null : (int) $template['id'],
            'number' => $number,
            'version' => $version,
            'attendance_rate' => (string) $summaryRow['attendance_rate'],
            'file_path' => $relativePath,
            'verification_code' => $verificationCode,
            'generated_by' => $adminUserId,
        ]);

        Audit::record('diploma.generate', 'registration', $registrationId, [
            'number' => $number,
            'version' => $version,
            'forced' => $force,
        ]);

        return [
            'id' => $id,
            'registration_id' => $registrationId,
            'number' => $number,
            'version' => $version,
            'file_path' => $relativePath,
            'absolute_path' => $filename,
            'verification_code' => $verificationCode,
        ];
    }

    /**
     * Genere en lot pour tous les eligibles n'ayant pas encore de diplome.
     *
     * @return array{generated: int, skipped: int, errors: array<int, string>}
     */
    public function generateBatch(int $trainingId, ?int $adminUserId, bool $regenerate = false): array
    {
        $existing = $this->diplomas->latestMapForTraining($trainingId);

        $generated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->attendance->summary($trainingId) as $row) {
            $registrationId = (int) $row['id'];

            if ($row['eligible'] !== true) {
                $skipped++;
                continue;
            }

            if (!$regenerate && isset($existing[$registrationId])) {
                $skipped++;
                continue;
            }

            try {
                $this->generate($row, $trainingId, $adminUserId);
                $generated++;
            } catch (\Throwable $exception) {
                $errors[$registrationId] = $exception->getMessage();
                Logger::error('Echec de generation d\'un diplome.', [
                    'registration_id' => $registrationId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Met en file l'envoi par courriel du diplome, avec le PDF en piece jointe.
     */
    public function sendByEmail(int $diplomaId): bool
    {
        $diploma = \Craw\Core\Database::selectOne(
            'SELECT * FROM diplomas WHERE id = :id',
            ['id' => $diplomaId]
        );

        if ($diploma === null) {
            return false;
        }

        $registration = $this->registrations->find((int) $diploma['registration_id']);

        if ($registration === null) {
            return false;
        }

        $training = $this->trainings->find((int) $registration['training_id']);

        if ($training === null) {
            return false;
        }

        $path = storage_path((string) $diploma['file_path']);

        if (!is_file($path)) {
            Logger::error('Fichier de diplome introuvable.', ['path' => $path]);

            return false;
        }

        Notifier::diplomaIssued($registration, $training, $diploma, $path);
        $this->diplomas->markSent($diplomaId);

        Audit::record('diploma.send', 'registration', (int) $registration['id'], [
            'diploma_id' => $diplomaId,
        ]);

        return true;
    }

    /**
     * Un unique PDF reprenant tous les diplomes, pour l'impression groupee.
     */
    public function printBundle(int $trainingId): string
    {
        $training = $this->trainings->find($trainingId);

        if ($training === null) {
            throw new RuntimeException('Formation introuvable.');
        }

        $template = $this->diplomas->activeTemplate();
        $existing = $this->diplomas->latestMapForTraining($trainingId);

        $pages = [];

        foreach ($this->attendance->summary($trainingId) as $row) {
            $registrationId = (int) $row['id'];
            $diploma = $existing[$registrationId] ?? null;

            if ($diploma === null) {
                continue;
            }

            $pages[] = $this->renderBody($row, $training, [
                'number' => (string) $diploma['number'],
                'verification_code' => (string) $diploma['verification_code'],
                'issued_on' => substr((string) $diploma['generated_at'], 0, 10),
            ], $template);
        }

        if ($pages === []) {
            throw new RuntimeException('Aucun diplôme généré : rien à imprimer.');
        }

        return PdfService::combine(
            $pages,
            View::partial('pdf/diploma_styles'),
            'A4',
            $template === null ? 'landscape' : (string) $template['orientation']
        );
    }

    /** Previsualisation avec un participant fictif, avant toute generation. */
    public function preview(int $trainingId, ?array $template = null): string
    {
        $training = $this->trainings->find($trainingId);

        if ($training === null) {
            throw new RuntimeException('Formation introuvable.');
        }

        $template ??= $this->diplomas->activeTemplate();

        $sample = [
            'id' => 0,
            'first_name' => 'Camille',
            'last_name' => 'Dubois',
            'organisation' => 'Exemple ASBL',
            'attendance_rate' => 0.9,
        ];

        $html = $this->renderHtml($sample, $training, [
            'number' => sprintf('%s-%d-0001', (string) Settings::get('diploma.number_prefix', 'CRAW'), (int) date('Y', strtotime((string) $training['starts_on']))),
            'verification_code' => 'EXEMPLE0000',
            'issued_on' => date('Y-m-d'),
        ], $template);

        return PdfService::fromHtml(
            $html,
            'A4',
            $template === null ? 'landscape' : (string) $template['orientation']
        );
    }

    /**
     * @param array<string, mixed>      $participant
     * @param array<string, mixed>      $training
     * @param array<string, string>     $diploma
     * @param array<string, mixed>|null $template
     */
    private function renderHtml(array $participant, array $training, array $diploma, ?array $template): string
    {
        return sprintf(
            '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Diplôme</title><style>%s</style></head>'
            . '<body><div class="pdf-page">%s</div></body></html>',
            View::partial('pdf/diploma_styles'),
            $this->renderBody($participant, $training, $diploma, $template)
        );
    }

    /**
     * @param array<string, mixed>      $participant
     * @param array<string, mixed>      $training
     * @param array<string, string>     $diploma
     * @param array<string, mixed>|null $template
     */
    private function renderBody(array $participant, array $training, array $diploma, ?array $template): string
    {
        $values = $this->placeholderValues($participant, $training, $diploma);

        // Aucun gabarit fourni : on utilise le modele par defaut du site.
        if ($template === null) {
            return View::partial('pdf/diploma_default', [
                'values' => $values,
                'training' => $training,
            ]);
        }

        if ((string) $template['mode'] === 'html') {
            return $this->substitute((string) ($template['html_body'] ?? ''), $values);
        }

        // Mode « fond + calque » : image de fond + blocs positionnes.
        /** @var array<int, array<string, mixed>> $layout */
        $layout = json_decode((string) ($template['field_layout'] ?? '[]'), true) ?: [];

        return View::partial('pdf/diploma_background', [
            'backgroundUri' => $this->backgroundDataUri($template),
            'layout' => $layout,
            'values' => $values,
        ]);
    }

    /**
     * @param array<string, mixed> $template
     */
    private function backgroundDataUri(array $template): string
    {
        $path = $template['background_path'] ?? null;

        if (!is_string($path) || $path === '') {
            return '';
        }

        $absolute = storage_path($path);

        if (!is_file($absolute)) {
            return '';
        }

        $mime = match (strtolower(pathinfo($absolute, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return sprintf('data:%s;base64,%s', $mime, base64_encode((string) file_get_contents($absolute)));
    }

    /**
     * @param array<string, mixed>  $participant
     * @param array<string, mixed>  $training
     * @param array<string, string> $diploma
     *
     * @return array<string, string>
     */
    private function placeholderValues(array $participant, array $training, array $diploma): array
    {
        $fullName = trim(
            (string) ($participant['first_name'] ?? '') . ' ' . (string) ($participant['last_name'] ?? '')
        );

        $dates = sprintf(
            'du %s au %s',
            fr_date((string) $training['starts_on']),
            fr_date((string) $training['ends_on'])
        );

        $subject = (bool) ($training['subject_pending'] ?? false) || ($training['subject'] ?? '') === ''
            ? (string) $training['title']
            : (string) $training['subject'];

        return [
            'participant.nom_complet' => $fullName,
            'participant.nom' => (string) ($participant['last_name'] ?? ''),
            'participant.prenom' => (string) ($participant['first_name'] ?? ''),
            'participant.organisation' => (string) ($participant['organisation'] ?? ''),
            'formation.titre' => (string) $training['title'],
            'formation.sujet' => $subject,
            'formation.dates' => $dates,
            'formation.lieu' => (string) $training['location_name'],
            'presence.taux' => AttendanceCalculator::format((float) ($participant['attendance_rate'] ?? 0)),
            'diplome.numero' => $diploma['number'],
            'diplome.date_emission' => fr_date($diploma['issued_on']),
            'qr_verification' => QrService::dataUri(
                url('/diplome/verification/' . $diploma['verification_code']),
                200
            ),
        ];
    }

    /**
     * Remplace les variables {{cle}} d'un gabarit HTML.
     *
     * Les valeurs sont echappees, sauf le QR code qui est une image.
     *
     * @param array<string, string> $values
     */
    private function substitute(string $template, array $values): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_.]+)\s*\}\}/i',
            static function (array $matches) use ($values): string {
                $key = strtolower($matches[1]);

                if (!array_key_exists($key, $values)) {
                    return '';
                }

                if ($key === 'qr_verification') {
                    return $values[$key] === ''
                        ? ''
                        : sprintf('<img src="%s" alt="" class="diploma-qr">', $values[$key]);
                }

                return e($values[$key]);
            },
            $template
        );
    }
}

<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Domain\AttendanceCalculator;
use Craw\Domain\DietPreference;
use Craw\Repository\PaymentRepository;

/**
 * Exports tabulaires.
 *
 * Le CSV est produit avec un BOM UTF-8 et le point-virgule comme separateur :
 * c'est ce qu'attend Excel en configuration francophone, sinon les accents et
 * les colonnes sont casses a l'ouverture.
 */
final class ExportService
{
    /**
     * @param array<int, array<string, mixed>> $registrations
     */
    public static function registrationsCsv(array $registrations, bool $includeHealthData = true): string
    {
        $headers = [
            'Référence',
            'Statut inscription',
            'Civilité',
            'Nom',
            'Prénom',
            'E-mail',
            'Téléphone',
            'Organisation',
            'Fonction',
            'Rue',
            'Code postal',
            'Ville',
            'Pays',
            'N° TVA',
            'Régime alimentaire',
            'Commentaire régime',
        ];

        if ($includeHealthData) {
            $headers[] = 'Allergies déclarées';
            $headers[] = 'Détail des allergies';
        }

        $headers = array_merge($headers, [
            'Remarques logistiques',
            'Montant dû',
            'Statut paiement',
            'Montant reçu',
            'Date de valeur',
            'Communication structurée',
            'Date d\'inscription',
        ]);

        $rows = [];

        foreach ($registrations as $registration) {
            $row = [
                (string) $registration['reference'],
                RegistrationService::statusLabel((string) $registration['status']),
                (string) ($registration['civility'] ?? ''),
                (string) $registration['last_name'],
                (string) $registration['first_name'],
                (string) $registration['email'],
                (string) ($registration['phone'] ?? ''),
                (string) ($registration['organisation'] ?? ''),
                (string) ($registration['job_title'] ?? ''),
                (string) ($registration['street'] ?? ''),
                (string) ($registration['postal_code'] ?? ''),
                (string) ($registration['city'] ?? ''),
                (string) ($registration['country'] ?? ''),
                (string) ($registration['vat_number'] ?? ''),
                DietPreference::tryLabel($registration['diet'] ?? null),
                (string) ($registration['diet_comment'] ?? ''),
            ];

            if ($includeHealthData) {
                $row[] = (int) ($registration['has_allergies'] ?? 0) === 1 ? 'Oui' : 'Non';
                $row[] = (string) ($registration['allergies_comment'] ?? '');
            }

            $row = array_merge($row, [
                self::amount((int) ($registration['amount_due_cents'] ?? 0)),
                PaymentRepository::statusLabel((string) ($registration['payment_status'] ?? 'unpaid')),
                $registration['received_cents'] === null ? '' : self::amount((int) $registration['received_cents']),
                (string) ($registration['value_date'] ?? ''),
                (string) ($registration['structured_communication'] ?? ''),
                substr((string) $registration['created_at'], 0, 16),
            ]);

            $rows[] = $row;
        }

        return self::toCsv($headers, $rows);
    }

    /**
     * Export des presences : une colonne par creneau, plus le taux calcule.
     *
     * @param array<int, array<string, mixed>> $summary
     * @param array<int, array<string, mixed>> $days
     * @param array<int, array<int, true>> $absences
     */
    public static function attendanceCsv(array $summary, array $days, array $absences): string
    {
        $headers = ['Référence', 'Nom', 'Prénom', 'E-mail'];

        foreach ($days as $day) {
            $headers[] = (string) ($day['label'] ?? $day['day_date']);
        }

        $headers = array_merge($headers, [
            'Créneaux présents',
            'Créneaux totaux',
            'Taux de présence',
            'Éligible au diplôme',
            'Motif (si non éligible)',
        ]);

        $rows = [];

        foreach ($summary as $participant) {
            $registrationId = (int) $participant['id'];

            $row = [
                (string) $participant['reference'],
                (string) $participant['last_name'],
                (string) $participant['first_name'],
                (string) $participant['email'],
            ];

            foreach ($days as $day) {
                $row[] = isset($absences[$registrationId][(int) $day['id']]) ? 'Absent' : 'Présent';
            }

            $row = array_merge($row, [
                (string) $participant['present_slots'],
                (string) $participant['total_slots'],
                AttendanceCalculator::format((float) $participant['attendance_rate']),
                $participant['eligible'] === true ? 'Oui' : 'Non',
                (string) $participant['eligibility_message'],
            ]);

            $rows[] = $row;
        }

        return self::toCsv($headers, $rows);
    }

    /**
     * @param array<int, array{diet: string, label: string, total: int, confirmed: int, pending: int, percentage: float}> $breakdown
     */
    public static function dietStatisticsCsv(array $breakdown): string
    {
        $rows = [];

        foreach ($breakdown as $item) {
            $rows[] = [
                $item['label'],
                (string) $item['total'],
                (string) $item['confirmed'],
                (string) $item['pending'],
                number_format($item['percentage'], 1, ',', '') . ' %',
            ];
        }

        return self::toCsv(
            ['Régime alimentaire', 'Total', 'Confirmés', 'En attente de paiement', 'Part'],
            $rows
        );
    }

    /**
     * @param array<int, string>              $headers
     * @param array<int, array<int, string>>  $rows
     */
    public static function toCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, $headers, ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, self::sanitiseRow($row), ';', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        // BOM UTF-8 : sans lui, Excel n'affiche pas correctement les accents.
        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Neutralise l'injection de formules dans un tableur : une cellule commencant
     * par =, +, - ou @ est interpretee comme une formule par Excel.
     *
     * @param array<int, string> $row
     * @return array<int, string>
     */
    private static function sanitiseRow(array $row): array
    {
        return array_map(static function (string $value): string {
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $value;
            }

            return $value;
        }, $row);
    }

    private static function amount(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }

    public static function filename(string $prefix, string $extension): string
    {
        return sprintf('%s-%s.%s', $prefix, date('Y-m-d'), $extension);
    }
}

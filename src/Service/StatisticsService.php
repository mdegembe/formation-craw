<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Database;
use Craw\Domain\DietPreference;

/**
 * Statistiques sur les preferences alimentaires.
 *
 * Le comptage s'appuie sur la donnee structuree (l'enum), jamais sur le
 * commentaire libre : on ne compte pas du texte. Les commentaires sont listes
 * a part, a destination du traiteur.
 */
final class StatisticsService
{
    /**
     * Repartition par regime, avec le detail par statut de paiement.
     *
     * @return array<int, array{diet: string, label: string, color: string, total: int, confirmed: int, pending: int, percentage: float}>
     */
    public function dietBreakdown(int $trainingId): array
    {
        $rows = Database::select(
            "SELECT r.diet,
                    COUNT(*) AS total,
                    SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN r.status = 'pending_payment' THEN 1 ELSE 0 END) AS pending
             FROM registrations r
             WHERE r.training_id = :training
               AND r.status IN ('pending_payment', 'confirmed')
             GROUP BY r.diet",
            ['training' => $trainingId]
        );

        $byDiet = [];
        $grandTotal = 0;

        foreach ($rows as $row) {
            $byDiet[(string) $row['diet']] = [
                'total' => (int) $row['total'],
                'confirmed' => (int) $row['confirmed'],
                'pending' => (int) $row['pending'],
            ];
            $grandTotal += (int) $row['total'];
        }

        $breakdown = [];

        foreach (DietPreference::cases() as $diet) {
            $data = $byDiet[$diet->value] ?? ['total' => 0, 'confirmed' => 0, 'pending' => 0];

            $breakdown[] = [
                'diet' => $diet->value,
                'label' => $diet->label(),
                'color' => $diet->color(),
                'total' => $data['total'],
                'confirmed' => $data['confirmed'],
                'pending' => $data['pending'],
                'percentage' => $grandTotal === 0 ? 0.0 : round($data['total'] / $grandTotal * 100, 1),
            ];
        }

        return $breakdown;
    }

    public function totalActive(int $trainingId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM registrations
             WHERE training_id = :training AND status IN ('pending_payment', 'confirmed')",
            ['training' => $trainingId]
        );
    }

    /**
     * Participants ayant declare une allergie ou une intolerance.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allergies(int $trainingId): array
    {
        return Database::select(
            "SELECT id, reference, first_name, last_name, diet, diet_comment,
                    allergies_comment, logistics_comment, status
             FROM registrations
             WHERE training_id = :training
               AND status IN ('pending_payment', 'confirmed')
               AND has_allergies = 1
             ORDER BY last_name ASC, first_name ASC",
            ['training' => $trainingId]
        );
    }

    /**
     * Commentaires de regime (halal, casher, autre) a transmettre au traiteur.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dietComments(int $trainingId): array
    {
        return Database::select(
            "SELECT id, reference, first_name, last_name, diet, diet_comment, status
             FROM registrations
             WHERE training_id = :training
               AND status IN ('pending_payment', 'confirmed')
               AND diet_comment IS NOT NULL AND diet_comment <> ''
             ORDER BY diet ASC, last_name ASC",
            ['training' => $trainingId]
        );
    }

    /**
     * Remarques logistiques diverses (accessibilite, horaires...).
     *
     * @return array<int, array<string, mixed>>
     */
    public function logisticsComments(int $trainingId): array
    {
        return Database::select(
            "SELECT id, reference, first_name, last_name, logistics_comment, status
             FROM registrations
             WHERE training_id = :training
               AND status IN ('pending_payment', 'confirmed')
               AND logistics_comment IS NOT NULL AND logistics_comment <> ''
             ORDER BY last_name ASC",
            ['training' => $trainingId]
        );
    }

    /**
     * Synthese chiffree pour le tableau de bord.
     *
     * @return array{total: int, with_allergies: int, special_diets: int}
     */
    public function headline(int $trainingId): array
    {
        $total = $this->totalActive($trainingId);

        $withAllergies = (int) Database::scalar(
            "SELECT COUNT(*) FROM registrations
             WHERE training_id = :training AND has_allergies = 1
               AND status IN ('pending_payment', 'confirmed')",
            ['training' => $trainingId]
        );

        $specialDiets = (int) Database::scalar(
            "SELECT COUNT(*) FROM registrations
             WHERE training_id = :training AND diet <> 'standard'
               AND status IN ('pending_payment', 'confirmed')",
            ['training' => $trainingId]
        );

        return [
            'total' => $total,
            'with_allergies' => $withAllergies,
            'special_diets' => $specialDiets,
        ];
    }

    /**
     * Fiche traiteur : effectifs par regime et detail des contraintes.
     *
     * @return array<string, mixed>
     */
    public function catererSheet(int $trainingId): array
    {
        return [
            'headline' => $this->headline($trainingId),
            'breakdown' => array_values(array_filter(
                $this->dietBreakdown($trainingId),
                static fn (array $row): bool => $row['total'] > 0
            )),
            'allergies' => $this->allergies($trainingId),
            'diet_comments' => $this->dietComments($trainingId),
        ];
    }
}

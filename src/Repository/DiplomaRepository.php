<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;

final class DiplomaRepository
{
    /**
     * @return array<string, mixed>|null
     */
    public function latestForRegistration(int $registrationId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM diplomas WHERE registration_id = :id ORDER BY version DESC, id DESC LIMIT 1',
            ['id' => $registrationId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forTraining(int $trainingId): array
    {
        return Database::select(
            'SELECT d.*, r.first_name, r.last_name, r.email, r.reference
             FROM diplomas d
             INNER JOIN registrations r ON r.id = d.registration_id
             WHERE r.training_id = :training
             ORDER BY r.last_name ASC, d.version DESC',
            ['training' => $trainingId]
        );
    }

    /** Derniere version par inscription, indexee par registration_id. */
    public function latestMapForTraining(int $trainingId): array
    {
        $map = [];

        foreach ($this->forTraining($trainingId) as $diploma) {
            $registrationId = (int) $diploma['registration_id'];

            if (!isset($map[$registrationId])
                || (int) $diploma['version'] > (int) $map[$registrationId]['version']
            ) {
                $map[$registrationId] = $diploma;
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByVerificationCode(string $code): ?array
    {
        if (preg_match('/^[A-Za-z0-9]{8,40}$/', $code) !== 1) {
            return null;
        }

        return Database::selectOne(
            'SELECT d.*, r.first_name, r.last_name, t.title AS training_title,
                    t.starts_on, t.ends_on, t.subject
             FROM diplomas d
             INNER JOIN registrations r ON r.id = d.registration_id
             INNER JOIN training t ON t.id = r.training_id
             WHERE d.verification_code = :code',
            ['code' => $code]
        );
    }

    public function nextVersion(int $registrationId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(MAX(version), 0) FROM diplomas WHERE registration_id = :id',
            ['id' => $registrationId]
        ) + 1;
    }

    /** Numerotation continue par annee : CRAW-2027-0001. */
    public function nextNumber(string $prefix, int $year): string
    {
        $like = sprintf('%s-%d-%%', $prefix, $year);

        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM diplomas WHERE number LIKE :like',
            ['like' => $like]
        );

        return sprintf('%s-%d-%04d', $prefix, $year, $count + 1);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data['generated_at'] ??= date('Y-m-d H:i:s');

        return Database::insert('diplomas', $data);
    }

    public function markSent(int $id): void
    {
        Database::update('diplomas', ['sent_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    // ------------------------------------------------------------- templates

    /**
     * @return array<string, mixed>|null
     */
    public function activeTemplate(): ?array
    {
        return Database::selectOne('SELECT * FROM diploma_templates WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function templates(): array
    {
        return Database::select('SELECT * FROM diploma_templates ORDER BY id DESC');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTemplate(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM diploma_templates WHERE id = :id', ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createTemplate(array $data): int
    {
        $data['created_at'] ??= date('Y-m-d H:i:s');

        return Database::insert('diploma_templates', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateTemplate(int $id, array $data): void
    {
        Database::update('diploma_templates', $data, ['id' => $id]);
    }

    public function activateTemplate(int $id): void
    {
        Database::transaction(static function () use ($id): void {
            Database::run('UPDATE diploma_templates SET is_active = 0');
            Database::update('diploma_templates', ['is_active' => 1], ['id' => $id]);
        });
    }

    public function deleteTemplate(int $id): void
    {
        Database::delete('diploma_templates', ['id' => $id]);
    }
}

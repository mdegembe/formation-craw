<?php

declare(strict_types=1);

namespace Craw\Repository;

use Craw\Core\Database;
use Craw\Domain\Slugger;

final class NewsRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function published(int $limit = 10, int $offset = 0): array
    {
        return Database::select(
            "SELECT * FROM news
             WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :now
             ORDER BY published_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            ['now' => date('Y-m-d H:i:s')]
        );
    }

    public function countPublished(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM news
             WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :now",
            ['now' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedBySlug(string $slug): ?array
    {
        return Database::selectOne(
            "SELECT n.*, u.display_name AS author_name
             FROM news n
             LEFT JOIN admin_users u ON u.id = n.author_id
             WHERE n.slug = :slug AND n.status = 'published'
               AND n.published_at IS NOT NULL AND n.published_at <= :now",
            ['slug' => $slug, 'now' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM news WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return Database::select(
            'SELECT n.*, u.display_name AS author_name
             FROM news n
             LEFT JOIN admin_users u ON u.id = n.author_id
             ORDER BY COALESCE(n.published_at, n.created_at) DESC'
        );
    }

    /**
     * Genere un slug unique, en suffixant si necessaire.
     */
    public function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = Slugger::slugify($title);
        $slug = $base;
        $suffix = 2;

        while (true) {
            $sql = 'SELECT id FROM news WHERE slug = :slug';
            $bindings = ['slug' => $slug];

            if ($exceptId !== null) {
                $sql .= ' AND id <> :id';
                $bindings['id'] = $exceptId;
            }

            if (Database::selectOne($sql, $bindings) === null) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $data['created_at'] ??= date('Y-m-d H:i:s');
        $data['updated_at'] ??= $data['created_at'];

        return Database::insert('news', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('news', $data, ['id' => $id]);
    }

    public function delete(int $id): void
    {
        Database::delete('news', ['id' => $id]);
    }
}

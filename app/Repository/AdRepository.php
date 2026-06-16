<?php

declare(strict_types=1);

namespace HelpTeam\Repository;

use HelpTeam\Support\Logger;
use RedBeanPHP\R;
use RuntimeException;

final class AdRepository
{
    /**
     * @param array{category?: string, city?: string, q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function findPublishedWithFirstMedia(array $filters = [], int $limit = 30): array
    {
        $this->ensureRedBeanAvailable();

        $where = ['a.status = ?'];
        $params = ['published'];

        if (!empty($filters['category'])) {
            $where[] = 'a.category = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['city'])) {
            $where[] = 'a.city LIKE ?';
            $params[] = '%' . $this->escapeLike($filters['city']) . '%';
        }

        if (!empty($filters['q'])) {
            $where[] = '(' . implode(' OR ', [
                'a.title LIKE ?',
                'a.body LIKE ?',
                'a.dog_name LIKE ?',
                'a.city LIKE ?',
                'a.address LIKE ?',
            ]) . ')';

            $query = '%' . $this->escapeLike($filters['q']) . '%';
            array_push($params, $query, $query, $query, $query, $query);
        }

        $limit = max(1, min(100, $limit));
        $sql = '
            SELECT
                a.id,
                a.category,
                a.status,
                a.dog_name,
                a.title,
                a.body,
                a.city,
                a.address,
                a.created_at,
                a.published_at,
                m.file_path AS image_path
            FROM ads a
            LEFT JOIN ad_media m ON m.id = (
                SELECT am.id
                FROM ad_media am
                WHERE am.ad_id = a.id
                  AND am.media_type = ?
                ORDER BY am.sort_order ASC, am.id ASC
                LIMIT 1
            )
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.id DESC
            LIMIT ' . $limit;

        array_unshift($params, 'image');

        return R::getAll($sql, $params);
    }

    public function begin(): void
    {
        $this->ensureRedBeanAvailable();
        R::begin();
    }

    public function commit(): void
    {
        R::commit();
    }

    public function rollback(): void
    {
        R::rollback();
    }

    /**
     * @param array<string, float|string|null> $data
     */
    public function create(array $data): int
    {
        $this->ensureRedBeanAvailable();

        R::exec(
            <<<'SQL'
            INSERT INTO ads (
                category,
                status,
                dog_name,
                title,
                body,
                city,
                address,
                latitude,
                longitude,
                contact_name,
                contact_phone,
                contact_vk
            ) VALUES (?, 'moderation', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            SQL,
            [
                $data['category'],
                $data['dog_name'],
                $data['title'],
                $data['body'],
                $data['city'],
                $data['address'],
                $data['latitude'],
                $data['longitude'],
                $data['contact_name'],
                $data['contact_phone'],
                $data['contact_vk'],
            ]
        );

        return (int) R::getCell('SELECT LAST_INSERT_ID()');
    }

    /**
     * @param list<array<string, int|string>> $media
     */
    public function createMedia(int $adId, array $media): void
    {
        Logger::info('ad_repository.create_media.start', [
            'ad_id' => $adId,
            'media_count' => count($media),
            'paths' => array_map(static fn (array $item): string => (string) ($item['file_path'] ?? ''), $media),
        ]);

        if ($media === []) {
            return;
        }

        $this->ensureRedBeanAvailable();

        foreach ($media as $item) {
            R::exec(
                <<<'SQL'
                INSERT INTO ad_media (
                    ad_id,
                    media_type,
                    file_path,
                    original_name,
                    mime_type,
                    file_size,
                    sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                SQL,
                [
                    $adId,
                    $item['media_type'],
                    $item['file_path'],
                    $item['original_name'],
                    $item['mime_type'],
                    $item['file_size'],
                    $item['sort_order'],
                ]
            );
        }

        Logger::info('ad_repository.create_media.done', [
            'ad_id' => $adId,
            'media_count' => count($media),
        ]);
    }

    private function ensureRedBeanAvailable(): void
    {
        if (!class_exists(R::class)) {
            throw new RuntimeException('RedBeanPHP is not available.');
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

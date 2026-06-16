<?php

declare(strict_types=1);

namespace HelpTeam\Repository;

use RedBeanPHP\R;
use RuntimeException;

final class AdRepository
{
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
    }

    private function ensureRedBeanAvailable(): void
    {
        if (!class_exists(R::class)) {
            throw new RuntimeException('RedBeanPHP is not available.');
        }
    }
}

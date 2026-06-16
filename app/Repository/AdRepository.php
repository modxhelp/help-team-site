<?php

declare(strict_types=1);

namespace HelpTeam\Repository;

use RedBeanPHP\R;
use RuntimeException;

final class AdRepository
{
    /**
     * @param array<string, float|string|null> $data
     */
    public function create(array $data): int
    {
        if (!class_exists(R::class)) {
            throw new RuntimeException('RedBeanPHP is not available.');
        }

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
}

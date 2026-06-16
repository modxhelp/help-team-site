<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
        CREATE TABLE ads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'moderation',
            dog_name VARCHAR(255) NULL,
            title VARCHAR(255) NULL,
            body TEXT NOT NULL,
            city VARCHAR(255) NULL,
            address VARCHAR(500) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            contact_name VARCHAR(255) NULL,
            contact_phone VARCHAR(100) NULL,
            contact_vk VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            published_at TIMESTAMP NULL DEFAULT NULL,
            INDEX ads_category_index (category),
            INDEX ads_status_index (status),
            INDEX ads_city_index (city),
            INDEX ads_created_at_index (created_at),
            INDEX ads_coordinates_index (latitude, longitude),
            FULLTEXT INDEX ads_fulltext_index (title, body, dog_name, city, address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

    $pdo->exec(<<<'SQL'
        CREATE TABLE ad_photos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ad_id BIGINT UNSIGNED NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX ad_photos_ad_id_index (ad_id),
            CONSTRAINT ad_photos_ad_id_foreign
                FOREIGN KEY (ad_id) REFERENCES ads (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
};

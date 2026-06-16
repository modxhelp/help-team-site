<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $tableExists = static function (string $table) use ($pdo): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $statement->execute([$table]);

        return (int) $statement->fetchColumn() > 0;
    };

    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $statement->execute([$table, $column]);

        return (int) $statement->fetchColumn() > 0;
    };

    $indexedColumnExists = static function (string $table, string $column) use ($pdo): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND SEQ_IN_INDEX = 1'
        );
        $statement->execute([$table, $column]);

        return (int) $statement->fetchColumn() > 0;
    };

    $foreignKeyExists = static function (string $table, string $column) use ($pdo): bool {
        $statement = $pdo->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL);
        $statement->execute([$table, $column]);

        return (int) $statement->fetchColumn() > 0;
    };

    if (!$tableExists('ad_media') && $tableExists('ad_photos')) {
        $pdo->exec('RENAME TABLE ad_photos TO ad_media');
    }

    if (!$tableExists('ad_media')) {
        $pdo->exec(<<<'SQL'
            CREATE TABLE ad_media (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ad_id BIGINT UNSIGNED NOT NULL,
                media_type VARCHAR(20) NOT NULL DEFAULT 'image',
                file_path VARCHAR(500) NOT NULL,
                original_name VARCHAR(255) NULL,
                mime_type VARCHAR(100) NULL,
                file_size BIGINT UNSIGNED NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX ad_media_ad_id_index (ad_id),
                INDEX ad_media_media_type_index (media_type),
                INDEX ad_media_sort_order_index (sort_order),
                CONSTRAINT ad_media_ad_id_foreign
                    FOREIGN KEY (ad_id) REFERENCES ads (id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        return;
    }

    if (!$columnExists('ad_media', 'media_type')) {
        $pdo->exec("ALTER TABLE ad_media ADD COLUMN media_type VARCHAR(20) NOT NULL DEFAULT 'image' AFTER ad_id");
    }

    if (!$columnExists('ad_media', 'original_name')) {
        $pdo->exec('ALTER TABLE ad_media ADD COLUMN original_name VARCHAR(255) NULL AFTER file_path');
    }

    if (!$columnExists('ad_media', 'mime_type')) {
        $pdo->exec('ALTER TABLE ad_media ADD COLUMN mime_type VARCHAR(100) NULL AFTER original_name');
    }

    if (!$columnExists('ad_media', 'file_size')) {
        $pdo->exec('ALTER TABLE ad_media ADD COLUMN file_size BIGINT UNSIGNED NULL AFTER mime_type');
    }

    if (!$columnExists('ad_media', 'sort_order')) {
        $pdo->exec('ALTER TABLE ad_media ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER file_size');
    }

    if (!$columnExists('ad_media', 'created_at')) {
        $pdo->exec('ALTER TABLE ad_media ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER sort_order');
    }

    if (!$indexedColumnExists('ad_media', 'ad_id')) {
        $pdo->exec('ALTER TABLE ad_media ADD INDEX ad_media_ad_id_index (ad_id)');
    }

    if (!$indexedColumnExists('ad_media', 'media_type')) {
        $pdo->exec('ALTER TABLE ad_media ADD INDEX ad_media_media_type_index (media_type)');
    }

    if (!$indexedColumnExists('ad_media', 'sort_order')) {
        $pdo->exec('ALTER TABLE ad_media ADD INDEX ad_media_sort_order_index (sort_order)');
    }

    if (!$foreignKeyExists('ad_media', 'ad_id')) {
        $pdo->exec(<<<'SQL'
            ALTER TABLE ad_media
                ADD CONSTRAINT ad_media_ad_id_foreign
                FOREIGN KEY (ad_id) REFERENCES ads (id)
                ON DELETE CASCADE
            SQL);
    }
};

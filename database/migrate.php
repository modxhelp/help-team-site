<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

try {
    runMigrations();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

function runMigrations(): void
{
    $pdo = createMigrationConnection();
    createMigrationsTable($pdo);

    $applied = loadAppliedMigrations($pdo);
    $files = findMigrationFiles(__DIR__ . '/migrations');

    if ($files === []) {
        echo "No migrations found.\n";
        return;
    }

    $ran = 0;

    foreach ($files as $file) {
        $migration = pathinfo($file, PATHINFO_FILENAME);

        if (isset($applied[$migration])) {
            echo "Skipped {$migration}\n";
            continue;
        }

        applyMigration($pdo, $file, $migration);
        $ran++;
    }

    echo $ran === 0 ? "Nothing to migrate.\n" : "Applied migrations: {$ran}\n";
}

function createMigrationConnection(): PDO
{
    $host = requiredEnv('DB_HOST');
    $name = requiredEnv('DB_NAME');
    $user = requiredEnv('DB_USER');
    $password = env('DB_PASS', env('DB_PASSWORD', '')) ?? '';
    $charset = env('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4';
    $port = env('DB_PORT', '3306') ?? '3306';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function requiredEnv(string $key): string
{
    $value = env($key);

    if ($value === null) {
        throw new RuntimeException("Missing required environment variable: {$key}");
    }

    return $value;
}

function createMigrationsTable(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
}

/**
 * @return array<string, true>
 */
function loadAppliedMigrations(PDO $pdo): array
{
    $rows = $pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll();
    $applied = [];

    foreach ($rows as $row) {
        $applied[(string) $row['migration']] = true;
    }

    return $applied;
}

/**
 * @return list<string>
 */
function findMigrationFiles(string $path): array
{
    $files = glob($path . '/*.php') ?: [];
    sort($files, SORT_STRING);

    return array_values($files);
}

function markMigrationAsApplied(PDO $pdo, string $migration): void
{
    $statement = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
    $statement->execute(['migration' => $migration]);
}

function applyMigration(PDO $pdo, string $file, string $migration): void
{
    echo "Running {$migration}... ";

    try {
        $callback = require $file;

        if (!is_callable($callback)) {
            throw new RuntimeException("Migration {$migration} must return a callable.");
        }

        $callback($pdo);
        markMigrationAsApplied($pdo, $migration);

        echo "done\n";
    } catch (Throwable $exception) {
        echo "failed\n";

        throw $exception;
    }
}

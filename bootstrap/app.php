<?php

declare(strict_types=1);

use RedBeanPHP\R;

define('BASE_PATH', dirname(__DIR__));

$autoloadPath = BASE_PATH . '/vendor/autoload.php';

if (is_file($autoloadPath)) {
    require $autoloadPath;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'HelpTeam\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

loadEnv(BASE_PATH . '/.env');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$host = env('DB_HOST');
$name = env('DB_NAME');
$user = env('DB_USER');
$port = env('DB_PORT', '3306');
$password = env('DB_PASS', env('DB_PASSWORD', ''));
$charset = env('DB_CHARSET', 'utf8mb4');

if ($host !== null && $name !== null && $user !== null && class_exists(R::class)) {
    R::setup("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $user, $password);
    R::freeze(env('APP_ENV', 'production') === 'production');
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

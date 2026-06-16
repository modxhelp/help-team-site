<?php

declare(strict_types=1);

namespace HelpTeam\Support;

use Throwable;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $event, array $context = []): void
    {
        self::write('error', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        try {
            $logDir = self::basePath() . '/storage/logs';

            if (!is_dir($logDir) && !mkdir($logDir, 0775, true) && !is_dir($logDir)) {
                return;
            }

            $line = json_encode([
                'time' => date('c'),
                'level' => $level,
                'event' => $event,
                'context' => $context,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($line === false) {
                return;
            }

            file_put_contents($logDir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
        }
    }

    private static function basePath(): string
    {
        if (defined('BASE_PATH')) {
            return (string) constant('BASE_PATH');
        }

        return dirname(__DIR__, 2);
    }
}

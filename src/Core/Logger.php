<?php

declare(strict_types=1);

namespace Craw\Core;

final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $directory = storage_path('logs');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : ' ' . (string) json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        @file_put_contents($directory . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function exception(\Throwable $exception): void
    {
        self::log('error', $exception->getMessage(), [
            'exception' => $exception::class,
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'trace' => Config::isDebug() ? $exception->getTraceAsString() : null,
        ]);
    }
}

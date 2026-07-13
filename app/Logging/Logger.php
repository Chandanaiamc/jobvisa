<?php

declare(strict_types=1);

namespace JobVisa\App\Logging;

use JobVisa\App\Domain\Observability\Services\RequestContext;

/**
 * Simple file-based logger with secret redaction.
 */
final class Logger
{
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_SECURITY = 'security';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function security(string $message, array $context = []): void
    {
        self::write(self::LEVEL_SECURITY, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        $directory = base_path('storage/logs');

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $file = $directory . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d') . '.log';
        if (!array_key_exists('request_id', $context)) {
            $requestId = RequestContext::currentId();
            if ($requestId !== null) {
                $context['request_id'] = $requestId;
            }
        }
        $context = self::redact($context);

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            self::redactString($message),
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'password_hash',
            'token',
            'token_hash',
            '_token',
            'csrf',
            'remember_token',
            'authorization',
            'db_password',
            'database_password',
            'session_id',
            'phpsessid',
            'api_key',
            'app_key',
            'secret',
            'bearer',
            'mysql_pwd',
            'plain_token',
        ];

        $clean = [];

        foreach ($context as $key => $value) {
            $keyLower = strtolower((string) $key);

            foreach ($sensitive as $needle) {
                if (str_contains($keyLower, $needle)) {
                    $clean[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $clean[$key] = self::redact($value);
                continue;
            }

            if (is_string($value)) {
                $clean[$key] = self::redactString($value);
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private static function redactString(string $value): string
    {
        // Avoid writing obvious session cookie values.
        if (preg_match('/PHPSESSID|jobvisa_session/i', $value) === 1) {
            return '[REDACTED_SESSION]';
        }

        return $value;
    }
}

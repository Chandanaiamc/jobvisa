<?php

declare(strict_types=1);

namespace JobVisa\App\Foundation;

use JobVisa\App\Domain\Observability\Services\ObservabilityService;
use JobVisa\App\Domain\Observability\Services\RequestContext;
use JobVisa\App\Logging\Logger;
use JobVisa\App\Security\SecurityHelper;
use Throwable;

/**
 * Global exception / error handler.
 */
final class ExceptionHandler
{
    private static bool $registered = false;

    /**
     * Register PHP error and exception handlers.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    public static function handleException(Throwable $exception): void
    {
        $context = [
            'type' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'ip' => SecurityHelper::clientIp(),
            'request_id' => RequestContext::currentId(),
        ];

        Logger::error($exception->getMessage(), $context);

        try {
            if (function_exists('container')) {
                container(ObservabilityService::class)->trackError($exception->getMessage(), $context);
            }
        } catch (Throwable) {
            // Never break error rendering if observability is unavailable.
        }

        self::render($exception);
    }

    /**
     * Convert PHP errors into ErrorException.
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Catch fatal errors on shutdown.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        if (!in_array($error['type'], $fatals, true)) {
            return;
        }

        self::handleException(new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    private static function render(Throwable $exception): void
    {
        if (self::wantsJsonApi()) {
            self::renderApi($exception);

            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        $debug = (bool) config('app.debug', false);

        if ($debug) {
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }

            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Application Error</title></head><body>';
            echo '<h1>Application Error</h1>';
            echo '<p><strong>' . SecurityHelper::escape($exception::class) . '</strong></p>';
            echo '<p>' . SecurityHelper::escape($exception->getMessage()) . '</p>';
            echo '<p>' . SecurityHelper::escape($exception->getFile()) . ':' . (int) $exception->getLine() . '</p>';
            echo '<pre>' . SecurityHelper::escape($exception->getTraceAsString()) . '</pre>';
            echo '</body></html>';

            return;
        }

        $view = base_path('app/views/errors/500.php');

        if (is_file($view)) {
            require $view;
            return;
        }

        echo 'An unexpected error occurred. Please try again later.';
    }

    private static function wantsJsonApi(): bool
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '');

        return str_contains($path, '/api/');
    }

    private static function renderApi(Throwable $exception): void
    {
        $debug = (bool) config('app.debug', false);
        $status = 500;
        $code = 'server_error';
        $message = $debug ? $exception->getMessage() : 'An unexpected error occurred.';
        $details = [];

        if ($exception instanceof \JobVisa\App\Domain\Api\Http\ApiException) {
            $status = $exception->status();
            $code = $exception->errorCode();
            $message = $exception->getMessage();
            $details = $exception->details();
        }

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? new \stdClass() : $details,
            ],
            'request_id' => RequestContext::currentId() ?? '',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

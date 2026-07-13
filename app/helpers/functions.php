<?php

declare(strict_types=1);

/**
 * Global helper functions for the JobVisa.lk framework.
 */

use App\Core\Config;
use App\Core\View;
use JobVisa\App\Container\Container;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SecurityHelper;

if (!function_exists('base_path')) {
    /**
     * Get an absolute path from the project root.
     */
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        $path = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('env')) {
    /**
     * Read an environment value loaded from .env.
     */
    function env(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Read a configuration value using dot notation.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template to a string.
     *
     * @param  array<string, mixed>  $data
     */
    function view(string $name, array $data = []): string
    {
        return (new View())->render($name, $data);
    }
}

if (!function_exists('e')) {
    /**
     * Escape a string for safe HTML output.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect the browser to another URL.
     */
    function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('asset')) {
    /**
     * Build a public asset URL.
     */
    function asset(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        $path = ltrim($path, '/');

        return $baseUrl . '/public/assets/' . $path;
    }
}

if (!function_exists('url')) {
    /**
     * Build an application URL from a path.
     */
    function url(string $path = ''): string
    {
        return app_url($path);
    }
}

if (!function_exists('app_url')) {
    /**
     * Build a front-controller URL under /public for the MVC app.
     */
    function app_url(string $path = ''): string
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');

        if (!str_ends_with(strtolower($baseUrl), '/public')) {
            $baseUrl .= '/public';
        }

        $path = trim($path, '/');

        return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Current CSRF token string.
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * HTML hidden input containing the CSRF token.
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('security_escape')) {
    /**
     * Escape HTML via SecurityHelper.
     */
    function security_escape(?string $value): string
    {
        return SecurityHelper::escape($value);
    }
}

if (!function_exists('container')) {
    /**
     * Get the application container, or resolve an abstract from it.
     */
    function container(?string $abstract = null): mixed
    {
        $container = $GLOBALS['jobvisa_container'] ?? null;

        if (!$container instanceof Container) {
            throw new \RuntimeException('Application container is not bootstrapped.');
        }

        if ($abstract === null) {
            return $container;
        }

        return $container->get($abstract);
    }
}

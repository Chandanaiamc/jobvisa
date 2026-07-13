<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configuration loader.
 *
 * Reads .env values and PHP config files from the /config directory.
 * Does not establish database connections.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    private static bool $envLoaded = false;

    /**
     * Load key/value pairs from a .env file into the process environment.
     */
    public static function loadEnv(string $path): void
    {
        if (self::$envLoaded || !is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv("{$name}={$value}");
        }

        self::$envLoaded = true;
    }

    /**
     * Load all PHP config files from a directory into memory.
     *
     * Each file should return an array. The filename (without .php)
     * becomes the top-level config key (e.g. app.php → "app").
     */
    public static function load(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob(rtrim($directory, '/\\') . '/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');

            $data = require $file;

            if (is_array($data)) {
                self::$items[$key] = $data;
            }
        }
    }

    /**
     * Get a config value using dot notation (e.g. "app.name").
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a config value using dot notation.
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $items = &self::$items;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $items[$segment] = $value;
                return;
            }

            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items = &$items[$segment];
        }
    }

    /**
     * Return all loaded configuration.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }
}

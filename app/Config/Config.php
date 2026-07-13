<?php

declare(strict_types=1);

namespace JobVisa\App\Config;

use RuntimeException;

/**
 * Request-scoped configuration manager with dot-notation access.
 */
final class Config
{
    private string $directory;

    /** @var array<string, array<string, mixed>> */
    private array $items = [];

    /** @var array<string, true> */
    private array $loaded = [];

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/\\');
    }

    /**
     * Load every PHP config file in the config directory.
     */
    public function loadAll(): void
    {
        if (!is_dir($this->directory)) {
            throw new RuntimeException('Configuration directory is not available.');
        }

        $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            throw new RuntimeException('Unable to read configuration directory.');
        }

        foreach ($files as $file) {
            $this->loadFile(basename($file, '.php'));
        }
    }

    /**
     * Load a single configuration group (cached for the request).
     */
    public function loadFile(string $group): void
    {
        if (isset($this->loaded[$group])) {
            return;
        }

        $path = $this->directory . DIRECTORY_SEPARATOR . $group . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('Configuration group could not be loaded.');
        }

        $data = require $path;

        if (!is_array($data)) {
            throw new RuntimeException('Configuration group must return an array.');
        }

        $this->items[$group] = $data;
        $this->loaded[$group] = true;
    }

    /**
     * Get a value using dot notation (e.g. app.name).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }

        $segments = explode('.', $key);
        $group = array_shift($segments);

        if ($group === null || $group === '') {
            return $default;
        }

        $this->ensureGroup($group);

        $value = $this->items[$group] ?? null;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Determine whether a configuration key exists.
     */
    public function has(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        $segments = explode('.', $key);
        $group = array_shift($segments);

        if ($group === null || $group === '') {
            return false;
        }

        try {
            $this->ensureGroup($group);
        } catch (RuntimeException) {
            return false;
        }

        $value = $this->items[$group] ?? null;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Whether a config group file has been loaded into memory.
     */
    public function isLoaded(string $group): bool
    {
        return isset($this->loaded[$group]);
    }

    private function ensureGroup(string $group): void
    {
        if (isset($this->loaded[$group])) {
            return;
        }

        $path = $this->directory . DIRECTORY_SEPARATOR . $group . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('Configuration group could not be loaded.');
        }

        $this->loadFile($group);
    }
}

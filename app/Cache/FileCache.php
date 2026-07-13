<?php

declare(strict_types=1);

namespace JobVisa\App\Cache;

/**
 * Atomic-ish file cache under storage/cache.
 */
final class FileCache implements CacheInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly string $prefix = 'jobvisa',
    ) {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return $default;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $default;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('value', $payload)) {
            @unlink($path);

            return $default;
        }

        $expires = (int) $payload['expires_at'];
        if ($expires > 0 && $expires < time()) {
            @unlink($path);

            return $default;
        }

        return $payload['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        $ttlSeconds = max(1, $ttlSeconds);
        $payload = [
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $path = $this->path($key);
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }

        return @rename($tmp, $path);
    }

    public function forever(string $key, mixed $value): bool
    {
        $payload = [
            'expires_at' => 0,
            'value' => $value,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }
        $path = $this->path($key);
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }

        return @rename($tmp, $path);
    }

    public function forget(string $key): bool
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return true;
        }

        return @unlink($path);
    }

    public function flush(): bool
    {
        if (!is_dir($this->directory)) {
            return true;
        }
        $ok = true;
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $file) {
            if (!@unlink($file)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    private function path(string $key): string
    {
        $hash = hash('sha256', $this->prefix . ':' . $key);

        return $this->directory . DIRECTORY_SEPARATOR . $hash . '.cache';
    }
}

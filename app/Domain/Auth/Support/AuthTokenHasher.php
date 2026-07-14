<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Support;

use DateTimeImmutable;
use DateTimeZone;
use JobVisa\App\Domain\Api\Http\ApiException;

/**
 * APP_KEY-backed HMAC hashing and UTC expiry helpers for auth tokens.
 */
final class AuthTokenHasher
{
    public function hash(string $plain): string
    {
        return hash_hmac('sha256', $plain, $this->pepper());
    }

    public function utcNow(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function utcPlusSeconds(int $seconds): string
    {
        return $this->utcNow()->modify('+' . max(0, $seconds) . ' seconds')->format('Y-m-d H:i:s');
    }

    public function utcPlusDays(int $days): string
    {
        return $this->utcNow()->modify('+' . max(0, $days) . ' days')->format('Y-m-d H:i:s');
    }

    public function isExpiredUtc(mixed $expiresAt): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }
        $raw = trim((string) $expiresAt);
        $utc = new DateTimeZone('UTC');
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, $utc);
        if ($parsed === false) {
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $raw, $utc);
        }
        if ($parsed === false) {
            try {
                $parsed = new DateTimeImmutable($raw, $utc);
            } catch (\Throwable) {
                return true;
            }
        }

        return $parsed->getTimestamp() < time();
    }

    public function familyId(): string
    {
        try {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } catch (\Throwable) {
            return bin2hex(random_bytes(16));
        }
    }

    private function pepper(): string
    {
        $pepper = trim((string) env('APP_KEY', ''));
        if ($pepper === '') {
            $pepper = trim((string) config('app.key', ''));
        }
        if ($pepper !== '') {
            return $pepper;
        }

        $env = strtolower((string) config('app.env', 'local'));
        if (in_array($env, ['local', 'testing', 'development'], true)) {
            return (string) config('app.name', 'JobVisa.lk') . '|local-dev-only';
        }

        throw new ApiException(
            'misconfigured',
            'APP_KEY is required for auth token hashing in this environment.',
            503
        );
    }
}

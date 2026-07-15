<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Auth;

use DateTimeImmutable;
use DateTimeZone;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Security\SecurityHelper;

/**
 * Issues and authenticates personal access tokens (never stores plaintext).
 */
final class PersonalAccessTokenService
{
    public function __construct(
        private readonly PersonalAccessTokenRepository $tokens,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @return array{token: string, id: int, name: string, expires_at: ?string, prefix: string}
     */
    public function create(int $userId, string $name, ?int $ttlDays = null): array
    {
        if (!$this->tokens->ensureSchemaReady()) {
            throw new \RuntimeException('API token schema is not installed. Apply migration 064.');
        }

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 120) {
            throw ApiException::validation('Invalid token name.', ['name' => ['Name is required (max 120).']]);
        }

        $user = $this->users->findActiveById($userId);
        if ($user === null) {
            throw ApiException::forbidden('User not found or inactive.');
        }

        $prefix = (string) config('api.token_prefix', 'jv1_');
        $bytes = (int) config('api.token_bytes', 32);
        $plain = $prefix . bin2hex(random_bytes($bytes));
        $hash = $this->hash($plain);

        $ttl = $ttlDays ?? (int) config('api.token_default_ttl_days', 365);
        $expires = $ttl > 0
            ? (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->modify('+' . $ttl . ' days')
                ->format('Y-m-d H:i:s')
            : null;

        $id = $this->tokens->create([
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => $hash,
            'token_prefix' => mb_substr($plain, 0, 12),
            'abilities' => null, // reserved; not enforced in 4.5.x
            'expires_at' => $expires,
        ]);

        return [
            'token' => $plain,
            'id' => $id,
            'name' => $name,
            'expires_at' => $expires,
            'prefix' => mb_substr($plain, 0, 12),
        ];
    }

    /**
     * Authenticate from Authorization header (PAT path). Prefer ApiBearerAuthenticator.
     */
    public function authenticateFromRequest(): void
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
            throw ApiException::unauthorized('Bearer token required.');
        }

        $this->authenticatePlain($m[1], 'pat');
    }

    /**
     * Authenticate a plaintext PAT and set ApiAuth.
     */
    public function authenticatePlain(string $plain, string $kind = 'pat'): void
    {
        if (!$this->tokens->ensureSchemaReady()) {
            throw ApiException::unauthorized('API authentication unavailable.');
        }

        $row = $this->tokens->findByHash($this->hash($plain));
        if ($row === null) {
            throw ApiException::unauthorized('Invalid access token.');
        }

        if (!empty($row['revoked_at'])) {
            throw ApiException::tokenRevoked();
        }

        if ($this->isExpired($row['expires_at'] ?? null)) {
            throw ApiException::tokenExpired();
        }

        $user = $this->users->findActiveById((int) $row['user_id']);
        if ($user === null) {
            throw ApiException::unauthorized('Token user is inactive.');
        }

        // Never expose password hash to API context.
        unset($user['password_hash']);

        $ip = SecurityHelper::clientIp();
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->tokens->touchUsage((int) $row['id'], $ip, $ua);

        ApiAuth::login($user, [
            'id' => (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'prefix' => (string) ($row['token_prefix'] ?? ''),
            'kind' => $kind,
        ]);
    }

    public function revoke(int $userId, int $tokenId): bool
    {
        return $this->tokens->revoke($tokenId, $userId);
    }

    /**
     * List long-lived PATs only (excludes legacy Phase-1 access:* row names).
     *
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $rows = $this->tokens->listForUser($userId);
        $filtered = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if (str_starts_with($name, 'access:')) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    /**
     * HMAC-SHA256 of the plaintext token. Requires a non-empty APP_KEY outside local/testing.
     */
    public function hash(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, $this->pepper());
    }

    /**
     * Compare expires_at as UTC wall-clock (how create() stores it).
     */
    public function isExpired(mixed $expiresAt): bool
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
            // Deterministic local-only fallback so dev bootstraps without APP_KEY.
            return (string) config('app.name', 'JobVisa.lk') . '|local-dev-only';
        }

        throw new ApiException(
            'misconfigured',
            'APP_KEY is required for API token hashing in this environment.',
            503
        );
    }
}

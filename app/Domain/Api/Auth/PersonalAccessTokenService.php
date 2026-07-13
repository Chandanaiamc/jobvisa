<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Auth;

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
        $expires = $ttl > 0 ? gmdate('Y-m-d H:i:s', time() + ($ttl * 86400)) : null;

        $id = $this->tokens->create([
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => $hash,
            'token_prefix' => mb_substr($plain, 0, 12),
            'abilities' => null,
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
     * Authenticate from Authorization header. Sets ApiAuth on success.
     */
    public function authenticateFromRequest(): void
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
            throw ApiException::unauthorized('Bearer token required.');
        }

        $plain = $m[1];
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

        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
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
        ]);
    }

    public function revoke(int $userId, int $tokenId): bool
    {
        return $this->tokens->revoke($tokenId, $userId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return $this->tokens->listForUser($userId);
    }

    public function hash(string $plainToken): string
    {
        $pepper = (string) (env('APP_KEY', config('app.name', 'JobVisa.lk')));

        return hash_hmac('sha256', $plainToken, $pepper);
    }
}

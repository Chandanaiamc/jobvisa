<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Auth;

/**
 * Request-scoped API authentication context (bearer tokens; no session).
 */
final class ApiAuth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    /** @var array<string, mixed>|null */
    private static ?array $token = null;

    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>  $token
     */
    public static function login(array $user, array $token): void
    {
        self::$user = $user;
        self::$token = $token;
    }

    public static function clear(): void
    {
        self::$user = null;
        self::$token = null;
    }

    public static function check(): bool
    {
        return self::$user !== null && (int) (self::$user['id'] ?? 0) > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        $id = (int) (self::$user['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public static function role(): string
    {
        return (string) (self::$user['role'] ?? '');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function token(): ?array
    {
        return self::$token;
    }

    public static function tokenId(): ?int
    {
        $id = (int) (self::$token['id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * Actor payload for existing domain policies/services.
     *
     * @return array<string, mixed>
     */
    public static function actor(): array
    {
        if (!self::check()) {
            return [];
        }

        return self::$user ?? [];
    }
}

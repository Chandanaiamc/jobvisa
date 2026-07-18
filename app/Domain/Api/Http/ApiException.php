<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Http;

/**
 * Throwable API errors mapped to JSON envelopes.
 */
final class ApiException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $status = 400,
        private readonly array $details = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function unauthorized(string $message = 'Unauthenticated.'): self
    {
        return new self('unauthorized', $message, 401);
    }

    public static function forbidden(string $message = 'Forbidden.'): self
    {
        return new self('forbidden', $message, 403);
    }

    public static function notFound(string $message = 'Resource not found.'): self
    {
        return new self('not_found', $message, 404);
    }

    public static function conflict(string $message = 'Conflict.', array $details = []): self
    {
        return new self('conflict', $message, 409, $details);
    }

    public static function validation(string $message, array $details = []): self
    {
        return new self('validation_error', $message, 422, $details);
    }

    public static function rateLimited(string $message = 'Too many requests.', int $retryAfter = 60): self
    {
        return new self('rate_limited', $message, 429, ['retry_after' => $retryAfter]);
    }

    public static function tokenExpired(): self
    {
        return new self('token_expired', 'Access token has expired.', 401);
    }

    public static function tokenRevoked(): self
    {
        return new self('token_revoked', 'Access token has been revoked.', 401);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}

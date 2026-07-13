<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Observability\Services;

/**
 * Request-scoped correlation context.
 */
final class RequestContext
{
    private static ?self $instance = null;

    public function __construct(
        private string $requestId,
        private readonly float $startedAt,
    ) {
    }

    public static function boot(self $context): void
    {
        self::$instance = $context;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public static function currentId(): ?string
    {
        return self::$instance?->requestId;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function startedAt(): float
    {
        return $this->startedAt;
    }

    public function elapsedMs(): float
    {
        return (hrtime(true) - $this->startedAt) / 1e6;
    }

    public static function generateId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            return str_replace('.', '', uniqid('req', true));
        }
    }
}

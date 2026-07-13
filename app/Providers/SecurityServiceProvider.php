<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Domain\Security\Services\PasswordPolicy;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;
use JobVisa\App\Domain\Security\Services\SecurityHardeningService;
use JobVisa\App\Foundation\ExceptionHandler;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SecurityHelper;

/**
 * Security foundation + hardening bindings (Sprint 4.7).
 */
final class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Csrf::class, static fn (): Csrf => new Csrf());
        $this->container->singleton(SecurityHelper::class, static fn (): SecurityHelper => new SecurityHelper());
        $this->container->singleton(ExceptionHandler::class, static fn (): ExceptionHandler => new ExceptionHandler());
        $this->container->singleton(PasswordPolicy::class, static fn (): PasswordPolicy => new PasswordPolicy());
        $this->container->singleton(SecurityAuditLogger::class, static fn (): SecurityAuditLogger => new SecurityAuditLogger());
        $this->container->singleton(SecurityHardeningService::class, static function ($c): SecurityHardeningService {
            return new SecurityHardeningService(
                $c->get(PasswordPolicy::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
    }

    public function boot(): void
    {
        ExceptionHandler::register();
    }
}

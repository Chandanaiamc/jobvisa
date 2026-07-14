<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Auth\PasswordHasher;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Auth\UserRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenRepository;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Auth\Repositories\DeviceSessionRepository;
use JobVisa\App\Domain\Auth\Repositories\MfaFactorRepository;
use JobVisa\App\Domain\Auth\Repositories\RefreshTokenRepository;
use JobVisa\App\Domain\Auth\Services\AuthLifecycleService;
use JobVisa\App\Domain\Auth\Services\DeviceSessionService;
use JobVisa\App\Domain\Auth\Services\LogoutEverywhereService;
use JobVisa\App\Domain\Auth\Services\MfaFactorService;
use JobVisa\App\Domain\Auth\Services\RefreshTokenService;
use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;

/**
 * Auth Token Lifecycle v2 bindings.
 */
final class AuthLifecycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(AuthTokenHasher::class, static fn (): AuthTokenHasher => new AuthTokenHasher());
        $this->container->singleton(DeviceSessionRepository::class, static fn (): DeviceSessionRepository => new DeviceSessionRepository());
        $this->container->singleton(RefreshTokenRepository::class, static fn (): RefreshTokenRepository => new RefreshTokenRepository());
        $this->container->singleton(MfaFactorRepository::class, static fn (): MfaFactorRepository => new MfaFactorRepository());

        $this->container->singleton(DeviceSessionService::class, static function ($c): DeviceSessionService {
            return new DeviceSessionService(
                $c->get(DeviceSessionRepository::class),
                $c->get(AuthTokenHasher::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
        $this->container->singleton(RefreshTokenService::class, static function ($c): RefreshTokenService {
            return new RefreshTokenService(
                $c->get(RefreshTokenRepository::class),
                $c->get(AuthTokenHasher::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
        $this->container->singleton(MfaFactorService::class, static function ($c): MfaFactorService {
            return new MfaFactorService(
                $c->get(MfaFactorRepository::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
        $this->container->singleton(LogoutEverywhereService::class, static function ($c): LogoutEverywhereService {
            return new LogoutEverywhereService(
                $c->get(RefreshTokenService::class),
                $c->get(DeviceSessionService::class),
                $c->get(PersonalAccessTokenRepository::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
        $this->container->singleton(AuthLifecycleService::class, static function ($c): AuthLifecycleService {
            return new AuthLifecycleService(
                $c->get(AuthManager::class),
                $c->get(UserRepository::class),
                $c->get(PasswordHasher::class),
                $c->get(PersonalAccessTokenService::class),
                $c->get(RefreshTokenService::class),
                $c->get(DeviceSessionService::class),
                $c->get(LogoutEverywhereService::class),
                $c->get(MfaFactorService::class),
                $c->get(AuthTokenHasher::class),
                $c->get(EmailVerificationService::class),
                $c->get(PasswordResetService::class),
                $c->get(SecurityAuditLogger::class),
            );
        });
    }

    public function boot(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Providers;

use App\Core\Database;
use JobVisa\App\Domain\Application\Repositories\ApplicationRepositoryInterface as DomainApplicationRepositoryInterface;
use JobVisa\App\Domain\Company\Repositories\CompanyRepositoryInterface as DomainCompanyRepositoryInterface;
use JobVisa\App\Domain\Job\Repositories\JobRepositoryInterface as DomainJobRepositoryInterface;
use JobVisa\App\Domain\User\Repositories\UserRepositoryInterface as DomainUserRepositoryInterface;
use JobVisa\App\Repositories\ApplicationRepository;
use JobVisa\App\Repositories\CompanyRepository;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface as InfrastructureApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CompanyRepositoryInterface as InfrastructureCompanyRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface as InfrastructureJobRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ScheduledInterviewRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserRepositoryInterface as InfrastructureUserRepositoryInterface;
use JobVisa\App\Repositories\JobRepository;
use JobVisa\App\Repositories\ScheduledInterviewRepository;
use JobVisa\App\Repositories\UserRepository;
use PDO;

/**
 * Binds enterprise repositories into the service container.
 *
 * Does not alter Auth\UserRepository or authentication flows.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(PDO::class, static function (): PDO {
            return Database::connection();
        });

        $this->container->singleton(UserRepository::class, static function ($container): UserRepository {
            return new UserRepository($container->get(PDO::class));
        });

        $this->container->singleton(CompanyRepository::class, static function ($container): CompanyRepository {
            return new CompanyRepository($container->get(PDO::class));
        });

        $this->container->singleton(JobRepository::class, static function ($container): JobRepository {
            return new JobRepository($container->get(PDO::class));
        });

        $this->container->singleton(ApplicationRepository::class, static function ($container): ApplicationRepository {
            return new ApplicationRepository($container->get(PDO::class));
        });

        $this->container->singleton(ScheduledInterviewRepository::class, static function ($container): ScheduledInterviewRepository {
            return new ScheduledInterviewRepository($container->get(PDO::class));
        });

        // Infrastructure contracts
        $this->container->singleton(
            InfrastructureUserRepositoryInterface::class,
            static fn ($c) => $c->get(UserRepository::class)
        );
        $this->container->singleton(
            InfrastructureCompanyRepositoryInterface::class,
            static fn ($c) => $c->get(CompanyRepository::class)
        );
        $this->container->singleton(
            InfrastructureJobRepositoryInterface::class,
            static fn ($c) => $c->get(JobRepository::class)
        );
        $this->container->singleton(
            InfrastructureApplicationRepositoryInterface::class,
            static fn ($c) => $c->get(ApplicationRepository::class)
        );
        $this->container->singleton(
            ScheduledInterviewRepositoryInterface::class,
            static fn ($c) => $c->get(ScheduledInterviewRepository::class)
        );

        // Domain contracts (same implementations — entity findById)
        $this->container->singleton(
            DomainUserRepositoryInterface::class,
            static fn ($c) => $c->get(UserRepository::class)
        );
        $this->container->singleton(
            DomainCompanyRepositoryInterface::class,
            static fn ($c) => $c->get(CompanyRepository::class)
        );
        $this->container->singleton(
            DomainJobRepositoryInterface::class,
            static fn ($c) => $c->get(JobRepository::class)
        );
        $this->container->singleton(
            DomainApplicationRepositoryInterface::class,
            static fn ($c) => $c->get(ApplicationRepository::class)
        );
    }

    public function boot(): void
    {
        // Bindings only — no eager DB queries.
    }
}

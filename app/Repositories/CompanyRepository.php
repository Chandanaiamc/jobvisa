<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\Company\Entities\Company;
use JobVisa\App\Domain\Company\Repositories\CompanyRepositoryInterface as DomainCompanyRepositoryInterface;
use JobVisa\App\Repositories\Contracts\CompanyRepositoryInterface as InfrastructureCompanyRepositoryInterface;

/**
 * Enterprise company repository.
 */
final class CompanyRepository extends BaseRepository implements
    InfrastructureCompanyRepositoryInterface,
    DomainCompanyRepositoryInterface
{
    protected string $table = 'companies';

    public function findById(int|string $id): ?Company
    {
        $row = $this->findRecordById($id);

        if ($row === null) {
            return null;
        }

        return Company::reconstitute((int) $row['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRecordById(int|string $id): ?array
    {
        if ((int) $id < 1) {
            return null;
        }

        return $this->findRowById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `companies` WHERE `slug` = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public function exists(int|string $id): bool
    {
        if ((int) $id < 1) {
            return false;
        }

        return $this->rowExists($id);
    }
}

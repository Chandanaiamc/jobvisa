<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumePersonalRepositoryInterface;

final class ResumePersonalRepository extends BaseRepository implements ResumePersonalRepositoryInterface
{
    protected string $table = 'resume_personal';

    public function findByResumeId(int $resumeId): ?array
    {
        if ($resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resume_personal` WHERE `resume_id` = :resume_id LIMIT 1',
            ['resume_id' => $resumeId]
        );
    }

    public function upsert(int $resumeId, array $data): void
    {
        $existing = $this->findByResumeId($resumeId);
        $payload = [
            'passport_number' => $data['passport_number'] ?? null,
            'passport_expiry' => $data['passport_expiry'] ?? null,
            'salary_currency' => $data['salary_currency'] ?? null,
            'visa_status' => $data['visa_status'] ?? null,
            'driving_licence_status' => $data['driving_licence_status'] ?? null,
        ];

        if ($existing === null) {
            $this->query(
                'INSERT INTO `resume_personal`
                    (`resume_id`, `passport_number`, `passport_expiry`, `salary_currency`,
                     `visa_status`, `driving_licence_status`)
                 VALUES
                    (:resume_id, :passport_number, :passport_expiry, :salary_currency,
                     :visa_status, :driving_licence_status)',
                ['resume_id' => $resumeId] + $payload
            );

            return;
        }

        $this->query(
            'UPDATE `resume_personal` SET
                `passport_number` = :passport_number,
                `passport_expiry` = :passport_expiry,
                `salary_currency` = :salary_currency,
                `visa_status` = :visa_status,
                `driving_licence_status` = :driving_licence_status
             WHERE `resume_id` = :resume_id',
            ['resume_id' => $resumeId] + $payload
        );
    }

    public function listPreferredCountryIds(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        $rows = $this->fetchAll(
            'SELECT `country_id` FROM `resume_preferred_countries`
             WHERE `resume_id` = :resume_id
             ORDER BY `sort_order` ASC, `country_id` ASC',
            ['resume_id' => $resumeId]
        );

        return array_map(static fn (array $row): int => (int) $row['country_id'], $rows);
    }

    public function syncPreferredCountries(int $resumeId, array $countryIds): void
    {
        $this->query(
            'DELETE FROM `resume_preferred_countries` WHERE `resume_id` = :resume_id',
            ['resume_id' => $resumeId]
        );

        $order = 0;

        foreach ($countryIds as $countryId) {
            $id = (int) $countryId;

            if ($id < 1) {
                continue;
            }

            $this->query(
                'INSERT INTO `resume_preferred_countries` (`resume_id`, `country_id`, `sort_order`)
                 VALUES (:resume_id, :country_id, :sort_order)',
                ['resume_id' => $resumeId, 'country_id' => $id, 'sort_order' => $order++]
            );
        }
    }

    public function countryExists(int $countryId): bool
    {
        if ($countryId < 1) {
            return false;
        }

        return (bool) $this->query(
            'SELECT 1 FROM `countries` WHERE `id` = :id AND `is_active` = 1 LIMIT 1',
            ['id' => $countryId]
        )->fetchColumn();
    }
}

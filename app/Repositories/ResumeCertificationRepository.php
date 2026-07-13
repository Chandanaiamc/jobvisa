<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;

final class ResumeCertificationRepository extends BaseRepository implements ResumeCertificationRepositoryInterface
{
    protected string $table = 'resume_certifications';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resume_certifications`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `is_primary` DESC, `sort_order` ASC, `issue_date` DESC, `id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resume_certifications`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_certifications`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_certifications`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_certifications`
                (`resume_id`, `name`, `issuing_organization`, `credential_id`, `credential_url`,
                 `issue_date`, `expiry_date`, `does_not_expire`, `license_number`, `verification_url`,
                 `certificate_path`, `is_primary`, `sort_order`, `status`)
             VALUES
                (:resume_id, :name, :issuing_organization, :credential_id, :credential_url,
                 :issue_date, :expiry_date, :does_not_expire, :license_number, :verification_url,
                 :certificate_path, :is_primary, :sort_order, :status)',
            $this->bindPayload($resumeId, $data)
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, int $resumeId, array $data): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $payload = $this->bindPayload($resumeId, $data);
        unset($payload['certificate_path']);
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_certifications` SET
                `name` = :name,
                `issuing_organization` = :issuing_organization,
                `credential_id` = :credential_id,
                `credential_url` = :credential_url,
                `issue_date` = :issue_date,
                `expiry_date` = :expiry_date,
                `does_not_expire` = :does_not_expire,
                `license_number` = :license_number,
                `verification_url` = :verification_url,
                `is_primary` = :is_primary,
                `sort_order` = :sort_order,
                `status` = :status
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            $payload
        );

        return true;
    }

    public function delete(int $id, int $resumeId): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_certifications`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `is_primary` = 0, `status` = \'archived\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function restore(int $id, int $resumeId): bool
    {
        if ($this->findDeletedOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_certifications`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateCertificatePath(int $id, int $resumeId, ?string $path): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_certifications` SET `certificate_path` = :path
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['path' => $path, 'id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function clearPrimaryExcept(int $resumeId, ?int $exceptId = null): void
    {
        if ($resumeId < 1) {
            return;
        }

        if ($exceptId !== null && $exceptId > 0) {
            $this->query(
                'UPDATE `resume_certifications` SET `is_primary` = 0
                 WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL AND `id` <> :except_id',
                ['resume_id' => $resumeId, 'except_id' => $exceptId]
            );

            return;
        }

        $this->query(
            'UPDATE `resume_certifications` SET `is_primary` = 0
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        );
    }

    public function reorder(int $resumeId, array $orderedIds): void
    {
        $order = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $this->query(
                'UPDATE `resume_certifications` SET `sort_order` = :sort_order
                 WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
                ['sort_order' => $order++, 'id' => $id, 'resume_id' => $resumeId]
            );
        }
    }

    public function countActive(int $resumeId): int
    {
        if ($resumeId < 1) {
            return 0;
        }

        return (int) $this->query(
            'SELECT COUNT(*) FROM `resume_certifications`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['resume_id' => $resumeId]
        )->fetchColumn();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bindPayload(int $resumeId, array $data): array
    {
        return [
            'resume_id' => $resumeId,
            'name' => $data['name'],
            'issuing_organization' => $data['issuing_organization'],
            'credential_id' => $data['credential_id'] ?? null,
            'credential_url' => $data['credential_url'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'does_not_expire' => !empty($data['does_not_expire']) ? 1 : 0,
            'license_number' => $data['license_number'] ?? null,
            'verification_url' => $data['verification_url'] ?? null,
            'certificate_path' => $data['certificate_path'] ?? null,
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => (string) ($data['status'] ?? 'active'),
        ];
    }
}

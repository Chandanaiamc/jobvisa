<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;

final class ResumeProjectRepository extends BaseRepository implements ResumeProjectRepositoryInterface
{
    protected string $table = 'resume_projects';

    public function listByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resume_projects`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NULL
             ORDER BY `sort_order` ASC, `start_date` DESC, `id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listPublicByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resume_projects`
             WHERE `resume_id` = :resume_id
               AND `deleted_at` IS NULL
               AND `visibility` = \'public\'
               AND `status` = \'active\'
             ORDER BY `sort_order` ASC, `start_date` DESC, `id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function listDeletedByResumeId(int $resumeId): array
    {
        if ($resumeId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resume_projects`
             WHERE `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             ORDER BY `deleted_at` DESC, `id` DESC',
            ['resume_id' => $resumeId]
        );
    }

    public function findOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_projects`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function findDeletedOwned(int $id, int $resumeId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resume_projects`
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL
             LIMIT 1',
            ['id' => $id, 'resume_id' => $resumeId]
        );
    }

    public function create(int $resumeId, array $data): int
    {
        $this->query(
            'INSERT INTO `resume_projects`
                (`resume_id`, `title`, `client_name`, `organization`, `role`, `description`,
                 `technologies`, `project_url`, `github_url`, `portfolio_url`, `video_demo_url`,
                 `image`, `document`, `start_date`, `end_date`, `currently_working`, `team_size`,
                 `project_type`, `industry`, `location`, `achievements`, `responsibilities`,
                 `status`, `visibility`, `sort_order`)
             VALUES
                (:resume_id, :title, :client_name, :organization, :role, :description,
                 :technologies, :project_url, :github_url, :portfolio_url, :video_demo_url,
                 :image, :document, :start_date, :end_date, :currently_working, :team_size,
                 :project_type, :industry, :location, :achievements, :responsibilities,
                 :status, :visibility, :sort_order)',
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
        unset($payload['image'], $payload['document']);
        $payload['id'] = $id;

        $this->query(
            'UPDATE `resume_projects` SET
                `title` = :title,
                `client_name` = :client_name,
                `organization` = :organization,
                `role` = :role,
                `description` = :description,
                `technologies` = :technologies,
                `project_url` = :project_url,
                `github_url` = :github_url,
                `portfolio_url` = :portfolio_url,
                `video_demo_url` = :video_demo_url,
                `start_date` = :start_date,
                `end_date` = :end_date,
                `currently_working` = :currently_working,
                `team_size` = :team_size,
                `project_type` = :project_type,
                `industry` = :industry,
                `location` = :location,
                `achievements` = :achievements,
                `responsibilities` = :responsibilities,
                `status` = :status,
                `visibility` = :visibility,
                `sort_order` = :sort_order
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
            'UPDATE `resume_projects`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `status` = \'archived\'
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
            'UPDATE `resume_projects`
             SET `deleted_at` = NULL, `status` = \'active\'
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NOT NULL',
            ['id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateImagePath(int $id, int $resumeId, ?string $path): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_projects` SET `image` = :path
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['path' => $path, 'id' => $id, 'resume_id' => $resumeId]
        );

        return true;
    }

    public function updateDocumentPath(int $id, int $resumeId, ?string $path): bool
    {
        if ($this->findOwned($id, $resumeId) === null) {
            return false;
        }

        $this->query(
            'UPDATE `resume_projects` SET `document` = :path
             WHERE `id` = :id AND `resume_id` = :resume_id AND `deleted_at` IS NULL',
            ['path' => $path, 'id' => $id, 'resume_id' => $resumeId]
        );

        return true;
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
                'UPDATE `resume_projects` SET `sort_order` = :sort_order
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
            'SELECT COUNT(*) FROM `resume_projects`
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
            'title' => $data['title'],
            'client_name' => $data['client_name'] ?? null,
            'organization' => $data['organization'] ?? null,
            'role' => $data['role'] ?? null,
            'description' => $data['description'] ?? null,
            'technologies' => $data['technologies'] ?? null,
            'project_url' => $data['project_url'] ?? null,
            'github_url' => $data['github_url'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'video_demo_url' => $data['video_demo_url'] ?? null,
            'image' => $data['image'] ?? null,
            'document' => $data['document'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'currently_working' => !empty($data['currently_working']) ? 1 : 0,
            'team_size' => isset($data['team_size']) && $data['team_size'] !== null && $data['team_size'] !== ''
                ? (int) $data['team_size']
                : null,
            'project_type' => $data['project_type'] ?? null,
            'industry' => $data['industry'] ?? null,
            'location' => $data['location'] ?? null,
            'achievements' => $data['achievements'] ?? null,
            'responsibilities' => $data['responsibilities'] ?? null,
            'status' => (string) ($data['status'] ?? 'active'),
            'visibility' => (string) ($data['visibility'] ?? 'public'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}

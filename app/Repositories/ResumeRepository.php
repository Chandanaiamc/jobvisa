<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\Entities\Resume;
use JobVisa\App\Domain\Resume\Factories\ResumeFactory;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface as DomainResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface as InfrastructureResumeRepositoryInterface;

/**
 * Enterprise resume repository — Domain + infrastructure contracts.
 * Preserves Sprint 2C CV helpers (ensurePrimary, updateFile, …).
 */
final class ResumeRepository extends BaseRepository implements
    InfrastructureResumeRepositoryInterface,
    DomainResumeRepositoryInterface
{
    protected string $table = 'resumes';

    public function __construct(
        \PDO $pdo,
        private readonly ResumeFactory $factory = new ResumeFactory()
    ) {
        parent::__construct($pdo);
    }

    private function notDeletedSql(string $alias = ''): string
    {
        $col = $alias !== '' ? $alias . '.`deleted_at`' : '`deleted_at`';

        return "({$col} IS NULL)";
    }

    public function findById(int|string $id): ?Resume
    {
        $row = $this->findRecordById((int) $id);

        return $row === null ? null : Resume::fromRow($row);
    }

    public function findRecordById(int $resumeId): ?array
    {
        if ($resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resumes`
             WHERE `id` = :id AND ' . $this->notDeletedSql() . '
             LIMIT 1',
            ['id' => $resumeId]
        );
    }

    public function findAggregateById(int $id): ?ResumeAggregate
    {
        $row = $this->findRecordById($id);

        return $row === null ? null : $this->factory->fromRow($row);
    }

    public function findOwnedAggregate(int $id, int $userId): ?ResumeAggregate
    {
        $row = $this->findByIdForUser($id, $userId);

        return $row === null ? null : $this->factory->fromRow($row);
    }

    public function listActiveForUser(int $userId): array
    {
        $rows = $this->listActiveRecordsForUser($userId);
        $items = [];

        foreach ($rows as $row) {
            $items[] = Resume::fromRow($row);
        }

        return $items;
    }

    public function listActiveRecordsForUser(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        return $this->fetchAll(
            'SELECT * FROM `resumes`
             WHERE `user_id` = :user_id AND ' . $this->notDeletedSql() . '
             ORDER BY `is_primary` DESC, `updated_at` DESC, `id` DESC',
            ['user_id' => $userId]
        );
    }

    public function saveAggregate(ResumeAggregate $aggregate): ResumeAggregate
    {
        $resume = $aggregate->resume();
        $data = $resume->toPersistenceArray();
        $id = (int) ($resume->id() ?? 0);

        if ($id < 1) {
            $this->query(
                'INSERT INTO `resumes`
                    (`user_id`, `title`, `status`, `visibility`, `file_path`, `file_mime`,
                     `file_size_bytes`, `is_primary`, `completeness_score`)
                 VALUES
                    (:user_id, :title, :status, :visibility, :file_path, :file_mime,
                     :file_size_bytes, :is_primary, :completeness_score)',
                [
                    'user_id' => $data['user_id'],
                    'title' => $data['title'],
                    'status' => $data['status'],
                    'visibility' => $data['visibility'],
                    'file_path' => $data['file_path'],
                    'file_mime' => $data['file_mime'],
                    'file_size_bytes' => $data['file_size_bytes'],
                    'is_primary' => $data['is_primary'],
                    'completeness_score' => $data['completeness_score'],
                ]
            );

            $id = (int) $this->pdo->lastInsertId();
        } else {
            $this->query(
                'UPDATE `resumes` SET
                    `title` = :title,
                    `status` = :status,
                    `visibility` = :visibility,
                    `file_path` = :file_path,
                    `file_mime` = :file_mime,
                    `file_size_bytes` = :file_size_bytes,
                    `is_primary` = :is_primary,
                    `completeness_score` = :completeness_score,
                    `deleted_at` = :deleted_at
                 WHERE `id` = :id AND `user_id` = :user_id',
                [
                    'title' => $data['title'],
                    'status' => $data['status'],
                    'visibility' => $data['visibility'],
                    'file_path' => $data['file_path'],
                    'file_mime' => $data['file_mime'],
                    'file_size_bytes' => $data['file_size_bytes'],
                    'is_primary' => $data['is_primary'],
                    'completeness_score' => $data['completeness_score'],
                    'deleted_at' => $data['deleted_at'],
                    'id' => $id,
                    'user_id' => $data['user_id'],
                ]
            );
        }

        if (!empty($data['is_primary'])) {
            $this->clearDefaultForUser((int) $data['user_id'], $id);
            $this->query(
                'UPDATE `resumes` SET `is_primary` = 1 WHERE `id` = :id',
                ['id' => $id]
            );
        }

        $saved = $this->findAggregateById($id);

        return $saved ?? $aggregate;
    }

    public function softDeleteAggregate(ResumeAggregate $aggregate): void
    {
        $aggregate->softDelete();
        $resume = $aggregate->resume();
        $id = (int) ($resume->id() ?? 0);

        if ($id < 1) {
            return;
        }

        $this->query(
            'UPDATE `resumes`
             SET `deleted_at` = CURRENT_TIMESTAMP(3), `is_primary` = 0, `status` = :status
             WHERE `id` = :id',
            ['status' => Resume::STATUS_DRAFT, 'id' => $id]
        );
    }

    public function clearDefaultForUser(int $userId, ?int $exceptResumeId = null): void
    {
        if ($userId < 1) {
            return;
        }

        if ($exceptResumeId !== null && $exceptResumeId > 0) {
            $this->query(
                'UPDATE `resumes` SET `is_primary` = 0
                 WHERE `user_id` = :user_id AND `id` <> :except AND ' . $this->notDeletedSql(),
                ['user_id' => $userId, 'except' => $exceptResumeId]
            );

            return;
        }

        $this->query(
            'UPDATE `resumes` SET `is_primary` = 0
             WHERE `user_id` = :user_id AND ' . $this->notDeletedSql(),
            ['user_id' => $userId]
        );
    }

    public function findPrimaryByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $row = $this->fetchOne(
            'SELECT * FROM `resumes`
             WHERE `user_id` = :user_id AND `is_primary` = 1 AND ' . $this->notDeletedSql() . '
             ORDER BY `id` DESC LIMIT 1',
            ['user_id' => $userId]
        );

        if ($row !== null) {
            return $row;
        }

        return $this->fetchOne(
            'SELECT * FROM `resumes`
             WHERE `user_id` = :user_id AND ' . $this->notDeletedSql() . '
             ORDER BY `id` ASC LIMIT 1',
            ['user_id' => $userId]
        );
    }

    public function ensurePrimary(int $userId, string $title = 'Primary CV'): array
    {
        $existing = $this->findPrimaryByUserId($userId);

        if ($existing !== null) {
            if ((int) ($existing['is_primary'] ?? 0) !== 1) {
                $this->clearDefaultForUser($userId, (int) $existing['id']);
                $this->query(
                    'UPDATE `resumes` SET `is_primary` = 1 WHERE `id` = :id',
                    ['id' => (int) $existing['id']]
                );
                $existing['is_primary'] = 1;
            }

            return $existing;
        }

        $this->query(
            'INSERT INTO `resumes`
                (`user_id`, `title`, `status`, `visibility`, `is_primary`, `completeness_score`)
             VALUES (:user_id, :title, :status, :visibility, 1, 0)',
            [
                'user_id' => $userId,
                'title' => $title,
                'status' => Resume::STATUS_DRAFT,
                'visibility' => Resume::VISIBILITY_EMPLOYERS,
            ]
        );

        $id = (int) $this->pdo->lastInsertId();

        return $this->findRecordById($id) ?? [
            'id' => $id,
            'user_id' => $userId,
            'title' => $title,
            'status' => Resume::STATUS_DRAFT,
            'visibility' => Resume::VISIBILITY_EMPLOYERS,
            'file_path' => null,
            'is_primary' => 1,
            'completeness_score' => 0,
            'deleted_at' => null,
        ];
    }

    public function updateFile(int $resumeId, ?string $path, ?string $mime, ?int $sizeBytes): void
    {
        $this->query(
            'UPDATE `resumes`
             SET `file_path` = :path, `file_mime` = :mime, `file_size_bytes` = :size
             WHERE `id` = :id AND ' . $this->notDeletedSql(),
            [
                'path' => $path,
                'mime' => $mime,
                'size' => $sizeBytes,
                'id' => $resumeId,
            ]
        );
    }

    public function updateCompleteness(int $resumeId, int $score): void
    {
        $score = max(0, min(100, $score));
        $this->query(
            'UPDATE `resumes` SET `completeness_score` = :score
             WHERE `id` = :id AND ' . $this->notDeletedSql(),
            ['score' => $score, 'id' => $resumeId]
        );
    }

    public function findByIdForUser(int $resumeId, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM `resumes`
             WHERE `id` = :id AND `user_id` = :user_id AND ' . $this->notDeletedSql() . '
             LIMIT 1',
            ['id' => $resumeId, 'user_id' => $userId]
        );
    }
}

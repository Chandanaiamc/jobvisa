<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

use JobVisa\App\Repositories\Contracts\ResumeProfessionalRepositoryInterface;

final class ResumeProfessionalRepository extends BaseRepository implements ResumeProfessionalRepositoryInterface
{
    protected string $table = 'resume_professional';

    public function findByResumeId(int $resumeId): ?array
    {
        if ($resumeId < 1) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM `resume_professional` WHERE `resume_id` = :resume_id LIMIT 1',
            ['resume_id' => $resumeId]
        );
    }

    public function upsert(int $resumeId, array $data): void
    {
        $fields = [
            'headline', 'summary', 'career_objective', 'years_of_experience',
            'current_job_title', 'current_company', 'industry', 'current_salary',
            'expected_salary', 'preferred_currency', 'notice_period', 'employment_status',
            'open_to_relocate', 'open_to_remote',
        ];

        $payload = [];
        foreach ($fields as $field) {
            $payload[$field] = $data[$field] ?? null;
        }

        $payload['open_to_relocate'] = !empty($payload['open_to_relocate']) ? 1 : 0;
        $payload['open_to_remote'] = !empty($payload['open_to_remote']) ? 1 : 0;

        $existing = $this->findByResumeId($resumeId);

        if ($existing === null) {
            $columns = array_merge(['resume_id'], array_keys($payload));
            $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
            $payload['resume_id'] = $resumeId;
            $sql = sprintf(
                'INSERT INTO `resume_professional` (`%s`) VALUES (%s)',
                implode('`, `', $columns),
                implode(', ', $placeholders)
            );
            $this->query($sql, $payload);

            return;
        }

        $sets = [];
        foreach (array_keys($payload) as $column) {
            $sets[] = '`' . $column . '` = :' . $column;
        }
        $payload['resume_id'] = $resumeId;
        $this->query(
            'UPDATE `resume_professional` SET ' . implode(', ', $sets) . ' WHERE `resume_id` = :resume_id',
            $payload
        );
    }
}

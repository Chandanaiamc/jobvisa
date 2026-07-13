<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\Validators;

use JobVisa\App\Domain\ResumeBuilder\Exceptions\ResumeBuilderException;

final class ResumeBuilderValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw ResumeBuilderException::invalidResume();
        }
    }

    public function assertVersionId(int $versionId): void
    {
        if ($versionId < 1) {
            throw ResumeBuilderException::versionNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw ResumeBuilderException::historyNotFound();
        }
    }

    public function normalizeTargetRole(?string $targetRole): ?string
    {
        if ($targetRole === null) {
            return null;
        }
        $role = trim($targetRole);

        return $role === '' ? null : mb_substr($role, 0, 191);
    }

    public function normalizeLabel(?string $label): string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return 'AI draft ' . date('Y-m-d H:i');
        }

        return mb_substr($label, 0, 120);
    }
}

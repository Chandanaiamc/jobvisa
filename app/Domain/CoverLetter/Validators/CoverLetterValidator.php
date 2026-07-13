<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\Validators;

use JobVisa\App\Domain\CoverLetter\Exceptions\CoverLetterException;
use JobVisa\App\Domain\CoverLetter\Support\CoverLetterRulesVersion;

final class CoverLetterValidator
{
    public function assertResumeId(int $resumeId): void
    {
        if ($resumeId < 1) {
            throw CoverLetterException::invalidResume();
        }
    }

    public function assertVersionId(int $versionId): void
    {
        if ($versionId < 1) {
            throw CoverLetterException::versionNotFound();
        }
    }

    public function assertHistoryId(int $historyId): void
    {
        if ($historyId < 1) {
            throw CoverLetterException::historyNotFound();
        }
    }

    public function normalizeStyle(?string $style): string
    {
        $style = strtolower(trim((string) $style));
        if ($style === '') {
            return CoverLetterRulesVersion::STYLE_PROFESSIONAL;
        }
        if (!in_array($style, CoverLetterRulesVersion::styles(), true)) {
            throw CoverLetterException::invalidStyle();
        }

        return $style;
    }

    public function normalizeTone(?string $tone): ?string
    {
        if ($tone === null) {
            return null;
        }
        $tone = trim($tone);

        return $tone === '' ? null : mb_substr($tone, 0, 64);
    }

    public function normalizeLabel(?string $label, string $style): string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return ucfirst($style) . ' cover letter ' . date('Y-m-d H:i');
        }

        return mb_substr($label, 0, 120);
    }

    public function normalizeJobId(mixed $jobId): ?int
    {
        if ($jobId === null || $jobId === '') {
            return null;
        }
        $id = (int) $jobId;

        return $id > 0 ? $id : null;
    }
}

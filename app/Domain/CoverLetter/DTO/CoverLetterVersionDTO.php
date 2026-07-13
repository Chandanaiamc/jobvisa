<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\CoverLetter\DTO;

use JobVisa\App\Domain\CoverLetter\Support\CoverLetterRulesVersion;

final class CoverLetterVersionDTO
{
    /**
     * @param  array<string, mixed>  $highlights
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly ?int $jobId,
        public readonly string $jobTitle,
        public readonly string $versionLabel,
        public readonly string $status,
        public readonly string $style,
        public readonly ?string $tone,
        public readonly string $bodyText,
        public readonly array $highlights,
        public readonly array $context,
        public readonly int $atsScore,
        public readonly string $rulesVersion,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row, bool $canEdit): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['resume_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            isset($row['job_id']) && $row['job_id'] !== null ? (int) $row['job_id'] : null,
            (string) ($row['job_title'] ?? ''),
            (string) ($row['version_label'] ?? ''),
            (string) ($row['status'] ?? CoverLetterRulesVersion::STATUS_PREVIEW),
            (string) ($row['style'] ?? CoverLetterRulesVersion::STYLE_PROFESSIONAL),
            isset($row['tone']) && $row['tone'] !== null && $row['tone'] !== '' ? (string) $row['tone'] : null,
            (string) ($row['body_text'] ?? ''),
            self::decodeMap($row['highlights_json'] ?? []),
            self::decodeMap($row['context_json'] ?? []),
            max(0, min(100, (int) ($row['ats_score'] ?? 0))),
            (string) ($row['rules_version'] ?? ''),
            !empty($row['is_active']),
            (string) ($row['created_at'] ?? ''),
            $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistPayload(): array
    {
        return [
            'job_id' => $this->jobId,
            'version_label' => $this->versionLabel,
            'status' => $this->status,
            'style' => $this->style,
            'tone' => $this->tone,
            'body_text' => $this->bodyText,
            'highlights_json' => $this->highlights,
            'context_json' => $this->context,
            'ats_score' => $this->atsScore,
            'rules_version' => $this->rulesVersion,
            'is_active' => $this->isActive ? 1 : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toHistorySnapshot(): array
    {
        return array_merge($this->toPersistPayload(), [
            'id' => $this->id,
            'resume_id' => $this->resumeId,
            'user_id' => $this->userId,
            'job_title' => $this->jobTitle,
            'created_at' => $this->createdAt,
        ]);
    }

    /** @return array<string, mixed> */
    private static function decodeMap(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}

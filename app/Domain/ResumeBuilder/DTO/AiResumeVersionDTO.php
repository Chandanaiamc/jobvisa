<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ResumeBuilder\DTO;

use JobVisa\App\Domain\ResumeBuilder\Support\ResumeBuilderVersion;

/**
 * One AI-generated resume version (preview or saved).
 */
final class AiResumeVersionDTO
{
    /**
     * @param  array<string, mixed>  $content
     * @param  list<string>  $missingKeywords
     * @param  list<array<string, mixed>>  $keywordSuggestions
     */
    public function __construct(
        public readonly int $id,
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $versionLabel,
        public readonly string $status,
        public readonly ?string $targetRole,
        public readonly string $professionalSummary,
        public readonly array $content,
        public readonly int $atsScore,
        public readonly array $missingKeywords,
        public readonly array $keywordSuggestions,
        public readonly string $builderVersion,
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
            (string) ($row['version_label'] ?? ''),
            (string) ($row['status'] ?? ResumeBuilderVersion::STATUS_PREVIEW),
            isset($row['target_role']) && $row['target_role'] !== null && $row['target_role'] !== ''
                ? (string) $row['target_role']
                : null,
            (string) ($row['professional_summary'] ?? ''),
            self::decodeMap($row['content_json'] ?? []),
            max(0, min(100, (int) ($row['ats_score'] ?? 0))),
            self::decodeStringList($row['missing_keywords_json'] ?? []),
            self::decodeList($row['keyword_suggestions_json'] ?? []),
            (string) ($row['builder_version'] ?? ''),
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
            'version_label' => $this->versionLabel,
            'status' => $this->status,
            'target_role' => $this->targetRole,
            'professional_summary' => $this->professionalSummary,
            'content_json' => $this->content,
            'ats_score' => $this->atsScore,
            'missing_keywords_json' => $this->missingKeywords,
            'keyword_suggestions_json' => $this->keywordSuggestions,
            'builder_version' => $this->builderVersion,
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
            'created_at' => $this->createdAt,
        ]);
    }

    /** @return array<string, mixed> */
    private static function decodeMap(mixed $value): array
    {
        $data = self::decodeJson($value);

        return is_array($data) ? $data : [];
    }

    /** @return list<array<string, mixed>> */
    private static function decodeList(mixed $value): array
    {
        $data = self::decodeJson($value);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /** @return list<string> */
    private static function decodeStringList(mixed $value): array
    {
        $data = self::decodeJson($value);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }

    /** @return array<mixed> */
    private static function decodeJson(mixed $value): array
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

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\JobMatching\DTO;

final class MatchRecommendationDTO
{
    public function __construct(
        public readonly string $code,
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity,
        public readonly string $section,
        public readonly int $estimatedImprovement,
        public readonly string $actionUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'affected_section' => $this->section,
            'estimated_score_improvement' => max(0, min(100, $this->estimatedImprovement)),
            'action_url' => $this->actionUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            code: (string) ($row['code'] ?? ''),
            title: (string) ($row['title'] ?? ''),
            message: (string) ($row['message'] ?? ''),
            severity: (string) ($row['severity'] ?? 'medium'),
            section: (string) ($row['affected_section'] ?? $row['section'] ?? ''),
            estimatedImprovement: (int) ($row['estimated_score_improvement'] ?? 0),
            actionUrl: (string) ($row['action_url'] ?? ''),
        );
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\InterviewAssistant\DTO;

/**
 * Prepared interview pack for a candidate.
 */
final class InterviewSessionDTO
{
    /**
     * @param  list<array{id: string, prompt: string, focus: string, difficulty: string}>  $technicalQuestions
     * @param  list<array{id: string, prompt: string, focus: string, difficulty: string}>  $behavioralQuestions
     * @param  list<string>  $strengths
     * @param  list<string>  $weaknesses
     * @param  list<string>  $recommendations
     * @param  array<string, mixed>  $contextScores
     * @param  array<string, mixed>|null  $scorecard
     */
    public function __construct(
        public readonly int $id,
        public readonly int $employerUserId,
        public readonly int $jobId,
        public readonly string $jobTitle,
        public readonly int $applicationId,
        public readonly ?int $resumeId,
        public readonly int $candidateUserId,
        public readonly string $candidateName,
        public readonly string $candidateEmail,
        public readonly string $status,
        public readonly array $technicalQuestions,
        public readonly array $behavioralQuestions,
        public readonly array $strengths,
        public readonly array $weaknesses,
        public readonly array $recommendations,
        public readonly array $contextScores,
        public readonly string $assistantVersion,
        public readonly string $createdAt,
        public readonly ?array $scorecard,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>|null  $scorecard
     */
    public static function fromRow(array $row, ?array $scorecard = null): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (int) ($row['employer_user_id'] ?? 0),
            (int) ($row['job_id'] ?? 0),
            (string) ($row['job_title'] ?? ''),
            (int) ($row['application_id'] ?? 0),
            isset($row['resume_id']) && $row['resume_id'] !== null ? (int) $row['resume_id'] : null,
            (int) ($row['candidate_user_id'] ?? 0),
            (string) ($row['candidate_name'] ?? ''),
            (string) ($row['candidate_email'] ?? ''),
            (string) ($row['status'] ?? 'prepared'),
            self::decodeList($row['technical_questions'] ?? []),
            self::decodeList($row['behavioral_questions'] ?? []),
            self::decodeStringList($row['strengths_json'] ?? []),
            self::decodeStringList($row['weaknesses_json'] ?? []),
            self::decodeStringList($row['recommendations_json'] ?? []),
            self::decodeMap($row['context_scores_json'] ?? []),
            (string) ($row['assistant_version'] ?? ''),
            (string) ($row['created_at'] ?? ''),
            $scorecard,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'employer_user_id' => $this->employerUserId,
            'job_id' => $this->jobId,
            'job_title' => $this->jobTitle,
            'application_id' => $this->applicationId,
            'resume_id' => $this->resumeId,
            'candidate_user_id' => $this->candidateUserId,
            'candidate_name' => $this->candidateName,
            'candidate_email' => $this->candidateEmail,
            'status' => $this->status,
            'technical_questions' => $this->technicalQuestions,
            'behavioral_questions' => $this->behavioralQuestions,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'recommendations' => $this->recommendations,
            'context_scores' => $this->contextScores,
            'assistant_version' => $this->assistantVersion,
            'created_at' => $this->createdAt,
            'scorecard' => $this->scorecard,
        ];
    }

    /**
     * @return list<array{id: string, prompt: string, focus: string, difficulty: string}>
     */
    private static function decodeList(mixed $value): array
    {
        $data = self::decodeJson($value);
        $out = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $out[] = [
                'id' => (string) ($item['id'] ?? ''),
                'prompt' => (string) ($item['prompt'] ?? ''),
                'focus' => (string) ($item['focus'] ?? ''),
                'difficulty' => (string) ($item['difficulty'] ?? 'medium'),
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function decodeStringList(mixed $value): array
    {
        $data = self::decodeJson($value);
        $out = [];
        foreach ($data as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeMap(mixed $value): array
    {
        $data = self::decodeJson($value);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<mixed>
     */
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

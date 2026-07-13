<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Resume project / portfolio record.
 */
final class ResumeProjectDTO
{
    /**
     * @param  list<string>  $technologies
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $resumeId,
        public readonly string $title,
        public readonly ?string $clientName,
        public readonly ?string $organization,
        public readonly ?string $role,
        public readonly ?string $description,
        public readonly array $technologies,
        public readonly ?string $projectUrl,
        public readonly ?string $githubUrl,
        public readonly ?string $portfolioUrl,
        public readonly ?string $videoDemoUrl,
        public readonly ?string $image,
        public readonly ?string $document,
        public readonly ?string $startDate,
        public readonly ?string $endDate,
        public readonly bool $currentlyWorking,
        public readonly ?int $teamSize,
        public readonly ?string $projectType,
        public readonly ?string $industry,
        public readonly ?string $location,
        public readonly ?string $achievements,
        public readonly ?string $responsibilities,
        public readonly string $status,
        public readonly string $visibility,
        public readonly int $sortOrder,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row, bool $canEdit): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            resumeId: (int) ($row['resume_id'] ?? 0),
            title: trim((string) ($row['title'] ?? '')),
            clientName: self::nullStr($row['client_name'] ?? null),
            organization: self::nullStr($row['organization'] ?? null),
            role: self::nullStr($row['role'] ?? null),
            description: self::nullStr($row['description'] ?? null),
            technologies: self::parseTechnologies($row['technologies'] ?? null),
            projectUrl: self::nullStr($row['project_url'] ?? null),
            githubUrl: self::nullStr($row['github_url'] ?? null),
            portfolioUrl: self::nullStr($row['portfolio_url'] ?? null),
            videoDemoUrl: self::nullStr($row['video_demo_url'] ?? null),
            image: self::nullStr($row['image'] ?? null),
            document: self::nullStr($row['document'] ?? null),
            startDate: self::nullStr($row['start_date'] ?? null),
            endDate: self::nullStr($row['end_date'] ?? null),
            currentlyWorking: !empty($row['currently_working']),
            teamSize: isset($row['team_size']) && $row['team_size'] !== null && $row['team_size'] !== ''
                ? (int) $row['team_size']
                : null,
            projectType: self::nullStr($row['project_type'] ?? null),
            industry: self::nullStr($row['industry'] ?? null),
            location: self::nullStr($row['location'] ?? null),
            achievements: self::nullStr($row['achievements'] ?? null),
            responsibilities: self::nullStr($row['responsibilities'] ?? null),
            status: (string) ($row['status'] ?? 'active'),
            visibility: (string) ($row['visibility'] ?? 'public'),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            createdAt: self::nullStr($row['created_at'] ?? null),
            updatedAt: self::nullStr($row['updated_at'] ?? null),
            deletedAt: self::nullStr($row['deleted_at'] ?? null),
            canEdit: $canEdit,
        );
    }

    public static function blank(int $resumeId, bool $canEdit): self
    {
        return new self(
            id: null,
            resumeId: $resumeId,
            title: '',
            clientName: null,
            organization: null,
            role: null,
            description: null,
            technologies: [],
            projectUrl: null,
            githubUrl: null,
            portfolioUrl: null,
            videoDemoUrl: null,
            image: null,
            document: null,
            startDate: null,
            endDate: null,
            currentlyWorking: false,
            teamSize: null,
            projectType: null,
            industry: null,
            location: null,
            achievements: null,
            responsibilities: null,
            status: 'active',
            visibility: 'public',
            sortOrder: 0,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
            canEdit: $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'title' => $this->title,
            'client_name' => $this->clientName ?? '',
            'organization' => $this->organization ?? '',
            'role' => $this->role ?? '',
            'description' => $this->description ?? '',
            'technologies' => implode(', ', $this->technologies),
            'project_url' => $this->projectUrl ?? '',
            'github_url' => $this->githubUrl ?? '',
            'portfolio_url' => $this->portfolioUrl ?? '',
            'video_demo_url' => $this->videoDemoUrl ?? '',
            'start_date' => $this->startDate ?? '',
            'end_date' => $this->endDate ?? '',
            'currently_working' => $this->currentlyWorking ? '1' : '',
            'team_size' => $this->teamSize !== null ? (string) $this->teamSize : '',
            'project_type' => $this->projectType ?? '',
            'industry' => $this->industry ?? '',
            'location' => $this->location ?? '',
            'achievements' => $this->achievements ?? '',
            'responsibilities' => $this->responsibilities ?? '',
            'status' => $this->status,
            'visibility' => $this->visibility,
            'sort_order' => (string) $this->sortOrder,
        ];
    }

    /**
     * Public profile projection. Private projects return null.
     * Private documents are never exposed.
     *
     * @return array<string, mixed>|null
     */
    public function toPublicArray(): ?array
    {
        if ($this->visibility !== 'public' || $this->deletedAt !== null || $this->status !== 'active') {
            return null;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'client_name' => $this->clientName,
            'organization' => $this->organization,
            'role' => $this->role,
            'description' => $this->description,
            'technologies' => $this->technologies,
            'project_url' => $this->projectUrl,
            'github_url' => $this->githubUrl,
            'portfolio_url' => $this->portfolioUrl,
            'video_demo_url' => $this->videoDemoUrl,
            'image' => $this->image,
            'start_date' => $this->startDate,
            'end_date' => $this->currentlyWorking ? null : $this->endDate,
            'currently_working' => $this->currentlyWorking,
            'team_size' => $this->teamSize,
            'project_type' => $this->projectType,
            'industry' => $this->industry,
            'location' => $this->location,
            'achievements' => $this->achievements,
            'responsibilities' => $this->responsibilities,
            'sort_order' => $this->sortOrder,
        ];
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public'
            && $this->deletedAt === null
            && $this->status === 'active';
    }

    /**
     * @return list<string>
     */
    public static function parseTechnologies(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            $items = $raw;
        } else {
            $raw = trim((string) $raw);
            if ($raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = preg_split('/[,;|]+/', $raw) ?: [];
            }
        }

        $out = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '' || in_array($item, $out, true)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  list<string>  $technologies
     */
    public static function encodeTechnologies(array $technologies): ?string
    {
        $technologies = self::parseTechnologies($technologies);

        return $technologies === [] ? null : json_encode(array_values($technologies), JSON_UNESCAPED_UNICODE);
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeAchievementDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeAchievementPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeAchievementValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeAchievementRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — awards & achievements (resume-scoped).
 */
final class ResumeAchievementService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeAchievementRepositoryInterface $achievements,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumeAchievementValidator $validator,
        private readonly ResumeAchievementPolicy $policy,
        private readonly ResumeCompletionCalculator $completion,
        private readonly FileStorage $storage
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function form(array $actor, int $resumeId, ?string $query = null): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canManage($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $items = array_map(
            static fn (array $row): ResumeAchievementDTO => ResumeAchievementDTO::fromRow($row, $canEdit),
            $this->achievements->listByResumeId($resumeId, $query)
        );
        $deleted = array_map(
            static fn (array $row): ResumeAchievementDTO => ResumeAchievementDTO::fromRow($row, $canEdit),
            $this->achievements->listDeletedByResumeId($resumeId)
        );

        $projectOptions = array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
            ],
            $this->projects->listByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeAchievementDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'statuses' => ResumeAchievementValidator::STATUSES,
            'visibilities' => ResumeAchievementValidator::VISIBILITIES,
            'types' => ResumeAchievementValidator::TYPES,
            'award_levels' => ResumeAchievementValidator::AWARD_LEVELS,
            'projects' => $projectOptions,
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
            'query' => $query ?? '',
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/achievements/search',
            'cities_url' => '/jobseeker/resumes/' . $resumeId . '/achievements/cities',
        ];
    }

    /**
     * Secure city lookup filtered by country (owner/view access required).
     *
     * @param  array<string, mixed>  $actor
     * @return list<array{id: int, name: string, country_id: int}>
     */
    public function citiesForCountry(array $actor, int $resumeId, int $countryId): array
    {
        $this->requireResume($actor, $resumeId, true);

        if ($countryId < 1 || !$this->locations->countryExists($countryId)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'country_id' => (int) $row['country_id'],
            ];
        }, $this->locations->listCities($countryId));
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array<string, mixed>>
     */
    public function search(array $actor, int $resumeId, string $query): array
    {
        $this->requireResume($actor, $resumeId, true);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'issuer' => (string) ($row['issuer'] ?? ''),
                'achievement_type' => (string) ($row['achievement_type'] ?? ''),
                'award_level' => (string) ($row['award_level'] ?? ''),
                'is_featured' => !empty($row['is_featured']),
                'visibility' => (string) ($row['visibility'] ?? 'public'),
                'project_title' => (string) ($row['project_title'] ?? ''),
                'country_name' => (string) ($row['country_name'] ?? ''),
                'city_name' => (string) ($row['city_name'] ?? ''),
            ];
        }, $this->achievements->search($resumeId, $query));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $resumeId): array
    {
        $out = [];
        foreach ($this->achievements->listPublicByResumeId($resumeId) as $row) {
            $public = ResumeAchievementDTO::fromRow($row, false)->toPublicArray();
            if ($public !== null) {
                $out[] = $public;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $achievementId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->achievements->findOwned($achievementId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeAchievementDTO::fromRow($row, $data['can_edit']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function store(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate(
            $input,
            $this->projectOwnedChecker($resumeId),
            fn (int $id): bool => $this->locations->countryExists($id),
            fn (int $cityId, int $countryId): bool => $this->locations->cityBelongsToCountry($cityId, $countryId)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $newId = $this->achievements->create($resumeId, $this->normalize($input));
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Achievement added.',
            'completion' => $completion,
            'id' => $newId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $achievementId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate(
            $input,
            $this->projectOwnedChecker($resumeId),
            fn (int $id): bool => $this->locations->countryExists($id),
            fn (int $cityId, int $countryId): bool => $this->locations->cityBelongsToCountry($cityId, $countryId)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if (!$this->achievements->update($achievementId, $resumeId, $this->normalize($input))) {
            return ['success' => false, 'message' => 'Achievement not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Achievement updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $achievementId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->achievements->delete($achievementId, $resumeId)) {
            return ['success' => false, 'message' => 'Achievement not found.'];
        }

        // Soft delete retains certificate files for restore / later permanent cleanup.
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Achievement moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $achievementId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->achievements->restore($achievementId, $resumeId)) {
            return ['success' => false, 'message' => 'Achievement not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Achievement restored.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, completion?: array}
     */
    public function reorder(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $ids = $input['order'] ?? $input['achievement_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No achievement order provided.'];
        }

        $owned = $this->achievements->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid achievement order.'];
        }

        $this->achievements->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Achievement order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadCertificate(array $actor, int $resumeId, int $achievementId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->achievements->findOwned($achievementId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Achievement not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;
        $allowed = (array) config('uploads.certificate_mimes', ['application/pdf', 'image/jpeg', 'image/png']);
        $maxBytes = (int) config('uploads.max_certificate_bytes', 5_242_880);

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-achievements/' . $userId . '/' . $resumeId,
                'award',
                $allowed,
                $maxBytes
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $absolute = $this->storage->absolutePath($path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = is_file($absolute) ? (string) $finfo->file($absolute) : null;
        $size = is_file($absolute) ? (int) filesize($absolute) : (int) ($file['size'] ?? 0);
        $original = $this->sanitizeOriginalName((string) ($file['name'] ?? 'certificate'));

        $this->achievements->updateCertificateMeta($achievementId, $resumeId, [
            'path' => $path,
            'original_name' => $original,
            'mime' => $mime,
            'size' => $size > 0 ? $size : null,
        ]);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Certificate uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function deleteCertificate(array $actor, int $resumeId, int $achievementId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->achievements->findOwned($achievementId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Achievement not found.'];
        }

        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;
        $this->achievements->updateCertificateMeta($achievementId, $resumeId, [
            'path' => null,
            'original_name' => null,
            'mime' => null,
            'size' => null,
        ]);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Certificate removed.'];
    }

    /**
     * Owner-only download. Uses sanitized original name when available.
     *
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function certificateDownload(array $actor, int $resumeId, int $achievementId): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->achievements->findOwned($achievementId, $resumeId);

        if ($row === null || empty($row['certificate_path'])) {
            return null;
        }

        $path = (string) $row['certificate_path'];
        $absolute = $this->storage->absolutePath($path);

        if (!is_file($absolute)) {
            return null;
        }

        $name = $this->sanitizeOriginalName((string) ($row['certificate_original_name'] ?? ''));
        if ($name === 'certificate' || $name === '') {
            $name = basename($path);
        }

        return ['path' => $absolute, 'name' => $name];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $projectId = trim((string) ($input['project_id'] ?? ''));
        $countryId = trim((string) ($input['country_id'] ?? ''));
        $cityId = trim((string) ($input['city_id'] ?? ''));

        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'issuer' => $this->nullStr($input['issuer'] ?? null),
            'description' => $this->nullStr($input['description'] ?? null),
            'remarks' => $this->nullStr($input['remarks'] ?? null),
            'achievement_type' => $this->nullStr($input['achievement_type'] ?? null),
            'award_level' => $this->nullStr($input['award_level'] ?? null),
            'rank_or_placement' => $this->nullStr($input['rank_or_placement'] ?? null),
            'achievement_date' => $this->nullStr($input['achievement_date'] ?? null),
            'credential_url' => $this->nullStr($input['credential_url'] ?? null),
            'project_id' => $projectId !== '' ? (int) $projectId : null,
            'country_id' => $countryId !== '' ? (int) $countryId : null,
            'city_id' => $cityId !== '' ? (int) $cityId : null,
            'is_featured' => !empty($input['is_featured']),
            'visibility' => $this->nullStr($input['visibility'] ?? null) ?? 'public',
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
        ];
    }

    private function projectOwnedChecker(int $resumeId): callable
    {
        $owned = [];
        foreach ($this->projects->listByResumeId($resumeId) as $row) {
            $owned[(int) $row['id']] = true;
        }

        return static fn (int $projectId): bool => isset($owned[$projectId]);
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, bool $viewOnly): ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);

        if ($aggregate === null || $aggregate->resume()->deletedAt() !== null) {
            throw ResumeException::notFound();
        }

        $allowed = $viewOnly
            ? $this->policy->canView($actor, $aggregate->resume())
            : $this->policy->canManage($actor, $aggregate->resume());

        if (!$allowed) {
            throw ResumeException::forbidden();
        }

        return $aggregate;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(str_replace(["\0", '\\'], '', $name));
        $name = preg_replace('/[^\p{L}\p{N}\.\-_ ()\[\]]+/u', '_', $name) ?? 'certificate';
        $name = trim($name, '._ ');

        if ($name === '') {
            return 'certificate';
        }

        return mb_substr($name, 0, 200);
    }

    private function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeProjectDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeProjectPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeProjectValidator;
use JobVisa\App\Repositories\Contracts\ResumeProjectRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — projects & portfolio (resume-scoped).
 */
final class ResumeProjectService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeProjectRepositoryInterface $projects,
        private readonly ResumeProjectValidator $validator,
        private readonly ResumeProjectPolicy $policy,
        private readonly ResumeCompletionCalculator $completion,
        private readonly FileStorage $storage
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function form(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, true);
        $canEdit = $this->policy->canManage($actor, $aggregate->resume());
        $userId = $aggregate->resume()->userId();

        $items = array_map(
            static fn (array $row): ResumeProjectDTO => ResumeProjectDTO::fromRow($row, $canEdit),
            $this->projects->listByResumeId($resumeId)
        );
        $deleted = array_map(
            static fn (array $row): ResumeProjectDTO => ResumeProjectDTO::fromRow($row, $canEdit),
            $this->projects->listDeletedByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeProjectDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'statuses' => ResumeProjectValidator::STATUSES,
            'visibilities' => ResumeProjectValidator::VISIBILITIES,
            'project_types' => ResumeProjectValidator::PROJECT_TYPES,
            'reorder_url' => '/jobseeker/resumes/' . $resumeId . '/projects/reorder',
        ];
    }

    /**
     * Public-safe projects for a resume (no auth beyond resume existence for callers).
     *
     * @return list<array<string, mixed>>
     */
    public function listPublic(int $resumeId): array
    {
        $out = [];
        foreach ($this->projects->listPublicByResumeId($resumeId) as $row) {
            $dto = ResumeProjectDTO::fromRow($row, false);
            $public = $dto->toPublicArray();
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
    public function editForm(array $actor, int $resumeId, int $projectId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeProjectDTO::fromRow($row, $data['can_edit']),
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
        $errors = $this->validator->validate($input);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $newId = $this->projects->create($resumeId, $this->normalize($input));
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Project added.',
            'completion' => $completion,
            'id' => $newId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $projectId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate($input);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        if (!$this->projects->update($projectId, $resumeId, $this->normalize($input))) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Project updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $projectId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->projects->delete($projectId, $resumeId)) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Project moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $projectId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->projects->restore($projectId, $resumeId)) {
            return ['success' => false, 'message' => 'Project not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Project restored.',
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
        $ids = $input['order'] ?? $input['project_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No project order provided.'];
        }

        $owned = $this->projects->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid project order.'];
        }

        $this->projects->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Project order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadImage(array $actor, int $resumeId, int $projectId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['image'] ?? null) ? (string) $row['image'] : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-projects/' . $userId . '/' . $resumeId . '/images',
                'shot',
                (array) config('uploads.project_image_mimes', ['image/jpeg', 'image/png', 'image/webp']),
                (int) config('uploads.max_project_image_bytes', 5_242_880)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->projects->updateImagePath($projectId, $resumeId, $path);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Project image uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function deleteImage(array $actor, int $resumeId, int $projectId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $old = is_string($row['image'] ?? null) ? (string) $row['image'] : null;
        $this->projects->updateImagePath($projectId, $resumeId, null);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Project image removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadDocument(array $actor, int $resumeId, int $projectId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['document'] ?? null) ? (string) $row['document'] : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-projects/' . $userId . '/' . $resumeId . '/docs',
                'doc',
                (array) config('uploads.project_document_mimes', ['application/pdf']),
                (int) config('uploads.max_project_document_bytes', 5_242_880)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->projects->updateDocumentPath($projectId, $resumeId, $path);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Project document uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function deleteDocument(array $actor, int $resumeId, int $projectId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Project not found.'];
        }

        $old = is_string($row['document'] ?? null) ? (string) $row['document'] : null;
        $this->projects->updateDocumentPath($projectId, $resumeId, null);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Project document removed.'];
    }

    /**
     * Owner-only downloads (image or document). Private docs never served publicly.
     *
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function downloadAsset(array $actor, int $resumeId, int $projectId, string $kind): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->projects->findOwned($projectId, $resumeId);

        if ($row === null) {
            return null;
        }

        $relative = $kind === 'document'
            ? (is_string($row['document'] ?? null) ? (string) $row['document'] : '')
            : (is_string($row['image'] ?? null) ? (string) $row['image'] : '');

        if ($relative === '') {
            return null;
        }

        $absolute = $this->storage->absolutePath($relative);
        if (!is_file($absolute)) {
            return null;
        }

        return ['path' => $absolute, 'name' => basename($relative)];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $current = !empty($input['currently_working']);
        $tech = ResumeProjectDTO::parseTechnologies($input['technologies'] ?? '');

        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'client_name' => $this->nullStr($input['client_name'] ?? null),
            'organization' => $this->nullStr($input['organization'] ?? null),
            'role' => $this->nullStr($input['role'] ?? null),
            'description' => $this->nullStr($input['description'] ?? null),
            'technologies' => ResumeProjectDTO::encodeTechnologies($tech),
            'project_url' => $this->nullStr($input['project_url'] ?? null),
            'github_url' => $this->nullStr($input['github_url'] ?? null),
            'portfolio_url' => $this->nullStr($input['portfolio_url'] ?? null),
            'video_demo_url' => $this->nullStr($input['video_demo_url'] ?? null),
            'start_date' => $this->nullStr($input['start_date'] ?? null),
            'end_date' => $current ? null : $this->nullStr($input['end_date'] ?? null),
            'currently_working' => $current,
            'team_size' => isset($input['team_size']) && $input['team_size'] !== ''
                ? (int) $input['team_size']
                : null,
            'project_type' => $this->nullStr($input['project_type'] ?? null),
            'industry' => $this->nullStr($input['industry'] ?? null),
            'location' => $this->nullStr($input['location'] ?? null),
            'achievements' => $this->nullStr($input['achievements'] ?? null),
            'responsibilities' => $this->nullStr($input['responsibilities'] ?? null),
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
            'visibility' => $this->nullStr($input['visibility'] ?? null) ?? 'public',
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
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

    private function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeLanguageDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeLanguagePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeLanguageValidator;
use JobVisa\App\Repositories\Contracts\LanguageCatalogRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeLanguageRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — languages section (catalogue = `languages`; not `user_languages`).
 */
final class ResumeLanguageService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeLanguageRepositoryInterface $resumeLanguages,
        private readonly LanguageCatalogRepositoryInterface $catalog,
        private readonly ResumeLanguageValidator $validator,
        private readonly ResumeLanguagePolicy $policy,
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
            static fn (array $row): ResumeLanguageDTO => ResumeLanguageDTO::fromRow($row, $canEdit),
            $this->resumeLanguages->listByResumeId($resumeId)
        );
        $deleted = array_map(
            static fn (array $row): ResumeLanguageDTO => ResumeLanguageDTO::fromRow($row, $canEdit),
            $this->resumeLanguages->listDeletedByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeLanguageDTO::blank($resumeId, $canEdit),
            'languages' => $this->catalog->listActive(),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'cefr' => ResumeLanguageValidator::CEFR,
            'certificate_types' => ResumeLanguageValidator::CERTIFICATE_TYPES,
            'statuses' => ResumeLanguageValidator::STATUSES,
            'search_url' => '/jobseeker/resumes/' . $resumeId . '/languages/search',
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $languageRowId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->resumeLanguages->findOwned($languageRowId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeLanguageDTO::fromRow($row, $data['can_edit']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return list<array{id: int, name: string, code: string}>
     */
    public function search(array $actor, int $resumeId, string $query): array
    {
        $this->requireResume($actor, $resumeId, true);
        $rows = $this->catalog->search($query, 15);

        return array_map(static fn (array $r): array => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code' => (string) ($r['code'] ?? ''),
        ], $rows);
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
            fn (int $id): bool => $this->catalog->isActive($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        $existing = $this->resumeLanguages->findByResumeAndLanguage($resumeId, (int) $payload['language_id']);

        if ($existing !== null) {
            if (empty($existing['deleted_at'])) {
                return [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => ['language_id' => ['This language is already on the resume.']],
                ];
            }

            $this->resumeLanguages->restore((int) $existing['id'], $resumeId);
            $this->resumeLanguages->update((int) $existing['id'], $resumeId, $payload);
            $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

            return [
                'success' => true,
                'message' => 'Language restored and updated.',
                'completion' => $completion,
            ];
        }

        $this->resumeLanguages->create($resumeId, $payload);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Language added to resume.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $languageRowId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $current = $this->resumeLanguages->findOwned($languageRowId, $resumeId);

        if ($current === null) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $input['language_id'] = (int) $current['language_id'];

        $errors = $this->validator->validate(
            $input,
            fn (int $id): bool => $this->catalog->isActive($id)
        );

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        $payload['language_id'] = (int) $current['language_id'];

        if (!$this->resumeLanguages->update($languageRowId, $resumeId, $payload)) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Language updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $languageRowId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->resumeLanguages->delete($languageRowId, $resumeId)) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Language moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $languageRowId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->resumeLanguages->restore($languageRowId, $resumeId)) {
            return ['success' => false, 'message' => 'Language not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Language restored.',
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
        $ids = $input['order'] ?? $input['language_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No language order provided.'];
        }

        $owned = $this->resumeLanguages->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid language order.'];
        }

        $this->resumeLanguages->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Language order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadCertificate(array $actor, int $resumeId, int $languageRowId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->resumeLanguages->findOwned($languageRowId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'language-certs/' . $userId . '/' . $resumeId,
                'langcert',
                (array) config('uploads.certificate_mimes', ['application/pdf', 'image/jpeg', 'image/png']),
                (int) config('uploads.max_certificate_bytes', 5_242_880)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->resumeLanguages->updateCertificatePath($languageRowId, $resumeId, $path);

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
    public function deleteCertificate(array $actor, int $resumeId, int $languageRowId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->resumeLanguages->findOwned($languageRowId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;
        $this->resumeLanguages->updateCertificatePath($languageRowId, $resumeId, null);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Certificate removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function certificateDownload(array $actor, int $resumeId, int $languageRowId): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->resumeLanguages->findOwned($languageRowId, $resumeId);

        if ($row === null || empty($row['certificate_path'])) {
            return null;
        }

        $path = (string) $row['certificate_path'];
        $absolute = $this->storage->absolutePath($path);

        if (!is_file($absolute)) {
            return null;
        }

        return [
            'path' => $absolute,
            'name' => basename($path),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $isNative = !empty($input['is_native']);
        $defaultCefr = $isNative ? 'C2' : null;

        $cefr = static function (mixed $value, ?string $fallback): string {
            $level = strtoupper(trim((string) ($value ?? '')));

            return $level !== '' ? $level : ($fallback ?? 'B1');
        };

        return [
            'language_id' => (int) ($input['language_id'] ?? 0),
            'speaking' => $cefr($input['speaking'] ?? null, $defaultCefr),
            'reading' => $cefr($input['reading'] ?? null, $defaultCefr),
            'writing' => $cefr($input['writing'] ?? null, $defaultCefr),
            'listening' => $cefr($input['listening'] ?? null, $defaultCefr),
            'is_native' => $isNative,
            'certificate_type' => $this->nullStr($input['certificate_type'] ?? null),
            'certificate_score' => $this->nullStr($input['certificate_score'] ?? null),
            'certificate_issued_at' => $this->nullStr($input['certificate_issued_at'] ?? null),
            'certificate_expires_at' => $this->nullStr($input['certificate_expires_at'] ?? null),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'status' => $this->nullStr($input['status'] ?? null) ?? 'active',
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

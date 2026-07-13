<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate;
use JobVisa\App\Domain\Resume\DTO\ResumeCertificationDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumeCertificationPolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumeCertificationValidator;
use JobVisa\App\Repositories\Contracts\ResumeCertificationRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — certifications & licences (resume-scoped; not profile).
 */
final class ResumeCertificationService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumeCertificationRepositoryInterface $certifications,
        private readonly ResumeCertificationValidator $validator,
        private readonly ResumeCertificationPolicy $policy,
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
            static fn (array $row): ResumeCertificationDTO => ResumeCertificationDTO::fromRow($row, $canEdit),
            $this->certifications->listByResumeId($resumeId)
        );
        $deleted = array_map(
            static fn (array $row): ResumeCertificationDTO => ResumeCertificationDTO::fromRow($row, $canEdit),
            $this->certifications->listDeletedByResumeId($resumeId)
        );

        $completion = $this->completion->evaluate($userId, $resumeId);

        return [
            'items' => $items,
            'deleted' => $deleted,
            'blank' => ResumeCertificationDTO::blank($resumeId, $canEdit),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
            'statuses' => ResumeCertificationValidator::STATUSES,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function editForm(array $actor, int $resumeId, int $certId): array
    {
        $data = $this->form($actor, $resumeId);
        $row = $this->certifications->findOwned($certId, $resumeId);

        if ($row === null) {
            throw ResumeException::notFound();
        }

        return array_merge($data, [
            'item' => ResumeCertificationDTO::fromRow($row, $data['can_edit']),
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

        $payload = $this->normalize($input);
        if (!empty($payload['is_primary'])) {
            $this->certifications->clearPrimaryExcept($resumeId, null);
        }

        $newId = $this->certifications->create($resumeId, $payload);
        if (!empty($payload['is_primary'])) {
            $this->certifications->clearPrimaryExcept($resumeId, $newId);
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Certification added.',
            'completion' => $completion,
            'id' => $newId,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, completion?: array}
     */
    public function update(array $actor, int $resumeId, int $certId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $errors = $this->validator->validate($input);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $payload = $this->normalize($input);
        if (!empty($payload['is_primary'])) {
            $this->certifications->clearPrimaryExcept($resumeId, $certId);
        }

        if (!$this->certifications->update($certId, $resumeId, $payload)) {
            return ['success' => false, 'message' => 'Certification not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Certification updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function delete(array $actor, int $resumeId, int $certId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->certifications->delete($certId, $resumeId)) {
            return ['success' => false, 'message' => 'Certification not found.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Certification moved to trash.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string, completion?: array}
     */
    public function restore(array $actor, int $resumeId, int $certId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);

        if (!$this->certifications->restore($certId, $resumeId)) {
            return ['success' => false, 'message' => 'Certification not found in trash.'];
        }

        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Certification restored.',
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
        $ids = $input['order'] ?? $input['certification_ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            return ['success' => false, 'message' => 'No certification order provided.'];
        }

        $owned = $this->certifications->listByResumeId($resumeId);
        $ownedIds = array_map(static fn (array $r): int => (int) $r['id'], $owned);
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $ownedIds, true) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        if ($ordered === []) {
            return ['success' => false, 'message' => 'Invalid certification order.'];
        }

        $this->certifications->reorder($resumeId, $ordered);
        $completion = $this->completion->evaluate($aggregate->resume()->userId(), $resumeId);

        return [
            'success' => true,
            'message' => 'Certification order updated.',
            'completion' => $completion,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadCertificate(array $actor, int $resumeId, int $certId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, false);
        $row = $this->certifications->findOwned($certId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Certification not found.'];
        }

        $userId = $aggregate->resume()->userId();
        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'resume-certs/' . $userId . '/' . $resumeId,
                'cert',
                (array) config('uploads.certificate_mimes', ['application/pdf', 'image/jpeg', 'image/png']),
                (int) config('uploads.max_certificate_bytes', 5_242_880)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->certifications->updateCertificatePath($certId, $resumeId, $path);

        if ($old !== null && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Certificate file uploaded.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function deleteCertificate(array $actor, int $resumeId, int $certId): array
    {
        $this->requireResume($actor, $resumeId, false);
        $row = $this->certifications->findOwned($certId, $resumeId);

        if ($row === null) {
            return ['success' => false, 'message' => 'Certification not found.'];
        }

        $old = is_string($row['certificate_path'] ?? null) ? (string) $row['certificate_path'] : null;
        $this->certifications->updateCertificatePath($certId, $resumeId, null);

        if ($old !== null && $old !== '') {
            $this->storage->delete($old);
        }

        return ['success' => true, 'message' => 'Certificate file removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{path: string, name: string}|null
     */
    public function certificateDownload(array $actor, int $resumeId, int $certId): ?array
    {
        $this->requireResume($actor, $resumeId, true);
        $row = $this->certifications->findOwned($certId, $resumeId);

        if ($row === null || empty($row['certificate_path'])) {
            return null;
        }

        $path = (string) $row['certificate_path'];
        $absolute = $this->storage->absolutePath($path);

        if (!is_file($absolute)) {
            return null;
        }

        return ['path' => $absolute, 'name' => basename($path)];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $noExpire = !empty($input['does_not_expire']);

        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'issuing_organization' => trim((string) ($input['issuing_organization'] ?? '')),
            'credential_id' => $this->nullStr($input['credential_id'] ?? null),
            'credential_url' => $this->nullStr($input['credential_url'] ?? null),
            'issue_date' => $this->nullStr($input['issue_date'] ?? null),
            'expiry_date' => $noExpire ? null : $this->nullStr($input['expiry_date'] ?? null),
            'does_not_expire' => $noExpire,
            'license_number' => $this->nullStr($input['license_number'] ?? null),
            'verification_url' => $this->nullStr($input['verification_url'] ?? null),
            'is_primary' => !empty($input['is_primary']),
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

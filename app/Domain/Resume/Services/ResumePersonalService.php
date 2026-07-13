<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\Services;

use App\Core\Database;
use JobVisa\App\Domain\Resume\DTO\ResumePersonalDTO;
use JobVisa\App\Domain\Resume\Exceptions\ResumeException;
use JobVisa\App\Domain\Resume\Policies\ResumePolicy;
use JobVisa\App\Domain\Resume\Repositories\ResumeRepositoryInterface;
use JobVisa\App\Domain\Resume\Support\ResumeCompletionCalculator;
use JobVisa\App\Domain\Resume\Validators\ResumePersonalValidator;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumePersonalRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Resume builder — personal information section (profile reuse + overrides).
 */
final class ResumePersonalService
{
    public function __construct(
        private readonly ResumeRepositoryInterface $resumes,
        private readonly ResumePersonalRepositoryInterface $personalRepo,
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly LocationRepositoryInterface $locations,
        private readonly ResumePersonalValidator $validator,
        private readonly ResumePolicy $policy,
        private readonly ResumeCompletionCalculator $completion,
        private readonly FileStorage $storage
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{
     *   personal: ResumePersonalDTO,
     *   countries: list<array<string, mixed>>,
     *   cities: list<array<string, mixed>>,
     *   completion: array{score: int, sections: array},
     *   resume: array<string, mixed>,
     *   can_edit: bool
     * }
     */
    public function form(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'view');
        $userId = $aggregate->resume()->userId();
        $canEdit = $this->policy->allows('update', $aggregate->resume(), $actor);

        $profile = $this->profiles->findByUserId($userId) ?? [
            'user_email' => (string) ($actor['email'] ?? ''),
            'user_phone' => null,
        ];

        // Ensure profile row exists for joins on subsequent saves.
        if (!isset($profile['user_id'])) {
            $this->profiles->upsertForUser($userId, [
                'first_name' => '',
                'last_name' => '',
            ]);
            $profile = $this->profiles->findByUserId($userId) ?? $profile;
        }

        $override = $this->personalRepo->findByResumeId($resumeId);
        $preferred = $this->personalRepo->listPreferredCountryIds($resumeId);
        $personal = ResumePersonalDTO::merge($resumeId, $userId, $profile, $override, $preferred, $canEdit);
        $completion = $this->completion->evaluate($userId, $resumeId, $personal);

        return [
            'personal' => $personal,
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
            'completion' => $completion,
            'resume' => [
                'id' => $resumeId,
                'title' => $aggregate->resume()->title(),
                'status' => $aggregate->resume()->status(),
                'completeness_score' => $completion['score'],
            ],
            'can_edit' => $canEdit,
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>, personal?: ResumePersonalDTO}
     */
    public function save(array $actor, int $resumeId, array $input): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'update');
        $userId = $aggregate->resume()->userId();

        // Strip email — never editable here.
        unset($input['email']);

        $countries = $this->locations->listCountries();
        $validIds = array_map(static fn (array $c): int => (int) $c['id'], $countries);
        $errors = $this->validator->validate($input, $validIds);

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
        }

        $first = trim((string) $input['first_name']);
        $last = trim((string) $input['last_name']);
        $phone = $this->nullStr($input['phone'] ?? null);

        Database::query(
            'UPDATE `users` SET `full_name` = ?, `phone` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [trim($first . ' ' . $last), $phone, $userId]
        );

        $preferred = $input['preferred_country_ids'] ?? [];

        if (!is_array($preferred)) {
            $preferred = [];
        }

        $preferred = array_values(array_unique(array_filter(array_map('intval', $preferred), static fn (int $id): bool => $id > 0)));

        $this->profiles->upsertForUser($userId, [
            'first_name' => $first,
            'last_name' => $last,
            'nic_passport' => $this->nullStr($input['nic_number'] ?? null),
            'headline' => $this->nullStr($input['headline'] ?? null),
            'summary' => $this->nullStr($input['summary'] ?? null),
            'date_of_birth' => $this->nullStr($input['date_of_birth'] ?? null),
            'gender' => $this->nullStr($input['gender'] ?? null),
            'marital_status' => $this->nullStr($input['marital_status'] ?? null),
            'expected_salary' => $this->nullSalary($input['expected_salary'] ?? null),
            'nationality_country_id' => $this->nullId($input['nationality_country_id'] ?? null),
            'current_country_id' => $this->nullId($input['current_country_id'] ?? null),
            'preferred_country_id' => $preferred[0] ?? $this->nullId($input['preferred_country_id'] ?? null),
            'current_city_id' => $this->nullId($input['current_city_id'] ?? null),
            'address' => $this->nullStr($input['address'] ?? null),
            'whatsapp' => $this->nullStr($input['whatsapp'] ?? null),
        ]);

        $currency = strtoupper(trim((string) ($input['salary_currency'] ?? 'LKR')));

        if ($currency === '') {
            $currency = 'LKR';
        }

        $this->personalRepo->upsert($resumeId, [
            'passport_number' => $this->nullStr($input['passport_number'] ?? null),
            'passport_expiry' => $this->nullStr($input['passport_expiry'] ?? null),
            'salary_currency' => $currency,
            'visa_status' => $this->nullStr($input['visa_status'] ?? null),
            'driving_licence_status' => $this->nullStr($input['driving_licence_status'] ?? null),
        ]);

        $this->personalRepo->syncPreferredCountries($resumeId, $preferred);

        $form = $this->form($actor, $resumeId);

        return [
            'success' => true,
            'message' => 'Personal information saved.',
            'personal' => $form['personal'],
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadPhoto(array $actor, int $resumeId, array $file): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'update');
        $userId = $aggregate->resume()->userId();
        $profile = $this->profiles->findByUserId($userId);
        $old = is_array($profile) ? ($profile['avatar_path'] ?? null) : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'avatars/' . $userId,
                'avatar',
                (array) config('uploads.avatar_mimes', ['image/jpeg', 'image/png', 'image/webp']),
                (int) config('uploads.max_avatar_bytes', 3_145_728)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->profiles->updateAvatar($userId, $path);

        if (is_string($old) && $old !== '' && $old !== $path) {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Profile photo updated.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array{success: bool, message: string}
     */
    public function deletePhoto(array $actor, int $resumeId): array
    {
        $aggregate = $this->requireResume($actor, $resumeId, 'update');
        $userId = $aggregate->resume()->userId();
        $profile = $this->profiles->findByUserId($userId);
        $old = is_array($profile) ? ($profile['avatar_path'] ?? null) : null;

        $this->profiles->updateAvatar($userId, null);

        if (is_string($old) && $old !== '') {
            $this->storage->delete($old);
        }

        $this->completion->evaluate($userId, $resumeId);

        return ['success' => true, 'message' => 'Profile photo removed.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function requireResume(array $actor, int $resumeId, string $action): \JobVisa\App\Domain\Resume\Aggregates\ResumeAggregate
    {
        $aggregate = $this->resumes->findAggregateById($resumeId);

        if ($aggregate === null) {
            throw ResumeException::notFound();
        }

        if (!$this->policy->allows($action, $aggregate->resume(), $actor)) {
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

    private function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function nullSalary(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}

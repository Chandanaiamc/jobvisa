<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use App\Core\Database;
use JobVisa\App\Repositories\Contracts\LocationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use JobVisa\App\Security\Validator;
use JobVisa\App\Support\FileStorage;
use RuntimeException;

/**
 * Personal profile + avatar management for job seekers.
 */
final class ProfileService
{
    public function __construct(
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly ResumeRepositoryInterface $resumes,
        private readonly LocationRepositoryInterface $locations,
        private readonly ProfileCompletenessService $completeness,
        private readonly FileStorage $storage,
        private readonly ProfileAccess $access
    ) {
    }

    /**
     * @param  array<string, mixed>  $actor
     * @return array<string, mixed>
     */
    public function dashboard(array $actor, int $userId): array
    {
        $this->assertView($actor, $userId);
        $this->resumes->ensurePrimary($userId);
        $profile = $this->profiles->findByUserId($userId);
        $completeness = $this->completeness->evaluate($userId);
        $resume = $this->resumes->findPrimaryByUserId($userId);

        return [
            'profile' => $profile,
            'resume' => $resume,
            'completeness' => $completeness,
            'can_edit' => $this->access->canEdit($actor, $userId),
            'countries' => $this->locations->listCountries(),
            'cities' => $this->locations->listCities(),
        ];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $input
     * @return array{success: bool, message: string, errors?: array<string, list<string>>}
     */
    public function update(array $actor, int $userId, array $input): array
    {
        $this->assertEdit($actor, $userId);

        $validator = Validator::make($input)
            ->required('first_name')->max('first_name', 80)
            ->required('last_name')->max('last_name', 80)
            ->max('nic_passport', 64)
            ->max('headline', 255)
            ->max('gender', 20)
            ->max('marital_status', 32)
            ->max('whatsapp', 32)
            ->max('phone', 32);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()];
        }

        $first = trim((string) $input['first_name']);
        $last = trim((string) $input['last_name']);
        $fullName = trim($first . ' ' . $last);
        $phone = trim((string) ($input['phone'] ?? ''));
        $phone = $phone !== '' ? $phone : null;

        Database::query(
            'UPDATE `users` SET `full_name` = ?, `phone` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?',
            [$fullName, $phone, $userId]
        );

        $this->profiles->upsertForUser($userId, [
            'first_name' => $first,
            'last_name' => $last,
            'nic_passport' => $this->nullable($input['nic_passport'] ?? null),
            'headline' => $this->nullable($input['headline'] ?? null),
            'summary' => $this->nullable($input['summary'] ?? null),
            'date_of_birth' => $this->nullable($input['date_of_birth'] ?? null),
            'gender' => $this->nullable($input['gender'] ?? null),
            'marital_status' => $this->nullable($input['marital_status'] ?? null),
            'expected_salary' => $this->nullableSalary($input['expected_salary'] ?? null),
            'nationality_country_id' => $this->nullableId($input['nationality_country_id'] ?? null),
            'current_country_id' => $this->nullableId($input['current_country_id'] ?? null),
            'preferred_country_id' => $this->nullableId($input['preferred_country_id'] ?? null),
            'current_city_id' => $this->nullableId($input['current_city_id'] ?? null),
            'address' => $this->nullable($input['address'] ?? null),
            'whatsapp' => $this->nullable($input['whatsapp'] ?? null),
            'linkedin_url' => $this->nullable($input['linkedin_url'] ?? null),
            'visibility' => in_array(($input['visibility'] ?? ''), ['public', 'employers', 'private'], true)
                ? $input['visibility']
                : 'employers',
        ]);

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     * @param  array<string, mixed>  $file
     * @return array{success: bool, message: string}
     */
    public function uploadAvatar(array $actor, int $userId, array $file): array
    {
        $this->assertEdit($actor, $userId);

        $profile = $this->profiles->findByUserId($userId);
        $old = is_array($profile) ? ($profile['avatar_path'] ?? null) : null;

        try {
            $path = $this->storage->storeUpload(
                $file,
                'avatars/' . $userId,
                'avatar',
                (array) config('uploads.avatar_mimes', ['image/jpeg', 'image/png', 'image/webp']),
                (int) config('uploads.max_avatar_bytes', 2_097_152)
            );
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $this->profiles->updateAvatar($userId, $path);

        if (is_string($old) && $old !== '') {
            $this->storage->delete($old);
        }

        $this->completeness->evaluate($userId);

        return ['success' => true, 'message' => 'Profile photo updated.'];
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function assertView(array $actor, int $userId): void
    {
        if (!$this->access->canView($actor, $userId)) {
            http_response_code(403);
            throw new RuntimeException('You do not have access to this profile.');
        }
    }

    /**
     * @param  array<string, mixed>  $actor
     */
    private function assertEdit(array $actor, int $userId): void
    {
        if (!$this->access->canEdit($actor, $userId)) {
            http_response_code(403);
            throw new RuntimeException('You cannot edit this profile.');
        }
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function nullableSalary(mixed $value): ?string
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

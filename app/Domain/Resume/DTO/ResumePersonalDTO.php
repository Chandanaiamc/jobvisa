<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Resume\DTO;

/**
 * Merged personal information for the resume builder (profile + overrides).
 */
final class ResumePersonalDTO
{
    /**
     * @param  list<int>  $preferredCountryIds
     */
    public function __construct(
        public readonly int $resumeId,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $headline,
        public readonly ?string $summary,
        public readonly ?string $dateOfBirth,
        public readonly ?string $gender,
        public readonly ?int $nationalityCountryId,
        public readonly ?string $maritalStatus,
        public readonly ?string $nicNumber,
        public readonly ?string $passportNumber,
        public readonly ?string $passportExpiry,
        public readonly ?int $currentCountryId,
        public readonly ?int $currentCityId,
        public readonly ?string $address,
        public readonly ?string $phone,
        public readonly ?string $whatsapp,
        public readonly ?string $expectedSalary,
        public readonly ?string $salaryCurrency,
        public readonly array $preferredCountryIds,
        public readonly ?string $visaStatus,
        public readonly ?string $drivingLicenceStatus,
        public readonly ?string $avatarPath,
        public readonly bool $canEdit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>|null  $override
     * @param  list<int>  $preferredCountryIds
     */
    public static function merge(
        int $resumeId,
        int $userId,
        array $profile,
        ?array $override,
        array $preferredCountryIds,
        bool $canEdit
    ): self {
        $override ??= [];

        $preferred = $preferredCountryIds;

        if ($preferred === [] && !empty($profile['preferred_country_id'])) {
            $preferred = [(int) $profile['preferred_country_id']];
        }

        return new self(
            resumeId: $resumeId,
            userId: $userId,
            email: (string) ($profile['user_email'] ?? ''),
            firstName: (string) ($profile['first_name'] ?? ''),
            lastName: (string) ($profile['last_name'] ?? ''),
            headline: self::nullStr($profile['headline'] ?? null),
            summary: self::nullStr($profile['summary'] ?? null),
            dateOfBirth: self::nullStr($profile['date_of_birth'] ?? null),
            gender: self::nullStr($profile['gender'] ?? null),
            nationalityCountryId: self::nullId($profile['nationality_country_id'] ?? null),
            maritalStatus: self::nullStr($profile['marital_status'] ?? null),
            nicNumber: self::nullStr($profile['nic_passport'] ?? null),
            passportNumber: self::nullStr($override['passport_number'] ?? null),
            passportExpiry: self::nullStr($override['passport_expiry'] ?? null),
            currentCountryId: self::nullId($profile['current_country_id'] ?? null),
            currentCityId: self::nullId($profile['current_city_id'] ?? null),
            address: self::nullStr($profile['address'] ?? null),
            phone: self::nullStr($profile['user_phone'] ?? null),
            whatsapp: self::nullStr($profile['whatsapp'] ?? null),
            expectedSalary: isset($profile['expected_salary']) && $profile['expected_salary'] !== null
                ? (string) $profile['expected_salary']
                : null,
            salaryCurrency: self::nullStr($override['salary_currency'] ?? null) ?? 'LKR',
            preferredCountryIds: $preferred,
            visaStatus: self::nullStr($override['visa_status'] ?? null),
            drivingLicenceStatus: self::nullStr($override['driving_licence_status'] ?? null),
            avatarPath: self::nullStr($profile['avatar_path'] ?? null),
            canEdit: $canEdit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'headline' => $this->headline ?? '',
            'summary' => $this->summary ?? '',
            'date_of_birth' => $this->dateOfBirth ?? '',
            'gender' => $this->gender ?? '',
            'nationality_country_id' => $this->nationalityCountryId ?? '',
            'marital_status' => $this->maritalStatus ?? '',
            'nic_number' => $this->nicNumber ?? '',
            'passport_number' => $this->passportNumber ?? '',
            'passport_expiry' => $this->passportExpiry ?? '',
            'current_country_id' => $this->currentCountryId ?? '',
            'current_city_id' => $this->currentCityId ?? '',
            'address' => $this->address ?? '',
            'phone' => $this->phone ?? '',
            'whatsapp' => $this->whatsapp ?? '',
            'email' => $this->email,
            'expected_salary' => $this->expectedSalary ?? '',
            'salary_currency' => $this->salaryCurrency ?? 'LKR',
            'preferred_country_ids' => $this->preferredCountryIds,
            'visa_status' => $this->visaStatus ?? '',
            'driving_licence_status' => $this->drivingLicenceStatus ?? '',
            'avatar_path' => $this->avatarPath,
        ];
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function nullId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}

<?php

declare(strict_types=1);

namespace JobVisa\App\JobSeeker;

use JobVisa\App\Repositories\Contracts\EducationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserLanguageRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserProfileRepositoryInterface;
use JobVisa\App\Repositories\Contracts\UserSkillRepositoryInterface;
use JobVisa\App\Repositories\Contracts\WorkExperienceRepositoryInterface;

/**
 * Calculates and persists profile completeness percentage.
 */
final class ProfileCompletenessService
{
    public function __construct(
        private readonly UserProfileRepositoryInterface $profiles,
        private readonly ResumeRepositoryInterface $resumes,
        private readonly EducationRepositoryInterface $education,
        private readonly WorkExperienceRepositoryInterface $experience,
        private readonly UserSkillRepositoryInterface $skills,
        private readonly UserLanguageRepositoryInterface $languages
    ) {
    }

    /**
     * @return array{score: int, sections: array<string, array{label: string, complete: bool, weight: int}>}
     */
    public function evaluate(int $userId): array
    {
        $profile = $this->profiles->findByUserId($userId) ?? [];
        $resume = $this->resumes->ensurePrimary($userId);
        $resumeId = (int) $resume['id'];

        $sections = [
            'personal' => [
                'label' => 'Personal information',
                'weight' => 25,
                'complete' => $this->personalComplete($profile),
            ],
            'headline' => [
                'label' => 'Headline & summary',
                'weight' => 10,
                'complete' => $this->filled($profile['headline'] ?? null) && $this->filled($profile['summary'] ?? null),
            ],
            'photo' => [
                'label' => 'Profile photo',
                'weight' => 5,
                'complete' => $this->filled($profile['avatar_path'] ?? null),
            ],
            'contact' => [
                'label' => 'Contact & location',
                'weight' => 10,
                'complete' => $this->filled($profile['user_phone'] ?? null)
                    || $this->filled($profile['whatsapp'] ?? null)
                    || $this->filled($profile['address'] ?? null)
                    || !empty($profile['current_city_id']),
            ],
            'education' => [
                'label' => 'Education',
                'weight' => 15,
                'complete' => $this->education->listByResumeId($resumeId) !== [],
            ],
            'experience' => [
                'label' => 'Work experience',
                'weight' => 15,
                'complete' => $this->experience->listByResumeId($resumeId) !== [],
            ],
            'skills' => [
                'label' => 'Skills',
                'weight' => 10,
                'complete' => $this->skills->listByUserId($userId) !== [],
            ],
            'languages' => [
                'label' => 'Languages',
                'weight' => 5,
                'complete' => $this->languages->listByUserId($userId) !== [],
            ],
            'cv' => [
                'label' => 'CV upload',
                'weight' => 5,
                'complete' => $this->filled($resume['file_path'] ?? null),
            ],
        ];

        $score = 0;
        foreach ($sections as $section) {
            if ($section['complete']) {
                $score += $section['weight'];
            }
        }

        $this->resumes->updateCompleteness($resumeId, $score);

        return ['score' => $score, 'sections' => $sections];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function personalComplete(array $profile): bool
    {
        return $this->filled($profile['first_name'] ?? null)
            && $this->filled($profile['last_name'] ?? null)
            && $this->filled($profile['date_of_birth'] ?? null)
            && $this->filled($profile['gender'] ?? null)
            && !empty($profile['nationality_country_id']);
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}

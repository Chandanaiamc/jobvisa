<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>|null $profile
 * @var array{score: int, sections: array<string, array{label: string, complete: bool, weight: int}>} $completeness
 * @var int $educationCount
 * @var int $experienceCount
 * @var int $skillsCount
 * @var int $languagesCount
 * @var bool $hasCv
 */

$headline = (string) ($profile['headline'] ?? 'Complete your professional profile');
?>
<section class="panel">
    <h2 class="panel__title">Welcome back</h2>
    <p class="panel__lead"><?= e($headline) ?></p>

    <div class="stat-grid">
        <a class="stat" href="<?= e(app_url('/jobseeker/education')) ?>"><strong><?= (int) $educationCount ?></strong><span>Education</span></a>
        <a class="stat" href="<?= e(app_url('/jobseeker/experience')) ?>"><strong><?= (int) $experienceCount ?></strong><span>Experience</span></a>
        <a class="stat" href="<?= e(app_url('/jobseeker/skills')) ?>"><strong><?= (int) $skillsCount ?></strong><span>Skills</span></a>
        <a class="stat" href="<?= e(app_url('/jobseeker/languages')) ?>"><strong><?= (int) $languagesCount ?></strong><span>Languages</span></a>
        <a class="stat" href="<?= e(app_url('/jobseeker/cv')) ?>"><strong><?= $hasCv ? 'Yes' : 'No' ?></strong><span>CV uploaded</span></a>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Completeness checklist</h2>
    <ul class="checklist">
        <?php foreach ($completeness['sections'] as $section): ?>
            <li class="<?= $section['complete'] ? 'is-done' : '' ?>">
                <span><?= e($section['label']) ?></span>
                <em><?= $section['complete'] ? 'Done' : $section['weight'] . '%' ?></em>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a class="btn btn--primary" href="<?= e(app_url('/jobseeker/profile')) ?>">Edit profile</a></p>
</section>

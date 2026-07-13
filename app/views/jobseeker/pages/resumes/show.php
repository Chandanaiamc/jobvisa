<?php

declare(strict_types=1);

/** @var \JobVisa\App\Domain\Resume\DTO\ResumeData $resume */
$resumeDto = $resume;
$resumeSection = 'overview';
$resume = ['id' => $resumeDto->id, 'title' => $resumeDto->title];
require base_path('app/views/jobseeker/partials/resume-subnav.php');
$resume = $resumeDto;
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title"><?= e($resume->title) ?></h2>
            <p class="muted">
                <span class="badge badge--<?= e($resume->status) ?>"><?= e(ucfirst($resume->status)) ?></span>
                · <?= e($resume->visibility) ?>
                <?php if ($resume->isDefault): ?> · Default<?php endif; ?>
            </p>
        </div>
        <div class="btn-row">
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/personal')) ?>">Personal info</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/professional')) ?>">Professional</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/education')) ?>">Education</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/experience')) ?>">Experience</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/skills')) ?>">Skills</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/languages')) ?>">Languages</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/certifications')) ?>">Certifications</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/projects')) ?>">Projects</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/achievements')) ?>">Achievements</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/publications')) ?>">Publications</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/portfolio')) ?>">Portfolio</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/references')) ?>">References</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/intelligence')) ?>">Intelligence</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume->id . '/edit')) ?>">Settings</a>
        </div>
    </div>

    <div class="completeness" style="max-width:280px;margin:1rem 0">
        <div class="completeness__meta">
            <span>Completion</span>
            <strong><?= (int) $resume->completenessScore ?>%</strong>
        </div>
        <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $resume->completenessScore ?>" aria-valuemin="0" aria-valuemax="100">
            <span style="width: <?= (int) $resume->completenessScore ?>%"></span>
        </div>
    </div>

    <ul class="meta-list">
        <li><strong>Created:</strong> <?= e((string) ($resume->createdAt ?? '—')) ?></li>
        <li><strong>Updated:</strong> <?= e((string) ($resume->updatedAt ?? '—')) ?></li>
        <li><strong>CV file:</strong> <?= $resume->filePath ? 'Attached' : 'None (use CV page for default resume file)' ?></li>
    </ul>

    <p><a href="<?= e(app_url('/jobseeker/resumes')) ?>">← Back to resumes</a></p>
</section>

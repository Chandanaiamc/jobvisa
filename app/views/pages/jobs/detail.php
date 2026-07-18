<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $job
 */

$id = (int) ($job['id'] ?? 0);
$salaryParts = [];
if (isset($job['salary_min']) || isset($job['salary_max'])) {
    $cur = (string) ($job['salary_currency'] ?? '');
    $period = (string) ($job['salary_period'] ?? '');
    $min = $job['salary_min'] ?? null;
    $max = $job['salary_max'] ?? null;
    if ($min !== null && $max !== null) {
        $salaryParts[] = trim($cur . ' ' . number_format((float) $min) . ' – ' . number_format((float) $max));
    } elseif ($min !== null) {
        $salaryParts[] = trim($cur . ' ' . number_format((float) $min) . '+');
    } elseif ($max !== null) {
        $salaryParts[] = trim($cur . ' up to ' . number_format((float) $max));
    }
    if ($period !== '') {
        $salaryParts[] = 'per ' . $period;
    }
}
?>
<article class="job-detail" data-job-detail data-job-id="<?= $id ?>">
    <p class="job-detail__back"><a href="<?= e(app_url('/jobs')) ?>">← All jobs</a></p>
    <p class="job-detail__brand">JobVisa.lk</p>
    <h1 class="job-detail__title"><?= e((string) ($job['title'] ?? '')) ?></h1>
    <p class="job-detail__meta">
        <span><?= e((string) ($job['country_name'] ?? '—')) ?></span>
        <?php if (!empty($job['job_type_name'])): ?>
            <span>· <?= e((string) $job['job_type_name']) ?></span>
        <?php endif; ?>
        <?php if (!empty($job['visa_sponsorship'])): ?>
            <span class="job-card__badge">Visa sponsorship</span>
        <?php endif; ?>
    </p>

    <dl class="job-detail__facts">
        <?php if ($salaryParts !== []): ?>
            <div>
                <dt>Salary</dt>
                <dd><?= e(implode(' ', $salaryParts)) ?></dd>
            </div>
        <?php endif; ?>
        <?php if (isset($job['experience_min_years'])): ?>
            <div>
                <dt>Experience</dt>
                <dd><?= (int) $job['experience_min_years'] ?>+ years</dd>
            </div>
        <?php endif; ?>
        <?php if (!empty($job['application_deadline'])): ?>
            <div>
                <dt>Deadline</dt>
                <dd><?= e((string) $job['application_deadline']) ?></dd>
            </div>
        <?php endif; ?>
        <?php if (!empty($job['published_at'])): ?>
            <div>
                <dt>Published</dt>
                <dd><?= e((string) $job['published_at']) ?></dd>
            </div>
        <?php endif; ?>
    </dl>

    <?php if (!empty($job['description'])): ?>
        <section class="job-detail__section" aria-labelledby="job-desc-heading">
            <h2 id="job-desc-heading">Description</h2>
            <div class="job-detail__prose"><?= nl2br(e((string) $job['description'])) ?></div>
        </section>
    <?php endif; ?>

    <?php if (!empty($job['requirements'])): ?>
        <section class="job-detail__section" aria-labelledby="job-req-heading">
            <h2 id="job-req-heading">Requirements</h2>
            <div class="job-detail__prose"><?= nl2br(e((string) $job['requirements'])) ?></div>
        </section>
    <?php endif; ?>

    <?php if (!empty($job['benefits'])): ?>
        <section class="job-detail__section" aria-labelledby="job-ben-heading">
            <h2 id="job-ben-heading">Benefits</h2>
            <div class="job-detail__prose"><?= nl2br(e((string) $job['benefits'])) ?></div>
        </section>
    <?php endif; ?>

    <p class="job-detail__note">Applications via the JobVisa seeker portal will be available in a later release. Sign in to manage your profile and resumes today.</p>
</article>

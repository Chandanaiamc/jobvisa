<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $jobs
 * @var array<string, int> $pagination
 * @var array<string, mixed> $filters
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $jobTypes
 * @var string $apiListUrl
 */

$q = (string) ($filters['q'] ?? '');
$countryId = (int) ($filters['country_id'] ?? 0);
$jobTypeId = (int) ($filters['job_type_id'] ?? 0);
$page = (int) ($pagination['page'] ?? 1);
$perPage = (int) ($pagination['per_page'] ?? 12);
$total = (int) ($pagination['total'] ?? 0);
$totalPages = (int) ($pagination['total_pages'] ?? 1);

$queryBase = static function (array $overrides = []) use ($q, $countryId, $jobTypeId, $perPage): string {
    $params = array_filter([
        'q' => $overrides['q'] ?? $q,
        'country_id' => $overrides['country_id'] ?? ($countryId > 0 ? $countryId : null),
        'job_type_id' => $overrides['job_type_id'] ?? ($jobTypeId > 0 ? $jobTypeId : null),
        'page' => $overrides['page'] ?? null,
        'per_page' => $overrides['per_page'] ?? $perPage,
    ], static fn ($v) => $v !== null && $v !== '' && $v !== 0);
    $qs = http_build_query($params);

    return app_url('/jobs') . ($qs !== '' ? '?' . $qs : '');
};
?>
<section class="jobs-hero" aria-labelledby="jobs-heading"
         data-jobs-board
         data-api-url="<?= e($apiListUrl) ?>"
         data-detail-base="<?= e(app_url('/jobs')) ?>">
    <div class="jobs-hero__inner">
        <p class="jobs-hero__brand">JobVisa.lk</p>
        <h1 id="jobs-heading" class="jobs-hero__title">Overseas roles, clearly listed</h1>
        <p class="jobs-hero__lead">Search published opportunities by keyword, destination, and job type.</p>
    </div>
</section>

<section class="jobs-panel" aria-label="Search jobs">
    <form class="jobs-filters" method="get" action="<?= e(app_url('/jobs')) ?>" data-jobs-filters>
        <div class="jobs-filters__row">
            <div class="jobs-field">
                <label for="jobs-q">Keyword</label>
                <input id="jobs-q" name="q" type="search" value="<?= e($q) ?>"
                       placeholder="e.g. nurse, engineer" maxlength="120" autocomplete="off">
            </div>
            <div class="jobs-field">
                <label for="jobs-country">Country</label>
                <select id="jobs-country" name="country_id">
                    <option value="">All countries</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= (int) $country['id'] ?>"<?= $countryId === (int) $country['id'] ? ' selected' : '' ?>>
                            <?= e((string) $country['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jobs-field">
                <label for="jobs-type">Job type</label>
                <select id="jobs-type" name="job_type_id">
                    <option value="">All types</option>
                    <?php foreach ($jobTypes as $type): ?>
                        <option value="<?= (int) $type['id'] ?>"<?= $jobTypeId === (int) $type['id'] ? ' selected' : '' ?>>
                            <?= e((string) $type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="per_page" value="<?= $perPage ?>">
            <div class="jobs-filters__actions">
                <button type="submit" class="jobs-btn jobs-btn--primary">Search</button>
                <a class="jobs-btn jobs-btn--ghost" href="<?= e(app_url('/jobs')) ?>">Reset</a>
            </div>
        </div>
    </form>

    <div class="jobs-status" data-jobs-status role="status" aria-live="polite">
        <?= $total === 1 ? '1 published role' : $total . ' published roles' ?>
        <?php if ($q !== '' || $countryId > 0 || $jobTypeId > 0): ?>
            matching your filters
        <?php endif; ?>
    </div>

    <div class="jobs-list" data-jobs-list>
        <?php if ($jobs === []): ?>
            <p class="jobs-empty" data-jobs-empty>No published jobs match these filters.</p>
        <?php else: ?>
            <?php foreach ($jobs as $job): ?>
                <?php
                $id = (int) ($job['id'] ?? 0);
                $href = app_url('/jobs/' . $id);
                ?>
                <article class="job-card">
                    <h2 class="job-card__title">
                        <a href="<?= e($href) ?>"><?= e((string) ($job['title'] ?? '')) ?></a>
                    </h2>
                    <p class="job-card__meta">
                        <span><?= e((string) ($job['country_name'] ?? '—')) ?></span>
                        <?php if (!empty($job['job_type_name'])): ?>
                            <span>· <?= e((string) $job['job_type_name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($job['visa_sponsorship'])): ?>
                            <span class="job-card__badge">Visa sponsorship</span>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($job['summary'])): ?>
                        <p class="job-card__summary"><?= e((string) $job['summary']) ?></p>
                    <?php endif; ?>
                    <a class="job-card__link" href="<?= e($href) ?>">View details</a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <nav class="jobs-pagination" data-jobs-pagination aria-label="Jobs pagination"
         data-page="<?= $page ?>" data-total-pages="<?= $totalPages ?>">
        <?php if ($totalPages > 1): ?>
            <?php if ($page > 1): ?>
                <a class="jobs-btn jobs-btn--ghost" href="<?= e($queryBase(['page' => $page - 1])) ?>" data-page-link="<?= $page - 1 ?>">Previous</a>
            <?php endif; ?>
            <span class="jobs-pagination__label">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="jobs-btn jobs-btn--ghost" href="<?= e($queryBase(['page' => $page + 1])) ?>" data-page-link="<?= $page + 1 ?>">Next</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</section>

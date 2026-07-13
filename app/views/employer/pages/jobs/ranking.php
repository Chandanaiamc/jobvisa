<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $job
 * @var \JobVisa\App\Domain\ApplicantRanking\DTO\RankingFilterDTO $filters
 * @var list<\JobVisa\App\Domain\ApplicantRanking\DTO\RankedApplicantDTO> $candidates
 * @var int $totalRanked
 * @var int $totalFiltered
 * @var bool $canRecalculate
 * @var string $disclaimer
 * @var array<string, mixed> $filterInput
 */

$jobId = (int) ($job['id'] ?? 0);
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">Top candidates</h1>
            <p class="panel__lead"><?= e((string) ($job['title'] ?? '')) ?></p>
        </div>
        <div class="btn-row">
            <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs')) ?>">All jobs</a>
            <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking/history')) ?>">Ranking history</a>
        </div>
    </div>
    <p class="muted"><?= e($disclaimer) ?></p>
    <p class="muted">Showing <?= (int) $totalFiltered ?> of <?= (int) $totalRanked ?> ranked applicants
        · Job status: <?= e((string) ($job['status'] ?? '')) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Filter & sort</h2>
    <form method="get" action="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking')) ?>" class="btn-row" style="flex-wrap:wrap; gap:0.75rem; align-items:end">
        <label class="field">
            <span class="muted">Status</span>
            <select name="status">
                <?php
                $statusVal = (string) ($filterInput['status'] ?? 'all');
                $statusOpts = ['all' => 'All', 'submitted' => 'Submitted', 'reviewing' => 'Reviewing', 'shortlisted' => 'Shortlisted', 'rejected' => 'Rejected', 'hired' => 'Hired', 'withdrawn' => 'Withdrawn'];
                foreach ($statusOpts as $val => $label):
                ?>
                    <option value="<?= e($val) ?>" <?= $statusVal === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="muted">Min overall</span>
            <input type="number" name="min_score" min="0" max="100" value="<?= e((string) ($filterInput['min_score'] ?? '')) ?>" placeholder="0">
        </label>
        <label class="field">
            <span class="muted">Sort</span>
            <select name="sort">
                <?php foreach (['rank' => 'Rank', 'overall' => 'Overall', 'match' => 'Job match', 'resume' => 'Resume score', 'applied' => 'Applied date'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($filterInput['sort'] ?? 'rank') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="muted">Direction</span>
            <select name="dir">
                <option value="desc" <?= (($filterInput['dir'] ?? 'desc') === 'desc') ? 'selected' : '' ?>>Desc</option>
                <option value="asc" <?= (($filterInput['dir'] ?? '') === 'asc') ? 'selected' : '' ?>>Asc</option>
            </select>
        </label>
        <label class="field">
            <span class="muted">Search</span>
            <input type="text" name="q" value="<?= e((string) ($filterInput['q'] ?? '')) ?>" placeholder="Name or email">
        </label>
        <button type="submit" class="btn btn--primary">Apply</button>
    </form>
    <?php if ($canRecalculate): ?>
        <form method="post" action="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking/recalculate')) ?>" style="margin-top:0.75rem">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--secondary">Recalculate rankings</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Ranked applicants</h2>
    <?php if ($candidates === []): ?>
        <p class="muted">No applicants match these filters. Recalculate after applications are submitted.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Status</th>
                        <th>Overall</th>
                        <th>Resume</th>
                        <th>Match</th>
                        <th>Skills</th>
                        <th>Exp</th>
                        <th>Edu</th>
                        <th>Cert</th>
                        <th>Portfolio</th>
                        <th>Refs</th>
                        <th>Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td><strong><?= (int) $c->rankPosition ?></strong></td>
                            <td>
                                <?= e($c->applicantName) ?>
                                <div class="muted"><?= e($c->applicantEmail) ?></div>
                            </td>
                            <td><span class="badge"><?= e($c->applicationStatus) ?></span></td>
                            <td><strong><?= (int) $c->overallScore ?></strong></td>
                            <td><?= (int) $c->resumeScore ?></td>
                            <td><?= (int) $c->jobMatchScore ?></td>
                            <td><?= (int) $c->skillsScore ?></td>
                            <td><?= (int) $c->experienceScore ?></td>
                            <td><?= (int) $c->educationScore ?></td>
                            <td><?= (int) $c->certificationScore ?></td>
                            <td><?= (int) $c->portfolioScore ?></td>
                            <td><?= (int) $c->referencesScore ?></td>
                            <td><?= e($c->appliedAt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

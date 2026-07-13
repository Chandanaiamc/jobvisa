<?php

declare(strict_types=1);

/**
 * @var \JobVisa\App\Domain\EmployerDashboard\DTO\EmployerAiDashboardDTO $dash
 * @var bool $canRefresh
 * @var string $disclaimer
 */

$health = $dash->health;
$maxMatchChart = 100;
foreach ($dash->chartMatchByJob as $row) {
    $maxMatchChart = max($maxMatchChart, (float) ($row['value'] ?? 0));
}
$maxStatus = 1;
foreach ($dash->chartStatusMix as $row) {
    $maxStatus = max($maxStatus, (int) ($row['value'] ?? 0));
}
$maxBand = 1;
foreach ($dash->chartScoreBands as $row) {
    $maxBand = max($maxBand, (int) ($row['value'] ?? 0));
}
$maxGap = 1;
foreach ($dash->skillGaps as $row) {
    $maxGap = max($maxGap, (int) ($row['count'] ?? 0));
}
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Hiring Dashboard</h1>
            <p class="panel__lead">Deterministic hiring insights across your jobs and applicants.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs')) ?>">Jobs & ranking</a>
            <?php if ($canRefresh): ?>
                <form method="post" action="<?= e(app_url('/employer/ai-dashboard/refresh')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary">Refresh insights</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <p class="muted"><?= e($disclaimer) ?></p>
    <p class="muted">Generated <?= e($dash->generatedAt) ?></p>
</section>

<section class="panel intelligence-scores" aria-label="Hiring health metrics">
    <div class="intelligence-scores__grid">
        <div class="intelligence-score-card">
            <p class="muted">Hiring health</p>
            <p class="intelligence-score-card__value"><?= (int) ($health['score'] ?? 0) ?></p>
            <p class="muted"><?= e((string) ($health['label'] ?? '')) ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Avg AI match</p>
            <p class="intelligence-score-card__value"><?= e(number_format($dash->averageMatchScore, 1)) ?></p>
            <p class="muted">Across applicant matches</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Avg ranking score</p>
            <p class="intelligence-score-card__value"><?= e(number_format($dash->averageRankingScore, 1)) ?></p>
            <p class="muted">Applicant ranking overall</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Applicants</p>
            <p class="intelligence-score-card__value"><?= (int) $dash->applicantsCount ?></p>
            <p class="muted"><?= (int) $dash->publishedJobsCount ?> published / <?= (int) $dash->jobsCount ?> jobs</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Interview-ready</p>
            <p class="intelligence-score-card__value"><?= count($dash->interviewReady) ?></p>
            <p class="muted">Rank ≥70 & match ≥55</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Active pipeline</p>
            <p class="intelligence-score-card__value"><?= (int) ($health['pipeline_active'] ?? 0) ?></p>
            <p class="muted">Submitted / reviewing / shortlisted</p>
        </div>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">AI hiring insights</h2>
    <ul class="insight-list">
        <?php foreach ($dash->insights as $insight): ?>
            <li><?= e((string) $insight) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Dashboard charts</h2>
    <div class="employer-charts">
        <div class="employer-chart">
            <h3 class="employer-chart__title">Average match by job</h3>
            <?php if ($dash->chartMatchByJob === []): ?>
                <p class="muted">No job match data yet.</p>
            <?php else: ?>
                <ul class="chart-bars" role="list">
                    <?php foreach ($dash->chartMatchByJob as $row): ?>
                        <?php $pct = $maxMatchChart > 0 ? (int) round(((float) $row['value'] / $maxMatchChart) * 100) : 0; ?>
                        <li>
                            <div class="chart-bars__meta">
                                <span><?= e((string) $row['label']) ?></span>
                                <strong><?= e(number_format((float) $row['value'], 1)) ?></strong>
                            </div>
                            <div class="chart-bars__track"><span style="width:<?= $pct ?>%"></span></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="employer-chart">
            <h3 class="employer-chart__title">Application status mix</h3>
            <?php if ($dash->chartStatusMix === []): ?>
                <p class="muted">No applications yet.</p>
            <?php else: ?>
                <ul class="chart-bars" role="list">
                    <?php foreach ($dash->chartStatusMix as $row): ?>
                        <?php $pct = (int) round(((int) $row['value'] / $maxStatus) * 100); ?>
                        <li>
                            <div class="chart-bars__meta">
                                <span><?= e((string) $row['label']) ?></span>
                                <strong><?= (int) $row['value'] ?></strong>
                            </div>
                            <div class="chart-bars__track"><span style="width:<?= $pct ?>%"></span></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="employer-chart">
            <h3 class="employer-chart__title">Ranking score bands</h3>
            <ul class="chart-bars" role="list">
                <?php foreach ($dash->chartScoreBands as $row): ?>
                    <?php $pct = (int) round(((int) $row['value'] / $maxBand) * 100); ?>
                    <li>
                        <div class="chart-bars__meta">
                            <span><?= e((string) $row['label']) ?></span>
                            <strong><?= (int) $row['value'] ?></strong>
                        </div>
                        <div class="chart-bars__track"><span style="width:<?= $pct ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Skill gap analytics</h2>
    <p class="panel__lead">Most frequent missing required skills across job match analyses.</p>
    <?php if ($dash->skillGaps === []): ?>
        <p class="muted">No skill-gap concentrations detected yet.</p>
    <?php else: ?>
        <ul class="chart-bars" role="list">
            <?php foreach ($dash->skillGaps as $gap): ?>
                <?php $pct = (int) round(((int) $gap['count'] / $maxGap) * 100); ?>
                <li>
                    <div class="chart-bars__meta">
                        <span><?= e((string) $gap['label']) ?></span>
                        <strong><?= (int) $gap['count'] ?></strong>
                    </div>
                    <div class="chart-bars__track chart-bars__track--accent"><span style="width:<?= $pct ?>%"></span></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">Top ranked candidates</h2>
    </div>
    <?php if ($dash->topCandidates === []): ?>
        <p class="muted">No ranked candidates yet. Open a job and recalculate rankings after applications arrive.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job</th>
                        <th>Overall</th>
                        <th>Match</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dash->topCandidates as $c): ?>
                        <tr>
                            <td>
                                <?= e((string) ($c['applicant_name'] ?? '')) ?>
                                <div class="muted"><?= e((string) ($c['applicant_email'] ?? '')) ?></div>
                            </td>
                            <td><?= e((string) ($c['job_title'] ?? '')) ?></td>
                            <td><strong><?= (int) ($c['overall_score'] ?? 0) ?></strong></td>
                            <td><?= (int) ($c['job_match_score'] ?? 0) ?></td>
                            <td><?= (int) ($c['resume_score'] ?? 0) ?></td>
                            <td><span class="badge"><?= e((string) ($c['application_status'] ?? '')) ?></span></td>
                            <td>
                                <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . (int) ($c['job_id'] ?? 0) . '/applicants/ranking')) ?>">Open ranking</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Interview-ready candidates</h2>
    <p class="panel__lead">High ranking and strong job-match alignment.</p>
    <?php if ($dash->interviewReady === []): ?>
        <p class="muted">No interview-ready candidates yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job</th>
                        <th>Overall</th>
                        <th>Match</th>
                        <th>Skills</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dash->interviewReady as $c): ?>
                        <tr>
                            <td><?= e((string) ($c['applicant_name'] ?? '')) ?></td>
                            <td><?= e((string) ($c['job_title'] ?? '')) ?></td>
                            <td><strong><?= (int) ($c['overall_score'] ?? 0) ?></strong></td>
                            <td><?= (int) ($c['job_match_score'] ?? 0) ?></td>
                            <td><?= (int) ($c['skills_score'] ?? 0) ?></td>
                            <td>
                                <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . (int) ($c['job_id'] ?? 0) . '/applicants/ranking')) ?>">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

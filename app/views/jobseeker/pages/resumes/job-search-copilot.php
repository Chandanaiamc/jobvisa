<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\JobSearchCopilot\DTO\JobSearchCopilotPlanDTO|null $copilotPlan
 * @var string $defaultCareerGoal
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'job-search-copilot';
$copilotPlan = $copilotPlan ?? null;
$defaultCareerGoal = $defaultCareerGoal ?? '';
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$p = $copilotPlan?->plan ?? [];
$filters = is_array($p['recommended_filters'] ?? null) ? $p['recommended_filters'] : [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Job Search Copilot</h1>
            <p class="panel__lead">Build a search strategy, ranked shortlist and weekly apply plan from your resume and market signals.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/job-search-copilot/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Generate search plan</h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/job-search-copilot/' . ($copilotPlan ? 'recalculate' : 'generate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Career goal / target role</span>
                <input type="text" name="career_goal" maxlength="255" value="<?= e((string) ($copilotPlan?->careerGoal ?: $defaultCareerGoal)) ?>" placeholder="e.g. Senior Registered Nurse — Dubai">
            </label>
            <button type="submit" class="btn btn--primary"><?= $copilotPlan ? 'Recalculate plan' : 'Generate search plan' ?></button>
        </form>
    <?php endif; ?>
</section>

<?php if ($copilotPlan !== null): ?>
<section class="panel">
    <h2 class="panel__title">Copilot overview</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Copilot score</th>
                    <th>Recommendations</th>
                    <th>Goal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= (int) $copilotPlan->copilotScore ?>/100</strong></td>
                    <td><?= (int) $copilotPlan->recommendationCount ?></td>
                    <td><?= e($copilotPlan->careerGoal) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><?= e((string) ($p['summary'] ?? '')) ?></p>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/job-search-copilot/plans/' . (int) $copilotPlan->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Search queries</h2>
    <ul>
        <?php foreach (($p['search_queries'] ?? []) as $q): ?>
            <li><?= e((string) $q) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended filters</h2>
    <div class="table-wrap">
        <table class="table">
            <tbody>
                <tr><th>Seniority</th><td><?= e((string) ($filters['seniority'] ?? '')) ?></td></tr>
                <tr><th>Experience</th><td><?= e((string) ($filters['experience_band'] ?? '')) ?></td></tr>
                <tr><th>Locations</th><td><?= e(implode(', ', array_map('strval', $filters['preferred_locations'] ?? []))) ?></td></tr>
                <tr><th>Keywords</th><td><?= e(implode(', ', array_map('strval', $filters['keywords'] ?? []))) ?></td></tr>
                <tr><th>Salary floor hint</th><td><?= isset($filters['salary_floor_hint']) && $filters['salary_floor_hint'] ? e((string) $filters['salary_floor_hint']) : '—' ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Ranked recommendations</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Role</th>
                    <th>Category</th>
                    <th>Score</th>
                    <th>Urgency</th>
                    <th>Reasons</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($p['recommendations'] ?? []) as $rec): ?>
                    <?php if (!is_array($rec)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= (int) ($rec['priority'] ?? 0) ?></td>
                        <td>
                            <strong><?= e((string) ($rec['title'] ?? '')) ?></strong>
                            <div class="muted"><?= e(trim((string) ($rec['country'] ?? '') . ' · ' . (string) ($rec['job_type'] ?? ''), ' ·')) ?></div>
                        </td>
                        <td><span class="badge"><?= e((string) ($rec['category'] ?? '')) ?></span></td>
                        <td><?= (int) ($rec['score'] ?? 0) ?>/100</td>
                        <td><?= e((string) ($rec['apply_urgency'] ?? '')) ?></td>
                        <td>
                            <ul>
                                <?php foreach (($rec['reasons'] ?? []) as $reason): ?>
                                    <li><?= e((string) $reason) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Weekly search plan</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Day</th><th>Focus</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach (($p['weekly_search_plan'] ?? []) as $day): ?>
                    <?php if (!is_array($day)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= e((string) ($day['day'] ?? '')) ?></td>
                        <td><?= e((string) ($day['focus'] ?? '')) ?></td>
                        <td>
                            <ul>
                                <?php foreach (($day['actions'] ?? []) as $action): ?>
                                    <li><?= e((string) $action) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Strategy tips</h2>
    <ul>
        <?php foreach (($p['strategy_tips'] ?? []) as $tip): ?>
            <li><?= e((string) $tip) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3>Alert keywords</h3>
    <p><?= e(implode(' · ', array_map('strval', $p['alert_keywords'] ?? []))) ?></p>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Recent history</h2>
    <?php if ($history === []): ?>
        <p class="muted">No job search copilot history yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Headline</th><th>Action</th><th>Score</th><th>When</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($history, 0, 8) as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['headline'] ?? '')) ?></td>
                            <td><?= e((string) ($row['action'] ?? '')) ?></td>
                            <td><?= (int) ($row['copilot_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

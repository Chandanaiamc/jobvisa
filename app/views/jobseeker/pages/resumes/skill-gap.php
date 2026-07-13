<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var list<array<string, mixed>> $matchedJobs
 * @var int|null $selectedJobId
 * @var \JobVisa\App\Domain\SkillGap\DTO\SkillGapAnalysisDTO|null $analysis
 * @var list<array<string, mixed>> $versions
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'skill-gap';
$matchedJobs = $matchedJobs ?? [];
$selectedJobId = $selectedJobId ?? null;
$analysis = $analysis ?? null;
$versions = $versions ?? [];
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$cmp = $analysis?->analysis['comparison'] ?? [];
$priority = $analysis?->analysis['priority_learning_order'] ?? [];
$roadmap = $analysis?->analysis['learning_roadmap'] ?? [];
$certs = $analysis?->analysis['recommended_certifications'] ?? [];
$courses = $analysis?->analysis['recommended_courses'] ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Skill Gap Analyzer</h1>
            <p class="panel__lead">Compare your resume skills to a target job and get a learning roadmap.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skill-gap/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Target job</h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skill-gap/' . ($analysis ? 'recalculate' : 'analyze'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Select job</span>
                <select name="job_id" required>
                    <option value="">Choose a published job…</option>
                    <?php foreach ($matchedJobs as $job): ?>
                        <?php $jid = (int) ($job['job_id'] ?? $job['id'] ?? 0); ?>
                        <option value="<?= $jid ?>" <?= $selectedJobId === $jid ? 'selected' : '' ?>>
                            <?= e((string) ($job['job_title'] ?? $job['title'] ?? ('Job #' . $jid))) ?>
                            <?php if (isset($job['overall_score'])): ?>
                                (match <?= (int) $job['overall_score'] ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn--primary"><?= $analysis ? 'Recalculate gap' : 'Analyze skill gap' ?></button>
        </form>
    <?php elseif ($matchedJobs === []): ?>
        <p class="muted">No published jobs available to compare.</p>
    <?php endif; ?>
</section>

<?php if ($analysis !== null): ?>
<section class="panel">
    <h2 class="panel__title">Gap snapshot</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Gap %</th>
                    <th>Readiness</th>
                    <th>Skill match</th>
                    <th>Matched</th>
                    <th>Missing</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= (int) $analysis->gapPercentage ?>%</strong> <span class="badge"><?= e((string) ($analysis->analysis['gap_label'] ?? '')) ?></span></td>
                    <td><?= (int) $analysis->readinessScore ?>/100 <span class="badge"><?= e((string) ($analysis->analysis['readiness_label'] ?? '')) ?></span></td>
                    <td><?= (int) $analysis->matchSkillsScore ?>/100</td>
                    <td><?= (int) $analysis->matchedCount ?></td>
                    <td><?= (int) $analysis->missingCount ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><strong><?= e($analysis->jobTitle) ?></strong></p>
    <p><?= e((string) ($analysis->analysis['explanation'] ?? '')) ?></p>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/skill-gap/analyses/' . (int) $analysis->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Matching skills</h2>
    <?php if (($cmp['matched_skills'] ?? []) === []): ?>
        <p class="muted">No matched skill signals yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($cmp['matched_skills'] as $skill): ?>
                <li><?= e((string) $skill) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Missing skills</h2>
    <?php if (($cmp['missing_skills'] ?? []) === []): ?>
        <p class="muted">No missing skills detected for this comparison.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($cmp['missing_skills'] as $skill): ?>
                <li><?= e((string) $skill) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Strengths</h2>
    <ul>
        <?php foreach (($analysis->analysis['strengths'] ?? []) as $s): ?>
            <li><?= e((string) $s) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Weaknesses</h2>
    <ul>
        <?php foreach (($analysis->analysis['weaknesses'] ?? []) as $s): ?>
            <li><?= e((string) $s) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Priority learning order</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Priority</th><th>Skill</th><th>Reason</th></tr></thead>
            <tbody>
                <?php foreach ($priority as $row): ?>
                    <?php if (!is_array($row)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= e((string) ($row['priority'] ?? '')) ?></td>
                        <td><?= e((string) ($row['skill'] ?? '')) ?></td>
                        <td><?= e((string) ($row['reason'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Learning roadmap</h2>
    <ul>
        <?php foreach ($roadmap as $phase): ?>
            <?php if (!is_array($phase)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($phase['phase'] ?? '')) ?></strong> (<?= (int) ($phase['weeks'] ?? 0) ?> weeks) — <?= e((string) ($phase['focus'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended certifications</h2>
    <ul>
        <?php foreach ($certs as $c): ?>
            <?php if (!is_array($c)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($c['name'] ?? '')) ?></strong> → <?= e((string) ($c['maps_to'] ?? '')) ?> (<?= e((string) ($c['why'] ?? '')) ?>)</li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended courses</h2>
    <ul>
        <?php foreach ($courses as $c): ?>
            <?php if (!is_array($c)) {
                continue;
            } ?>
            <li><?= e((string) ($c['title'] ?? '')) ?> — <?= e((string) ($c['provider'] ?? '')) ?> [<?= e((string) ($c['level'] ?? '')) ?>]</li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($history !== []): ?>
<section class="panel">
    <h2 class="panel__title">Recent history</h2>
    <ul>
        <?php foreach (array_slice($history, 0, 8) as $row): ?>
            <li><?= e((string) ($row['headline'] ?? '')) ?> · <?= e((string) ($row['created_at'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $job
 * @var list<array<string, mixed>> $resumes
 * @var int|null $selectedResumeId
 * @var \JobVisa\App\Domain\ApplicationAssistant\DTO\ApplicationAnalysisDTO|null $analysis
 * @var list<array<string, mixed>> $versions
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var string $version
 * @var string $disclaimer
 */

$analysis = $analysis ?? null;
$resumes = $resumes ?? [];
$selectedResumeId = $selectedResumeId ?? null;
$versions = $versions ?? [];
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
$cmp = $analysis?->analysis['comparison'] ?? [];
$recs = $analysis?->analysis['recommendations'] ?? [];
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Application Assistant</h1>
            <p class="panel__lead">Compare your resume to <strong><?= e((string) ($job['title'] ?? '')) ?></strong> before you apply.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/jobs/' . (int) ($job['id'] ?? 0) . '/application-assistant/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Select resume & analyze</h2>
    <?php if ($resumes === []): ?>
        <p class="muted">Create a resume first, then return here to analyze readiness.</p>
    <?php else: ?>
        <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/analyze')) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Resume</span>
                <select name="resume_id" required>
                    <?php foreach ($resumes as $r): ?>
                        <option value="<?= (int) ($r['id'] ?? 0) ?>" <?= (int) ($r['id'] ?? 0) === (int) $selectedResumeId ? 'selected' : '' ?>>
                            <?= e((string) ($r['title'] ?? 'Resume')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn--primary">Analyze readiness</button>
        </form>
        <?php if ($analysis !== null): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/recalculate')) ?>" style="margin-top:.75rem">
                <?= csrf_field() ?>
                <input type="hidden" name="resume_id" value="<?= $analysis->resumeId ?>">
                <button type="submit" class="btn btn--secondary">Recalculate</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if ($analysis === null): ?>
<section class="panel"><p class="muted">No analysis yet for this job. Select a resume and run Analyze.</p></section>
<?php else: ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Application readiness</h2>
            <p class="muted"><?= e((string) ($analysis->analysis['readiness_label'] ?? '')) ?> · <?= e($analysis->resumeTitle) ?></p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant/analyses/' . $analysis->id . '/export/pdf')) ?>">Export PDF</a>
    </div>
    <p style="font-size:2rem;font-weight:700;margin:0"><?= $analysis->readinessScore ?><span class="muted" style="font-size:1rem">/100</span></p>
    <div class="table-wrap" style="margin-top:1rem">
        <table class="table">
            <thead>
                <tr>
                    <th>Skills</th>
                    <th>Experience</th>
                    <th>Education</th>
                    <th>Certifications</th>
                    <th>Portfolio</th>
                    <th>Job match</th>
                    <th>Resume score</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $analysis->skillsScore ?></td>
                    <td><?= $analysis->experienceScore ?></td>
                    <td><?= $analysis->educationScore ?></td>
                    <td><?= $analysis->certificationScore ?></td>
                    <td><?= $analysis->portfolioScore ?></td>
                    <td><?= $analysis->matchOverall ?></td>
                    <td><?= $analysis->resumeOverall ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Resume vs job comparison</h2>
    <p><strong>Matched:</strong> <?= e(implode(', ', $cmp['matched_requirements'] ?? []) ?: '—') ?></p>
    <p><strong>Missing skills:</strong> <?= e(implode(', ', $cmp['missing_skills'] ?? []) ?: '—') ?></p>
    <p><strong>Missing ATS keywords:</strong> <?= e(implode(', ', $cmp['missing_ats_keywords'] ?? []) ?: '—') ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Strengths</h2>
    <ul><?php foreach (($analysis->analysis['strengths'] ?? []) as $s): ?><li><?= e((string) $s) ?></li><?php endforeach; ?></ul>
</section>

<section class="panel">
    <h2 class="panel__title">Weaknesses</h2>
    <ul><?php foreach (($analysis->analysis['weaknesses'] ?? []) as $s): ?><li><?= e((string) $s) ?></li><?php endforeach; ?></ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommendations before applying</h2>
    <ul class="record-list">
        <?php foreach ($recs as $r): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($r['action'] ?? '')) ?></strong>
                    <span class="badge"><?= e((string) ($r['priority'] ?? '')) ?></span>
                </div>
                <?php if (!empty($r['link'])): ?>
                    <a class="btn btn--primary" href="<?= e(app_url((string) $r['link'])) ?>">Open</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem">
        <a class="btn btn--primary" href="<?= e(app_url('/jobseeker/resumes/' . $analysis->resumeId . '/ai-builder')) ?>">AI Resume Builder</a>
        <a class="btn btn--primary" href="<?= e(app_url('/jobseeker/resumes/' . $analysis->resumeId . '/cover-letters')) ?>">Cover Letter Generator</a>
    </div>
</section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Analysis versions</h2>
    <?php if ($versions === []): ?>
        <p class="muted">No saved analyses yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($versions as $v): ?>
                <li class="record">
                    <div>
                        <strong>Readiness <?= (int) ($v['readiness_score'] ?? 0) ?>/100</strong>
                        <p class="muted"><?= e((string) ($v['resume_title'] ?? '')) ?> · <?= e((string) ($v['created_at'] ?? '')) ?></p>
                    </div>
                    <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/jobs/' . (int) $job['id'] . '/application-assistant?resume=' . (int) ($v['resume_id'] ?? 0))) ?>">View</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

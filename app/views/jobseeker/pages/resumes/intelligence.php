<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var array{score: int, sections: array} $completion
 * @var \JobVisa\App\Domain\Resume\Intelligence\DTO\ResumeIntelligenceDTO $intelligence
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $disclaimer
 */

$resumeSection = 'intelligence';
$intel = $intelligence;
$history = $history ?? [];
$kw = $intel->keywordAnalysis();
$gaps = $intel->skillGapAnalysis();
$severityClass = static function (string $severity): string {
    return match ($severity) {
        'high' => 'badge--danger',
        'medium' => 'badge--warning',
        'low' => 'badge',
        default => 'badge',
    };
};
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Resume Intelligence</h2>
            <p class="panel__lead">Explainable quality scores to guide improvements — separate from resume completion percentage.</p>
        </div>
        <div class="completeness" style="min-width:200px">
            <div class="completeness__meta">
                <span>Resume completion</span>
                <strong><?= (int) $completion['score'] ?>%</strong>
            </div>
            <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $completion['score'] ?>" aria-valuemin="0" aria-valuemax="100">
                <span style="width: <?= (int) $completion['score'] ?>%"></span>
            </div>
        </div>
    </div>
    <p class="muted"><?= e($disclaimer) ?></p>
</section>

<section class="panel intelligence-scores" aria-label="Intelligence scores">
    <div class="intelligence-scores__grid">
        <div class="intelligence-score-card">
            <p class="muted">Overall Resume Score</p>
            <p class="intelligence-score-card__value"><?= (int) $intel->overallScore ?></p>
            <p class="muted">Strength: <strong><?= e($intel->strengthLabel()) ?></strong></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">ATS Readiness</p>
            <p class="intelligence-score-card__value"><?= (int) $intel->atsScore ?></p>
            <p class="muted">Heuristic only — not an ATS approval guarantee</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Employer Readiness</p>
            <p class="intelligence-score-card__value"><?= (int) $intel->employerReadinessScore ?></p>
            <p class="muted">Based on shareable, review-ready content</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Keyword Match</p>
            <p class="intelligence-score-card__value"><?= (int) $intel->keywordMatchScore ?></p>
            <p class="muted">Coverage vs role-expected keywords</p>
        </div>
    </div>
    <p class="muted" style="margin-top:0.75rem">
        Rules version <?= e($intel->rulesVersion) ?>
        <?php if ($intel->calculatedAt !== ''): ?>
            · Calculated <?= e($intel->calculatedAt) ?>
        <?php endif; ?>
        <?php if ($intel->targetRole): ?>
            · Role focus: <?= e((string) $intel->targetRole) ?>
        <?php endif; ?>
    </p>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/intelligence/recalculate')) ?>" class="btn-row" style="margin-top:0.75rem; flex-wrap:wrap; gap:0.5rem; align-items:end">
            <?= csrf_field() ?>
            <label class="field" style="min-width:220px; flex:1">
                <span class="muted">Target role (optional)</span>
                <input type="text" name="target_role" maxlength="200" value="<?= e((string) ($intel->targetRole ?? '')) ?>" placeholder="e.g. Software Engineer" autocomplete="organization-title">
            </label>
            <button type="submit" class="btn btn--primary">Recalculate</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Keyword matching</h2>
    <p class="panel__lead">Matched vs missing keywords for the resolved role taxonomy.</p>
    <?php if ($kw === []): ?>
        <p class="muted">Recalculate to generate keyword analysis.</p>
    <?php else: ?>
        <p class="muted">Role: <strong><?= e((string) ($kw['role'] ?? 'general')) ?></strong>
            · Matched <?= count($kw['matched'] ?? []) ?> / <?= count($kw['target_keywords'] ?? []) ?></p>
        <?php if (!empty($kw['matched'])): ?>
            <p><strong>Matched:</strong> <?= e(implode(', ', array_map('strval', $kw['matched']))) ?></p>
        <?php endif; ?>
        <?php if (!empty($kw['missing'])): ?>
            <p><strong>Missing:</strong> <?= e(implode(', ', array_map('strval', $kw['missing']))) ?></p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Skill gap analysis</h2>
    <p class="panel__lead">Expected skills for your role vs skills listed on this resume.</p>
    <?php if ($gaps === []): ?>
        <p class="muted">Recalculate to generate skill gap analysis.</p>
    <?php else: ?>
        <p class="muted">Coverage: <strong><?= (int) ($gaps['coverage_percent'] ?? 0) ?>%</strong>
            · Role: <?= e((string) ($gaps['role'] ?? 'general')) ?></p>
        <?php if (!empty($gaps['present'])): ?>
            <p><strong>Present:</strong> <?= e(implode(', ', array_map('strval', $gaps['present']))) ?></p>
        <?php endif; ?>
        <?php if (!empty($gaps['gaps'])): ?>
            <p><strong>Gaps:</strong> <?= e(implode(', ', array_map('strval', $gaps['gaps']))) ?></p>
        <?php else: ?>
            <p class="muted">No major skill gaps detected for this role profile.</p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Score breakdown</h2>
    <p class="panel__lead">Transparent category weights (overall score max 100).</p>
    <?php if ($intel->breakdown === []): ?>
        <p class="muted">No breakdown available yet.</p>
    <?php else: ?>
        <ul class="intelligence-breakdown" role="list">
            <?php foreach ($intel->breakdown as $category => $row): ?>
                <?php
                $weight = (int) ($row['weight'] ?? 0);
                $earned = (int) ($row['earned'] ?? 0);
                $pct = $weight > 0 ? (int) round(($earned / $weight) * 100) : 0;
                ?>
                <li class="intelligence-breakdown__item">
                    <div class="intelligence-breakdown__meta">
                        <strong><?= e((string) ($row['label'] ?? $category)) ?></strong>
                        <span class="muted"><?= $earned ?> / <?= $weight ?></span>
                    </div>
                    <div class="completeness__bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e((string) ($row['label'] ?? $category)) ?>">
                        <span style="width: <?= $pct ?>%"></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Priority recommendations</h2>
    <p class="panel__lead">Actionable next steps with estimated score impact.</p>
    <?php if ($intel->recommendations === []): ?>
        <p class="muted">No recommendations — keep your resume updated as your career grows.</p>
    <?php else: ?>
        <ul class="record-list intelligence-recs">
            <?php foreach ($intel->recommendations as $rec): ?>
                <li class="record">
                    <div>
                        <div class="publication-card__head">
                            <strong><?= e($rec->title) ?></strong>
                            <span class="badge <?= e($severityClass($rec->severity)) ?>"><?= e(ucfirst($rec->severity)) ?></span>
                            <span class="badge"><?= e($rec->section) ?></span>
                            <span class="badge">+<?= (int) $rec->estimatedImprovement ?> est.</span>
                        </div>
                        <p class="muted"><?= e($rec->message) ?></p>
                        <p class="muted"><code><?= e($rec->code) ?></code></p>
                    </div>
                    <?php if ($rec->actionUrl !== ''): ?>
                        <a class="btn btn--secondary" href="<?= e(app_url($rec->actionUrl)) ?>">Open section</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Score history</h2>
            <p class="panel__lead">Past recalculations (soft-deleted entries are hidden).</p>
        </div>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/intelligence/history/clear')) ?>" onsubmit="return confirm('Clear all score history for this resume?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No history yet. Recalculate to save a snapshot entry.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Overall</th>
                        <th>ATS</th>
                        <th>Employer</th>
                        <th>Keywords</th>
                        <th>Role</th>
                        <th>Rules</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['calculated_at'] ?? '')) ?></td>
                            <td><?= (int) ($row['overall_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['ats_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['employer_readiness_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['keyword_match_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['target_role'] ?? '—')) ?></td>
                            <td><code><?= e((string) ($row['rules_version'] ?? '')) ?></code></td>
                            <td>
                                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/intelligence/history/' . (int) ($row['id'] ?? 0) . '/delete')) ?>" onsubmit="return confirm('Remove this history entry?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--secondary">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

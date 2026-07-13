<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var array<string, mixed> $job
 * @var array{score: int, sections: array} $completion
 * @var \JobVisa\App\Domain\JobMatching\DTO\JobMatchResultDTO $match
 * @var bool $canEdit
 * @var string $disclaimer
 */

$resumeSection = 'recommended-jobs';
$expl = $match->explanation;
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
            <h2 class="panel__title">Job match</h2>
            <p class="panel__lead"><?= e((string) ($job['title'] ?? $match->jobTitle)) ?></p>
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
    <p class="muted">
        <?php if (!empty($job['country_name'])): ?>Location: <?= e((string) $job['country_name']) ?> · <?php endif; ?>
        <?php if (!empty($job['job_type_name'])): ?><?= e((string) $job['job_type_name']) ?> · <?php endif; ?>
        <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/recommended-jobs')) ?>">Back to recommended jobs</a>
    </p>
</section>

<section class="panel intelligence-scores" aria-label="Match scores">
    <div class="intelligence-scores__grid">
        <div class="intelligence-score-card">
            <p class="muted">Overall match</p>
            <p class="intelligence-score-card__value"><?= (int) $match->overallScore ?>%</p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Skills</p>
            <p class="intelligence-score-card__value"><?= (int) $match->skillsScore ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Experience</p>
            <p class="intelligence-score-card__value"><?= (int) $match->experienceScore ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Education</p>
            <p class="intelligence-score-card__value"><?= (int) $match->educationScore ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Languages</p>
            <p class="intelligence-score-card__value"><?= (int) $match->languageScore ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Certifications</p>
            <p class="intelligence-score-card__value"><?= (int) $match->certificationScore ?></p>
        </div>
        <div class="intelligence-score-card">
            <p class="muted">Location</p>
            <p class="intelligence-score-card__value"><?= (int) $match->locationScore ?></p>
        </div>
    </div>
    <p class="muted" style="margin-top:0.75rem">
        Rules <?= e($match->rulesVersion) ?>
        <?php if ($match->calculatedAt !== ''): ?> · Calculated <?= e($match->calculatedAt) ?><?php endif; ?>
    </p>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/jobs/' . (int) $job['id'] . '/match/recalculate')) ?>" class="btn-row" style="margin-top:0.75rem">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--primary">Recalculate match</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Score breakdown</h2>
    <p class="panel__lead">Weighted categories (overall max 100).</p>
    <ul class="intelligence-breakdown" role="list">
        <?php foreach ($match->breakdown as $key => $row): ?>
            <?php
            $weight = (int) ($row['weight'] ?? 0);
            $earned = (int) ($row['earned'] ?? 0);
            $pct = $weight > 0 ? (int) round(($earned / $weight) * 100) : 0;
            ?>
            <li class="intelligence-breakdown__item">
                <div class="intelligence-breakdown__meta">
                    <strong><?= e((string) ($row['label'] ?? $key)) ?></strong>
                    <span class="muted"><?= $earned ?> / <?= $weight ?> (<?= (int) ($row['score'] ?? 0) ?>/100)</span>
                </div>
                <div class="completeness__bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: <?= $pct ?>%"></span>
                </div>
                <p class="muted"><?= e((string) ($row['explain'] ?? '')) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Matched requirements</h2>
    <?php $matched = $expl['matched_requirements'] ?? []; ?>
    <?php if ($matched === []): ?>
        <p class="muted">No explicit requirement signals matched yet.</p>
    <?php else: ?>
        <p><?= e(implode(', ', array_map('strval', $matched))) ?></p>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Missing requirements</h2>
    <p><strong>Required skills:</strong>
        <?php $mr = $expl['missing_required_skills'] ?? []; ?>
        <?= $mr === [] ? 'None detected' : e(implode(', ', array_map('strval', $mr))) ?>
    </p>
    <p><strong>Preferred skills:</strong>
        <?php $mp = $expl['missing_preferred_skills'] ?? []; ?>
        <?= $mp === [] ? 'None detected' : e(implode(', ', array_map('strval', $mp))) ?>
    </p>
    <?php if (!empty($expl['gaps'])): ?>
        <ul>
            <?php foreach ($expl['gaps'] as $gap): ?>
                <li><?= e((string) $gap) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Strengths & reasons</h2>
    <?php if (!empty($expl['strengths'])): ?>
        <p class="muted">Strengths</p>
        <ul>
            <?php foreach ($expl['strengths'] as $s): ?>
                <li><?= e((string) $s) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (!empty($expl['reasons'])): ?>
        <p class="muted">Score reasons</p>
        <ul>
            <?php foreach ($expl['reasons'] as $r): ?>
                <li><?= e((string) $r) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Improvement recommendations</h2>
    <?php if ($match->recommendations === []): ?>
        <p class="muted">No recommendations — this looks like a strong alignment.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($match->recommendations as $rec): ?>
                <li class="record">
                    <div>
                        <div class="publication-card__head">
                            <strong><?= e($rec->title) ?></strong>
                            <span class="badge <?= e($severityClass($rec->severity)) ?>"><?= e(ucfirst($rec->severity)) ?></span>
                            <span class="badge">+<?= (int) $rec->estimatedImprovement ?> est.</span>
                        </div>
                        <p class="muted"><?= e($rec->message) ?></p>
                    </div>
                    <?php if ($rec->actionUrl !== ''): ?>
                        <a class="btn btn--secondary" href="<?= e(app_url($rec->actionUrl)) ?>">Open section</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

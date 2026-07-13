<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\LearningPath\DTO\LearningPathDTO|null $learningPath
 * @var string $defaultCareerGoal
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'learning-path';
$learningPath = $learningPath ?? null;
$defaultCareerGoal = $defaultCareerGoal ?? '';
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$p = $learningPath?->path ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Learning Path Generator</h1>
            <p class="panel__lead">Personalized beginner → advanced roadmap from your skill gaps, salary goals and career signals.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/learning-path/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Generate path</h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/learning-path/' . ($learningPath ? 'recalculate' : 'generate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Career goal</span>
                <input type="text" name="career_goal" maxlength="255" value="<?= e((string) ($learningPath?->careerGoal ?: $defaultCareerGoal)) ?>" placeholder="e.g. Senior Registered Nurse — Dubai">
            </label>
            <button type="submit" class="btn btn--primary"><?= $learningPath ? 'Recalculate path' : 'Generate learning path' ?></button>
        </form>
    <?php endif; ?>
</section>

<?php if ($learningPath !== null): ?>
<section class="panel">
    <h2 class="panel__title">Path snapshot</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Goal</th>
                    <th>Timeline</th>
                    <th>Progress</th>
                    <th>Alignment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= e($learningPath->careerGoal) ?></strong></td>
                    <td><?= (int) $learningPath->timelineWeeks ?> weeks</td>
                    <td><?= (int) $learningPath->progressPercent ?>% (<?= (int) $learningPath->milestonesDone ?>/<?= (int) $learningPath->milestonesTotal ?>)</td>
                    <td><?= (int) $learningPath->alignmentScore ?>/100</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><?= e((string) ($p['summary'] ?? '')) ?></p>
    <p><?= e((string) (($p['career_goal_alignment']['notes'] ?? ''))) ?></p>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/learning-path/paths/' . (int) $learningPath->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Beginner → Intermediate → Advanced</h2>
    <?php foreach (['beginner', 'intermediate', 'advanced'] as $key): ?>
        <?php $level = $p['levels'][$key] ?? null; ?>
        <?php if (!is_array($level)) {
            continue;
        } ?>
        <h3><?= e((string) ($level['title'] ?? $key)) ?></h3>
        <p>Focus: <?= e(implode(', ', $level['focus'] ?? [])) ?></p>
        <ul>
            <?php foreach (($level['outcomes'] ?? []) as $o): ?>
                <li><?= e((string) $o) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Priority learning sequence</h2>
    <ol>
        <?php foreach (($p['priority_sequence'] ?? []) as $row): ?>
            <?php if (!is_array($row)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($row['skill'] ?? '')) ?></strong> — <?= e((string) ($row['why'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="panel">
    <h2 class="panel__title">Weekly schedule</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Week</th><th>Level</th><th>Theme</th><th>Hours</th></tr></thead>
            <tbody>
                <?php foreach (($p['weekly_schedule'] ?? []) as $w): ?>
                    <?php if (!is_array($w)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= (int) ($w['week'] ?? 0) ?></td>
                        <td><?= e((string) ($w['level'] ?? '')) ?></td>
                        <td><?= e((string) ($w['theme'] ?? '')) ?></td>
                        <td><?= (int) ($w['hours'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended courses</h2>
    <ul>
        <?php foreach (($p['courses'] ?? []) as $c): ?>
            <?php if (!is_array($c)) {
                continue;
            } ?>
            <li><?= e((string) ($c['title'] ?? '')) ?> — <?= e((string) ($c['provider'] ?? '')) ?> [<?= e((string) ($c['level'] ?? '')) ?>]</li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended certifications</h2>
    <ul>
        <?php foreach (($p['certifications'] ?? []) as $c): ?>
            <?php if (!is_array($c)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($c['name'] ?? '')) ?></strong> → <?= e((string) ($c['maps_to'] ?? '')) ?> (<?= e((string) ($c['timing'] ?? '')) ?>)</li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended books</h2>
    <ul>
        <?php foreach (($p['books'] ?? []) as $b): ?>
            <?php if (!is_array($b)) {
                continue;
            } ?>
            <li><?= e((string) ($b['title'] ?? '')) ?> — <?= e((string) ($b['focus'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">YouTube resources</h2>
    <ul>
        <?php foreach (($p['youtube'] ?? []) as $y): ?>
            <?php if (!is_array($y)) {
                continue;
            } ?>
            <li><?= e((string) ($y['title'] ?? '')) ?> (<?= e((string) ($y['channel'] ?? '')) ?>)</li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Practice projects</h2>
    <ul>
        <?php foreach (($p['practice_projects'] ?? []) as $pr): ?>
            <?php if (!is_array($pr)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($pr['title'] ?? '')) ?></strong> [<?= e((string) ($pr['level'] ?? '')) ?>] — <?= e((string) ($pr['deliverable'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Portfolio recommendations</h2>
    <ul>
        <?php foreach (($p['portfolio'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Milestones</h2>
    <ul>
        <?php foreach (($p['milestones'] ?? []) as $m): ?>
            <?php if (!is_array($m)) {
                continue;
            } ?>
            <li>
                <?= !empty($m['done']) ? '✓' : '○' ?>
                <?= e((string) ($m['title'] ?? '')) ?>
                <span class="muted">(<?= e((string) ($m['phase'] ?? '')) ?>)</span>
                <?php if ($canEdit && empty($m['done'])): ?>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/learning-path/paths/' . (int) $learningPath->id . '/milestones')) ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="milestone_key" value="<?= e((string) ($m['key'] ?? '')) ?>">
                        <input type="hidden" name="done" value="1">
                        <button type="submit" class="btn btn--secondary">Mark done</button>
                    </form>
                <?php endif; ?>
            </li>
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

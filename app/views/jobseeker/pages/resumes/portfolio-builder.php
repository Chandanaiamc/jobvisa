<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\PortfolioBuilder\DTO\PortfolioPlanDTO|null $portfolioPlan
 * @var string $defaultCareerGoal
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'portfolio-builder';
$portfolioPlan = $portfolioPlan ?? null;
$defaultCareerGoal = $defaultCareerGoal ?? '';
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$p = $portfolioPlan?->plan ?? [];
$eval = is_array($p['recruiter_evaluation'] ?? null) ? $p['recruiter_evaluation'] : [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Portfolio & Project Builder</h1>
            <p class="panel__lead">Generate recruiter-ready portfolio projects from your resume, skill gaps and learning path.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio-builder/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Generate portfolio plan</h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio-builder/' . ($portfolioPlan ? 'recalculate' : 'generate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Career goal / target role</span>
                <input type="text" name="career_goal" maxlength="255" value="<?= e((string) ($portfolioPlan?->careerGoal ?: $defaultCareerGoal)) ?>" placeholder="e.g. Senior Registered Nurse — Dubai">
            </label>
            <button type="submit" class="btn btn--primary"><?= $portfolioPlan ? 'Recalculate plan' : 'Generate portfolio plan' ?></button>
        </form>
    <?php endif; ?>
</section>

<?php if ($portfolioPlan !== null): ?>
<section class="panel">
    <h2 class="panel__title">Portfolio strength</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Strength</th>
                    <th>Recruiter score</th>
                    <th>Projects</th>
                    <th>Goal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= (int) $portfolioPlan->strengthScore ?>/100</strong></td>
                    <td><?= (int) $portfolioPlan->recruiterScore ?>/100 <span class="badge"><?= e((string) ($eval['label'] ?? '')) ?></span></td>
                    <td><?= (int) $portfolioPlan->projectCount ?></td>
                    <td><?= e($portfolioPlan->careerGoal) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><?= e((string) ($p['summary'] ?? '')) ?></p>
    <p><?= e((string) ($eval['advice'] ?? '')) ?></p>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/portfolio-builder/plans/' . (int) $portfolioPlan->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Recruiter evaluation</h2>
    <h3>Strengths</h3>
    <ul>
        <?php foreach (($eval['pros'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3>Gaps</h3>
    <ul>
        <?php foreach (($eval['cons'] ?? []) as $item): ?>
            <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Priority project recommendations</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>P</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Weeks</th>
                    <th>Skills</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($p['projects'] ?? []) as $project): ?>
                    <?php if (!is_array($project)) {
                        continue;
                    } ?>
                    <tr>
                        <td><?= (int) ($project['priority'] ?? 0) ?></td>
                        <td>
                            <strong><?= e((string) ($project['title'] ?? '')) ?></strong>
                            <div class="muted"><?= e((string) ($project['summary'] ?? '')) ?></div>
                            <div class="muted">Repo: <?= e((string) ($project['github_repo_idea'] ?? '')) ?></div>
                        </td>
                        <td><?= e((string) ($project['category'] ?? '')) ?></td>
                        <td><?= e((string) ($project['difficulty'] ?? '')) ?></td>
                        <td><?= (int) ($project['estimated_weeks'] ?? 0) ?></td>
                        <td><?= e(implode(', ', $project['skills_demonstrated'] ?? [])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$categoryBlocks = [
    'github_ideas' => 'GitHub repository ideas',
    'fullstack_ideas' => 'Full-stack project ideas',
    'mobile_ideas' => 'Mobile app project ideas',
    'uiux_ideas' => 'UI/UX project ideas',
    'datascience_ideas' => 'Data science / AI project ideas',
];
?>
<?php foreach ($categoryBlocks as $key => $label): ?>
<section class="panel">
    <h2 class="panel__title"><?= e($label) ?></h2>
    <ul>
        <?php foreach (($p[$key] ?? []) as $project): ?>
            <?php if (!is_array($project)) {
                continue;
            } ?>
            <li><strong><?= e((string) ($project['title'] ?? '')) ?></strong> — <?= e((string) ($project['summary'] ?? '')) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endforeach; ?>

<section class="panel">
    <h2 class="panel__title">Case studies</h2>
    <?php foreach (($p['case_studies'] ?? []) as $c): ?>
        <?php if (!is_array($c)) {
            continue;
        } ?>
        <article style="margin-bottom:1rem">
            <h3><?= e((string) ($c['project'] ?? '')) ?></h3>
            <p><strong>Problem:</strong> <?= e((string) ($c['problem'] ?? '')) ?></p>
            <p><strong>Approach:</strong> <?= e((string) ($c['approach'] ?? '')) ?></p>
            <p><strong>Result:</strong> <?= e((string) ($c['result'] ?? '')) ?></p>
            <p class="muted"><?= e((string) ($c['recruiter_hook'] ?? '')) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2 class="panel__title">STAR achievements</h2>
    <?php foreach (($p['star_achievements'] ?? []) as $s): ?>
        <?php if (!is_array($s)) {
            continue;
        } ?>
        <article style="margin-bottom:1rem">
            <h3><?= e((string) ($s['project'] ?? '')) ?></h3>
            <ul>
                <li><strong>Situation:</strong> <?= e((string) ($s['situation'] ?? '')) ?></li>
                <li><strong>Task:</strong> <?= e((string) ($s['task'] ?? '')) ?></li>
                <li><strong>Action:</strong> <?= e((string) ($s['action'] ?? '')) ?></li>
                <li><strong>Result:</strong> <?= e((string) ($s['result'] ?? '')) ?></li>
            </ul>
        </article>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Resume-ready project descriptions</h2>
    <?php foreach (($p['resume_ready_descriptions'] ?? []) as $r): ?>
        <?php if (!is_array($r)) {
            continue;
        } ?>
        <h3><?= e((string) ($r['title'] ?? '')) ?></h3>
        <ul>
            <?php foreach (($r['bullets'] ?? []) as $b): ?>
                <li><?= e((string) $b) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
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

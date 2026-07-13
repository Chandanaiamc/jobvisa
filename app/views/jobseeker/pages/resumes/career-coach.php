<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var array{score: int, sections: array} $completion
 * @var \JobVisa\App\Domain\CareerCoach\DTO\CareerCoachSessionDTO $coach
 * @var list<array<string, mixed>> $history
 * @var list<array<string, mixed>> $deletedHistory
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'career-coach';
$history = $history ?? [];
$deletedHistory = $deletedHistory ?? [];
$scores = $coach->contextScores;
$summary = $coach->summary;
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Career Coach</h1>
            <p class="panel__lead"><?= e($coach->headline) ?></p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/intelligence')) ?>">Intelligence</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Coaching snapshot</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Resume</th>
                    <th>ATS</th>
                    <th>Employer ready</th>
                    <th>Keyword</th>
                    <th>Top match</th>
                    <th>Readiness</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= (int) ($scores['resume_overall'] ?? 0) ?></strong></td>
                    <td><?= (int) ($scores['ats_score'] ?? 0) ?></td>
                    <td><?= (int) ($scores['employer_readiness'] ?? 0) ?></td>
                    <td><?= (int) ($scores['keyword_match'] ?? 0) ?></td>
                    <td><?= (int) ($scores['top_match'] ?? 0) ?></td>
                    <td><span class="badge"><?= e((string) ($summary['readiness_label'] ?? '')) ?></span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <ul>
        <li><?= e((string) ($summary['focus'] ?? '')) ?></li>
        <li><?= e((string) ($summary['job_signal'] ?? '')) ?></li>
        <li><?= e((string) ($summary['next_step'] ?? '')) ?></li>
    </ul>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/career-coach/recalculate')) ?>" class="recruiter-search" style="margin-top:1rem">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Target role (optional)</span>
                <input type="text" name="target_role" maxlength="191" value="<?= e((string) ($coach->targetRole ?? '')) ?>" placeholder="e.g. Registered Nurse — Dubai">
            </label>
            <button type="submit" class="btn btn--primary">Refresh coaching</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Skill-gap analysis</h2>
    <?php if ($coach->skillGaps === []): ?>
        <p class="muted">No skill gaps identified yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($coach->skillGaps as $gap): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($gap['skill'] ?? '')) ?></strong>
                        <span class="badge"><?= e((string) ($gap['priority'] ?? '')) ?></span>
                        <p class="muted"><?= e((string) ($gap['reason'] ?? '')) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Next-best roles</h2>
    <?php if ($coach->nextRoles === []): ?>
        <p class="muted">No role recommendations yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Fit</th>
                        <th>Confidence</th>
                        <th>Why</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coach->nextRoles as $role): ?>
                        <tr>
                            <td><?= e((string) ($role['title'] ?? '')) ?></td>
                            <td><?= e((string) ($role['fit'] ?? '')) ?></td>
                            <td><?= (int) ($role['confidence'] ?? 0) ?></td>
                            <td class="muted"><?= e((string) ($role['why'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Learning roadmap</h2>
    <ol class="record-list">
        <?php foreach ($coach->learningRoadmap as $step): ?>
            <li class="record">
                <div>
                    <strong>#<?= (int) ($step['priority'] ?? 0) ?> · <?= e((string) ($step['horizon'] ?? '')) ?></strong>
                    <p><?= e((string) ($step['action'] ?? '')) ?></p>
                    <p class="muted"><?= e((string) ($step['outcome'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="panel">
    <h2 class="panel__title">Certification recommendations</h2>
    <ul class="record-list">
        <?php foreach ($coach->certificationRecs as $rec): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($rec['name'] ?? '')) ?></strong>
                    <span class="badge"><?= e((string) ($rec['priority'] ?? '')) ?></span>
                    <p class="muted"><?= e((string) ($rec['why'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Portfolio improvements</h2>
    <ul class="record-list">
        <?php foreach ($coach->portfolioRecs as $rec): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($rec['action'] ?? '')) ?></strong>
                    <span class="badge"><?= e((string) ($rec['priority'] ?? '')) ?></span>
                    <p class="muted"><?= e((string) ($rec['why'] ?? '')) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Suitable job opportunities</h2>
    <?php if ($coach->jobOpportunities === []): ?>
        <p class="muted">No job-match snapshots yet. Visit <a href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/recommended-jobs')) ?>">Job matches</a> first.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Country</th>
                        <th>Match</th>
                        <th>Skills</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coach->jobOpportunities as $job): ?>
                        <tr>
                            <td><?= e((string) ($job['title'] ?? '')) ?></td>
                            <td><?= e((string) ($job['country'] ?? '—')) ?></td>
                            <td><strong><?= (int) ($job['match_score'] ?? 0) ?></strong></td>
                            <td><?= (int) ($job['skills_score'] ?? 0) ?></td>
                            <td>
                                <?php if ((int) ($job['job_id'] ?? 0) > 0): ?>
                                    <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/jobs/' . (int) $job['job_id'] . '/match')) ?>">Match</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($canEdit): ?>
<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">Recommendation history</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/career-coach/history/clear')) ?>" onsubmit="return confirm('Clear all coaching history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No coaching history yet. Refresh coaching to save a snapshot.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($history as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($item['headline'] ?? 'Coaching session')) ?></strong>
                        <p class="muted">
                            <?= e((string) ($item['created_at'] ?? '')) ?>
                            · <?= e((string) ($item['coach_version'] ?? '')) ?>
                            <?php if (!empty($item['target_role'])): ?>
                                · <?= e((string) $item['target_role']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/career-coach/history/' . (int) ($item['id'] ?? 0) . '/delete')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($deletedHistory !== []): ?>
<section class="panel">
    <h2 class="panel__title">Recently removed</h2>
    <ul class="record-list">
        <?php foreach ($deletedHistory as $item): ?>
            <li class="record">
                <div>
                    <strong><?= e((string) ($item['headline'] ?? 'Coaching session')) ?></strong>
                    <p class="muted">Removed <?= e((string) ($item['deleted_at'] ?? '')) ?></p>
                </div>
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/career-coach/history/' . (int) ($item['id'] ?? 0) . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--primary">Restore</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php endif; ?>

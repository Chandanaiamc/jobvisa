<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var array{score: int, sections: array} $completion
 * @var list<array<string, mixed>> $recommendations
 * @var bool $canEdit
 * @var string $disclaimer
 */

$resumeSection = 'recommended-jobs';
$recommendations = $recommendations ?? [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Recommended jobs</h2>
            <p class="panel__lead">Published vacancies ranked by deterministic match score for this resume.</p>
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

<section class="panel">
    <?php if ($recommendations === []): ?>
        <p class="muted">No published jobs available to match yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Overall</th>
                        <th>Skills</th>
                        <th>Experience</th>
                        <th>Location</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recommendations as $row): ?>
                        <?php
                        $jobId = (int) ($row['job_id'] ?? 0);
                        $title = (string) ($row['job_title'] ?? 'Job #' . $jobId);
                        ?>
                        <tr>
                            <td><?= e($title) ?></td>
                            <td><strong><?= (int) ($row['overall_score'] ?? 0) ?>%</strong></td>
                            <td><?= (int) ($row['skills_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['experience_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['country_name'] ?? '—')) ?></td>
                            <td>
                                <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/jobs/' . $jobId . '/match')) ?>">View match</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

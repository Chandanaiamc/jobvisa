<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $job
 * @var list<array<string, mixed>> $history
 * @var bool $canManage
 */

$jobId = (int) ($job['id'] ?? 0);
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">Ranking history</h1>
            <p class="panel__lead"><?= e((string) ($job['title'] ?? '')) ?></p>
        </div>
        <div class="btn-row">
            <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking')) ?>">Back to ranking</a>
            <?php if ($canManage && $history !== []): ?>
                <form method="post" action="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking/history/clear')) ?>" onsubmit="return confirm('Clear all ranking history for this job?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--secondary">Clear history</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="panel">
    <?php if ($history === []): ?>
        <p class="muted">No ranking history yet. Recalculate rankings to create history entries.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Overall</th>
                        <th>Match</th>
                        <th>Resume</th>
                        <th>Rules</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['calculated_at'] ?? '')) ?></td>
                            <td><?= (int) ($row['rank_position'] ?? 0) ?></td>
                            <td><?= e((string) ($row['applicant_name'] ?? 'Applicant')) ?></td>
                            <td><?= (int) ($row['overall_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['job_match_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['resume_score'] ?? 0) ?></td>
                            <td><code><?= e((string) ($row['rules_version'] ?? '')) ?></code></td>
                            <td>
                                <?php if ($canManage): ?>
                                    <form method="post" action="<?= e(app_url('/employer/jobs/' . $jobId . '/applicants/ranking/history/' . (int) ($row['id'] ?? 0) . '/delete')) ?>" onsubmit="return confirm('Remove this history entry?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn--secondary">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

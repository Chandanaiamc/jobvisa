<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $jobs
 * @var array<string, mixed>|null $selectedJob
 * @var list<array<string, mixed>> $candidates
 * @var list<array<string, mixed>> $history
 * @var string $version
 * @var string $disclaimer
 */

$jobs = $jobs ?? [];
$selectedJob = $selectedJob ?? null;
$candidates = $candidates ?? [];
$history = $history ?? [];
$selectedJobId = $selectedJob !== null ? (int) ($selectedJob['id'] ?? 0) : 0;
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Interview Assistant</h1>
            <p class="panel__lead">Prepare technical and behavioral interviews from resume, AI scores, and job requirements.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/employer')) ?>">AI Dashboard</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Select a job</h2>
    <?php if ($jobs === []): ?>
        <p class="muted">No jobs found for your employer account.</p>
    <?php else: ?>
        <form method="get" action="<?= e(app_url('/employer/interview-assistant')) ?>" class="recruiter-search">
            <label class="field">
                <span class="muted">Job</span>
                <select name="job" required onchange="this.form.submit()">
                    <option value="">Choose job…</option>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?= (int) ($job['id'] ?? 0) ?>" <?= $selectedJobId === (int) ($job['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= e((string) ($job['title'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <noscript><button type="submit" class="btn btn--primary">Load applicants</button></noscript>
        </form>
    <?php endif; ?>
</section>

<?php if ($selectedJob !== null): ?>
<section class="panel">
    <h2 class="panel__title">Applicants — <?= e((string) ($selectedJob['title'] ?? '')) ?></h2>
    <?php if ($candidates === []): ?>
        <p class="muted">No applications for this job yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Resume</th>
                        <th>Ranking</th>
                        <th>AI match</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $row): ?>
                        <tr>
                            <td>
                                <?= e((string) ($row['candidate_name'] ?? '')) ?>
                                <div class="muted"><?= e((string) ($row['candidate_email'] ?? '')) ?></div>
                            </td>
                            <td><?= e((string) ($row['resume_title'] ?? '—')) ?></td>
                            <td>
                                <?php if (isset($row['ranking_score']) && $row['ranking_score'] !== null): ?>
                                    <strong><?= (int) $row['ranking_score'] ?></strong>
                                    <?php if (isset($row['rank_position'])): ?>
                                        <span class="muted">#<?= (int) $row['rank_position'] ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= isset($row['match_score']) && $row['match_score'] !== null ? (int) $row['match_score'] : '—' ?></td>
                            <td><span class="badge"><?= e((string) ($row['application_status'] ?? '')) ?></span></td>
                            <td>
                                <?php if (!empty($row['resume_id'])): ?>
                                    <form method="post" action="<?= e(app_url('/employer/interview-assistant/generate')) ?>" style="display:inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="application_id" value="<?= (int) ($row['application_id'] ?? 0) ?>">
                                        <input type="hidden" name="job_id" value="<?= (int) ($row['job_id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn--primary">Prepare interview</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">No resume</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">Interview history</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/employer/interview-assistant/history/clear')) ?>" onsubmit="return confirm('Clear all interview history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No interview sessions yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($history as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($item['candidate_name'] ?? '')) ?></strong>
                        <p class="muted">
                            <?= e((string) ($item['job_title'] ?? '')) ?>
                            · <?= e((string) ($item['status'] ?? '')) ?>
                            · <?= e((string) ($item['created_at'] ?? '')) ?>
                            <?php if (isset($item['scorecard_overall']) && $item['scorecard_overall'] !== null): ?>
                                · Score <?= (int) $item['scorecard_overall'] ?>
                                (<?= e((string) ($item['hiring_recommendation'] ?? '')) ?>)
                            <?php endif; ?>
                        </p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a class="btn btn--secondary" href="<?= e(app_url('/employer/interview-assistant/sessions/' . (int) ($item['id'] ?? 0))) ?>">Open</a>
                        <form method="post" action="<?= e(app_url('/employer/interview-assistant/sessions/' . (int) ($item['id'] ?? 0) . '/delete')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--secondary">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $jobs
 * @var \JobVisa\App\Domain\RecruiterAssistant\DTO\RecruiterSearchCriteria|null $criteria
 * @var list<array<string, mixed>> $results
 * @var list<array<string, mixed>> $suggestions
 * @var list<array<string, mixed>> $history
 * @var string $version
 * @var string $disclaimer
 * @var string $query
 */

$results = $results ?? [];
$suggestions = $suggestions ?? [];
$history = $history ?? [];
$query = (string) ($query ?? '');
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Recruiter Assistant</h1>
            <p class="panel__lead">Search applicants with natural language across your jobs.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/employer')) ?>">AI Dashboard</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title">Natural language search</h2>
    <form method="post" action="<?= e(app_url('/employer/recruiter-assistant/search')) ?>" class="recruiter-search">
        <?= csrf_field() ?>
        <label class="field" style="width:100%">
            <span class="muted">Ask for candidates</span>
            <textarea name="q" rows="3" maxlength="500" required placeholder="e.g. nurses with 3 years experience in Dubai match above 50"><?= e($query) ?></textarea>
        </label>
        <button type="submit" class="btn btn--primary">Search candidates</button>
    </form>
    <?php if ($criteria !== null): ?>
        <div class="interpreted-filters" style="margin-top:1rem">
            <p class="muted">Interpreted filters</p>
            <ul>
                <?php foreach ($criteria->interpreted as $line): ?>
                    <li><?= e((string) $line) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Recruiter suggestions</h2>
    <?php if ($suggestions === []): ?>
        <p class="muted">No suggestions yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($suggestions as $s): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) ($s['title'] ?? '')) ?></strong>
                        <p class="muted"><?= e((string) ($s['reason'] ?? '')) ?></p>
                        <p class="muted"><code><?= e((string) ($s['query'] ?? '')) ?></code></p>
                    </div>
                    <a class="btn btn--secondary" href="<?= e(app_url('/employer/recruiter-assistant?q=' . rawurlencode((string) ($s['query'] ?? '')))) ?>">Run</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel">
    <h2 class="panel__title">Recommended top candidates</h2>
    <?php if ($results === []): ?>
        <p class="muted">No candidates matched. Try a broader query or refresh rankings from a job page.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Job</th>
                        <th>Location</th>
                        <th>Ranking</th>
                        <th>AI match</th>
                        <th>Resume</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <?= e((string) ($row['applicant_name'] ?? '')) ?>
                                <div class="muted"><?= e((string) ($row['applicant_email'] ?? '')) ?></div>
                            </td>
                            <td><?= e((string) ($row['job_title'] ?? '')) ?></td>
                            <td><?= e((string) ($row['job_country'] ?? '—')) ?></td>
                            <td><strong><?= (int) ($row['ranking_score'] ?? 0) ?></strong></td>
                            <td><?= (int) ($row['match_score'] ?? 0) ?></td>
                            <td><?= (int) ($row['resume_score'] ?? 0) ?></td>
                            <td><?= isset($row['years_of_experience']) && $row['years_of_experience'] !== null ? (int) $row['years_of_experience'] . 'y' : '—' ?></td>
                            <td><span class="badge"><?= e((string) ($row['application_status'] ?? '')) ?></span></td>
                            <td>
                                <a class="btn btn--secondary" href="<?= e(app_url('/employer/jobs/' . (int) ($row['job_id'] ?? 0) . '/applicants/ranking')) ?>">Ranking</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-head">
        <h2 class="panel__title">Search history</h2>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(app_url('/employer/recruiter-assistant/history/clear')) ?>" onsubmit="return confirm('Clear all search history?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--secondary">Clear history</button>
            </form>
        <?php endif; ?>
    </div>
    <?php if ($history === []): ?>
        <p class="muted">No saved searches yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Query</th>
                        <th>Results</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?= e((string) ($h['created_at'] ?? '')) ?></td>
                            <td><?= e((string) ($h['query_text'] ?? '')) ?></td>
                            <td><?= (int) ($h['result_count'] ?? 0) ?></td>
                            <td class="btn-row">
                                <a class="btn btn--secondary" href="<?= e(app_url('/employer/recruiter-assistant?q=' . rawurlencode((string) ($h['query_text'] ?? '')))) ?>">Re-run</a>
                                <form method="post" action="<?= e(app_url('/employer/recruiter-assistant/history/' . (int) ($h['id'] ?? 0) . '/delete')) ?>" onsubmit="return confirm('Remove this search?');">
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

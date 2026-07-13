<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\MockInterview\DTO\MockInterviewSessionDTO|null $mockSession
 * @var list<array<string, mixed>> $matchedJobs
 * @var int|null $selectedJobId
 * @var list<array<string, mixed>> $history
 * @var bool $canEdit
 * @var string $version
 * @var string $disclaimer
 */

$resumeSection = 'mock-interview';
$mockSession = $mockSession ?? null;
$matchedJobs = $matchedJobs ?? [];
$selectedJobId = $selectedJobId ?? null;
$history = $history ?? [];
$canEdit = $canEdit ?? false;
$s = $mockSession?->session ?? [];
$questions = is_array($s['questions'] ?? null) ? $s['questions'] : [];
$answers = is_array($s['answers'] ?? null) ? $s['answers'] : [];
$analysis = is_array($s['analysis'] ?? null) ? $s['analysis'] : [];
$report = is_array($s['report'] ?? null) ? $s['report'] : [];
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h1 class="panel__title">AI Mock Interview Simulator</h1>
            <p class="panel__lead">Practice HR, technical, behavioral and scenario questions tailored to your resume and target job.</p>
        </div>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/mock-interview/history')) ?>">History</a>
    </div>
    <p class="muted"><?= e($disclaimer) ?> · Rules <?= e($version) ?></p>
</section>

<section class="panel">
    <h2 class="panel__title"><?= $mockSession ? 'Recalculate interview' : 'Generate mock interview' ?></h2>
    <?php if ($canEdit): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/mock-interview/' . ($mockSession ? 'recalculate' : 'generate'))) ?>" class="recruiter-search">
            <?= csrf_field() ?>
            <label class="field">
                <span class="muted">Target job</span>
                <select name="job_id" required>
                    <?php foreach ($matchedJobs as $job): ?>
                        <?php
                        $jid = (int) ($job['job_id'] ?? $job['id'] ?? 0);
                        if ($jid < 1) {
                            continue;
                        }
                        ?>
                        <option value="<?= $jid ?>" <?= (int) $selectedJobId === $jid ? 'selected' : '' ?>>
                            <?= e((string) ($job['job_title'] ?? $job['title'] ?? 'Job #' . $jid)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn--primary"><?= $mockSession ? 'Recalculate questions' : 'Generate mock interview' ?></button>
        </form>
    <?php endif; ?>
</section>

<?php if ($mockSession !== null): ?>
<section class="panel">
    <h2 class="panel__title">Session overview</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th>Overall</th>
                    <th>Communication</th>
                    <th>Technical</th>
                    <th>Confidence</th>
                    <th>STAR</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= e($mockSession->jobTitle) ?></strong></td>
                    <td><?= e($mockSession->careerLevel) ?></td>
                    <td><span class="badge"><?= e($mockSession->status) ?></span></td>
                    <td><strong><?= (int) $mockSession->overallScore ?>/100</strong></td>
                    <td><?= (int) $mockSession->communicationScore ?></td>
                    <td><?= (int) $mockSession->technicalScore ?></td>
                    <td><?= (int) $mockSession->confidenceScore ?></td>
                    <td><?= (int) $mockSession->starScore ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php if (($analysis['summary'] ?? '') !== '' || ($report['summary'] ?? '') !== ''): ?>
        <p><?= e((string) ($analysis['summary'] ?? $report['summary'] ?? '')) ?></p>
        <?php if (($analysis['readiness_label'] ?? '') !== ''): ?>
            <p><span class="badge"><?= e((string) $analysis['readiness_label']) ?></span></p>
        <?php endif; ?>
    <?php endif; ?>
    <p>
        <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/mock-interview/sessions/' . (int) $mockSession->id . '/export/pdf')) ?>">Export PDF</a>
    </p>
</section>

<section class="panel">
    <h2 class="panel__title">Answer questions</h2>
    <?php if ($canEdit && $questions !== []): ?>
        <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/mock-interview/analyze')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= (int) $mockSession->id ?>">
            <?php foreach ($questions as $q): ?>
                <?php if (!is_array($q)) {
                    continue;
                }
                $qid = (string) ($q['id'] ?? '');
                if ($qid === '') {
                    continue;
                }
                ?>
                <label class="field" style="display:block;margin-bottom:1rem">
                    <span class="muted">[<?= e(strtoupper((string) ($q['type'] ?? ''))) ?> · <?= e((string) ($q['difficulty'] ?? '')) ?>] <?= e((string) ($q['focus'] ?? '')) ?></span>
                    <div><strong><?= e((string) ($q['prompt'] ?? '')) ?></strong></div>
                    <textarea name="answers[<?= e($qid) ?>]" rows="4" maxlength="5000" placeholder="Use STAR where relevant: Situation, Task, Action, Result"><?= e((string) ($answers[$qid] ?? '')) ?></textarea>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn--primary">Analyze answers</button>
        </form>
    <?php else: ?>
        <p class="muted">Generate a session to see interview questions.</p>
    <?php endif; ?>
</section>

<?php if ($analysis !== []): ?>
<section class="panel">
    <h2 class="panel__title">Improvement suggestions</h2>
    <ul>
        <?php foreach (($analysis['improvements'] ?? []) as $tip): ?>
            <li><?= e((string) $tip) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Follow-up questions</h2>
    <ul>
        <?php foreach (($analysis['follow_up_questions'] ?? []) as $fq): ?>
            <li><?= e((string) $fq) ?></li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="panel">
    <h2 class="panel__title">Interview report</h2>
    <?php if (($report['context_notes'] ?? []) !== []): ?>
        <h3>Context notes</h3>
        <ul>
            <?php foreach ($report['context_notes'] as $note): ?>
                <li><?= e((string) $note) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (($analysis['per_question'] ?? []) !== []): ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Comm</th>
                        <th>Tech</th>
                        <th>Conf</th>
                        <th>STAR</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analysis['per_question'] as $pq): ?>
                        <?php if (!is_array($pq)) {
                            continue;
                        } ?>
                        <tr>
                            <td><?= e((string) ($pq['focus'] ?? $pq['question_id'] ?? '')) ?></td>
                            <td><?= (int) ($pq['communication'] ?? 0) ?></td>
                            <td><?= (int) ($pq['technical'] ?? 0) ?></td>
                            <td><?= (int) ($pq['confidence'] ?? 0) ?></td>
                            <td><?= (int) ($pq['star'] ?? 0) ?></td>
                            <td><?= e((string) ($pq['star_feedback'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php endif; ?>

<section class="panel">
    <h2 class="panel__title">Recent history</h2>
    <?php if ($history === []): ?>
        <p class="muted">No mock interview history yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Headline</th><th>Action</th><th>Score</th><th>When</th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($history, 0, 8) as $row): ?>
                        <tr>
                            <td><?= e((string) ($row['headline'] ?? '')) ?></td>
                            <td><?= e((string) ($row['action'] ?? '')) ?></td>
                            <td><?= (int) ($row['overall_score'] ?? 0) ?></td>
                            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeProfessionalDTO $professional
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 * @var string $autosaveUrl
 */

$resumeSection = 'professional';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
$readonly = !$canEdit;
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel" id="professional-panel"
         data-autosave-url="<?= e($autosaveUrl ?? '') ?>"
         data-can-edit="<?= $canEdit ? '1' : '0' ?>">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Headline &amp; professional summary</h2>
            <p class="panel__lead">Describe your career focus. Changes autosave while you type when editing is enabled.</p>
        </div>
        <div class="completeness" style="min-width:200px">
            <div class="completeness__meta">
                <span>Resume completion</span>
                <strong id="resume-completion-score"><?= (int) $completion['score'] ?>%</strong>
            </div>
            <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $completion['score'] ?>" aria-valuemin="0" aria-valuemax="100">
                <span id="resume-completion-bar" style="width: <?= (int) $completion['score'] ?>%"></span>
            </div>
            <p class="muted" id="autosave-status" style="margin:0.4rem 0 0;font-size:0.85rem" aria-live="polite"></p>
        </div>
    </div>

    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/professional')) ?>"
          class="form-grid" id="professional-form" <?= $readonly ? 'onsubmit="return false;"' : '' ?>>
        <?= csrf_field() ?>

        <div class="form-field form-field--full">
            <label for="headline">Professional headline</label>
            <input id="headline" name="headline" required maxlength="255"
                   value="<?= e((string) ($old['headline'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'headline') ?>
        </div>

        <div class="form-field form-field--full">
            <label for="summary">Professional summary</label>
            <textarea id="summary" name="summary" rows="8" required <?= $readonly ? 'readonly' : '' ?>><?= e((string) ($old['summary'] ?? '')) ?></textarea>
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">At least 40 characters. Use paragraphs to highlight strengths.</p>
            <?= $fieldError($errors, 'summary') ?>
        </div>

        <div class="form-field form-field--full">
            <label for="career_objective">Career objective</label>
            <textarea id="career_objective" name="career_objective" rows="4" <?= $readonly ? 'readonly' : '' ?>><?= e((string) ($old['career_objective'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'career_objective') ?>
        </div>

        <div class="form-field">
            <label for="years_of_experience">Years of experience</label>
            <input id="years_of_experience" type="number" min="0" max="60" step="0.5" name="years_of_experience"
                   value="<?= e((string) ($old['years_of_experience'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'years_of_experience') ?>
        </div>
        <div class="form-field">
            <label for="employment_status">Employment status</label>
            <select id="employment_status" name="employment_status" required <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach ([
                    'employed' => 'Employed',
                    'unemployed' => 'Unemployed',
                    'freelance' => 'Freelance',
                    'contract' => 'Contract',
                    'student' => 'Student',
                    'retired' => 'Retired',
                ] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['employment_status'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'employment_status') ?>
        </div>

        <div class="form-field">
            <label for="current_job_title">Current job title</label>
            <input id="current_job_title" name="current_job_title" maxlength="150"
                   value="<?= e((string) ($old['current_job_title'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'current_job_title') ?>
        </div>
        <div class="form-field">
            <label for="current_company">Current company</label>
            <input id="current_company" name="current_company" maxlength="200"
                   value="<?= e((string) ($old['current_company'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'current_company') ?>
        </div>
        <div class="form-field">
            <label for="industry">Industry</label>
            <input id="industry" name="industry" maxlength="150"
                   value="<?= e((string) ($old['industry'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'industry') ?>
        </div>
        <div class="form-field">
            <label for="notice_period">Notice period</label>
            <select id="notice_period" name="notice_period" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach ([
                    'immediate' => 'Immediate',
                    '1_week' => '1 week',
                    '2_weeks' => '2 weeks',
                    '1_month' => '1 month',
                    '2_months' => '2 months',
                    '3_months' => '3 months',
                    'negotiable' => 'Negotiable',
                ] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['notice_period'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'notice_period') ?>
        </div>

        <div class="form-field">
            <label for="current_salary">Current salary (optional)</label>
            <input id="current_salary" type="number" min="0" step="0.01" name="current_salary"
                   value="<?= e((string) ($old['current_salary'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'current_salary') ?>
        </div>
        <div class="form-field">
            <label for="expected_salary">Expected salary</label>
            <input id="expected_salary" type="number" min="0" step="0.01" name="expected_salary" required
                   value="<?= e((string) ($old['expected_salary'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'expected_salary') ?>
        </div>
        <div class="form-field">
            <label for="preferred_currency">Preferred currency</label>
            <select id="preferred_currency" name="preferred_currency" <?= $readonly ? 'disabled' : '' ?>>
                <?php foreach (['LKR', 'USD', 'AED', 'QAR', 'SAR', 'EUR', 'GBP'] as $cur): ?>
                    <option value="<?= e($cur) ?>" <?= (($old['preferred_currency'] ?? 'LKR') === $cur) ? 'selected' : '' ?>><?= e($cur) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'preferred_currency') ?>
        </div>

        <div class="form-field form-field--full choice-row">
            <label><input type="checkbox" name="open_to_relocate" value="1" <?= !empty($old['open_to_relocate']) ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>> Open to relocate</label>
            <label><input type="checkbox" name="open_to_remote" value="1" <?= !empty($old['open_to_remote']) ? 'checked' : '' ?> <?= $readonly ? 'disabled' : '' ?>> Open to remote work</label>
        </div>

        <?php if ($canEdit): ?>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--primary">Save professional summary</button>
            </div>
        <?php endif; ?>
    </form>
</section>

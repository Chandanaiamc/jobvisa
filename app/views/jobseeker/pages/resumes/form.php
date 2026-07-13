<?php

declare(strict_types=1);

/**
 * @var string $mode
 * @var \JobVisa\App\Domain\Resume\DTO\ResumeData|null $resume
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 */

$action = $mode === 'edit' && $resume !== null
    ? app_url('/jobseeker/resumes/' . (int) $resume->id)
    : app_url('/jobseeker/resumes');
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="panel">
    <h2 class="panel__title"><?= $mode === 'edit' ? 'Edit resume' : 'Create resume' ?></h2>
    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-field form-field--full">
            <label for="title">Title</label>
            <input id="title" name="title" required maxlength="150" value="<?= e((string) ($old['title'] ?? '')) ?>">
            <?= $fieldError($errors, 'title') ?>
        </div>
        <div class="form-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['draft' => 'Draft', 'published' => 'Published'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['status'] ?? 'draft') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'status') ?>
        </div>
        <div class="form-field">
            <label for="visibility">Visibility</label>
            <select id="visibility" name="visibility">
                <?php foreach (['employers' => 'Employers', 'public' => 'Public', 'private' => 'Private'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['visibility'] ?? 'employers') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'visibility') ?>
        </div>
        <?php if ($mode === 'create'): ?>
            <div class="form-field form-field--full">
                <label><input type="checkbox" name="is_default" value="1"> Make default resume</label>
            </div>
        <?php endif; ?>
        <div class="form-actions form-field--full">
            <button class="btn btn--primary" type="submit"><?= $mode === 'edit' ? 'Save changes' : 'Create resume' ?></button>
            <a class="btn btn--secondary" href="<?= e(app_url('/jobseeker/resumes')) ?>">Cancel</a>
        </div>
    </form>
</section>

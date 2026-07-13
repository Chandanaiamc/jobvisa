<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $items */
/** @var array<string, list<string>> $errors */
$old = $old ?? [];
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="panel">
    <h2 class="panel__title">Add education</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/education')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-field"><label for="school">School</label><input id="school" name="school" value="<?= e((string) ($old['school'] ?? '')) ?>"></div>
        <div class="form-field"><label for="institution">Institute</label><input id="institution" name="institution" required value="<?= e((string) ($old['institution'] ?? '')) ?>"><?= $fieldError($errors, 'institution') ?></div>
        <div class="form-field"><label for="degree">Degree</label><input id="degree" name="degree" required value="<?= e((string) ($old['degree'] ?? '')) ?>"><?= $fieldError($errors, 'degree') ?></div>
        <div class="form-field"><label for="field_of_study">Field</label><input id="field_of_study" name="field_of_study" value="<?= e((string) ($old['field_of_study'] ?? '')) ?>"></div>
        <div class="form-field"><label for="start_date">Start</label><input id="start_date" type="date" name="start_date" value="<?= e((string) ($old['start_date'] ?? '')) ?>"></div>
        <div class="form-field"><label for="end_date">End</label><input id="end_date" type="date" name="end_date" value="<?= e((string) ($old['end_date'] ?? '')) ?>"></div>
        <div class="form-field"><label for="grade">Grade</label><input id="grade" name="grade" value="<?= e((string) ($old['grade'] ?? '')) ?>"></div>
        <div class="form-field form-field--full"><label for="description">Description</label><textarea id="description" name="description" rows="3"><?= e((string) ($old['description'] ?? '')) ?></textarea></div>
        <div class="form-field form-field--full"><label><input type="checkbox" name="is_current" value="1"> Currently studying</label></div>
        <div class="form-actions form-field--full"><button class="btn btn--primary" type="submit">Add education</button></div>
    </form>
</section>

<section class="panel">
    <h2 class="panel__title">Your education</h2>
    <?php if ($items === []): ?>
        <p class="muted">No education records yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) $item['degree']) ?></strong>
                        <p><?= e((string) ($item['institution'] ?? '')) ?><?= !empty($item['school']) ? ' · ' . e((string) $item['school']) : '' ?></p>
                        <p class="muted"><?= e((string) ($item['field_of_study'] ?? '')) ?> <?= !empty($item['grade']) ? '· Grade ' . e((string) $item['grade']) : '' ?></p>
                        <p class="muted"><?= e((string) ($item['start_date'] ?? '')) ?> – <?= !empty($item['is_current']) ? 'Present' : e((string) ($item['end_date'] ?? '')) ?></p>
                    </div>
                    <details>
                        <summary>Edit</summary>
                        <form method="post" action="<?= e(app_url('/jobseeker/education/' . (int) $item['id'])) ?>" class="form-grid">
                            <?= csrf_field() ?>
                            <div class="form-field"><label>School</label><input name="school" value="<?= e((string) ($item['school'] ?? '')) ?>"></div>
                            <div class="form-field"><label>Institute</label><input name="institution" required value="<?= e((string) $item['institution']) ?>"></div>
                            <div class="form-field"><label>Degree</label><input name="degree" required value="<?= e((string) $item['degree']) ?>"></div>
                            <div class="form-field"><label>Field</label><input name="field_of_study" value="<?= e((string) ($item['field_of_study'] ?? '')) ?>"></div>
                            <div class="form-field"><label>Start</label><input type="date" name="start_date" value="<?= e((string) ($item['start_date'] ?? '')) ?>"></div>
                            <div class="form-field"><label>End</label><input type="date" name="end_date" value="<?= e((string) ($item['end_date'] ?? '')) ?>"></div>
                            <div class="form-field"><label>Grade</label><input name="grade" value="<?= e((string) ($item['grade'] ?? '')) ?>"></div>
                            <div class="form-field form-field--full"><label>Description</label><textarea name="description" rows="3"><?= e((string) ($item['description'] ?? '')) ?></textarea></div>
                            <div class="form-field form-field--full"><label><input type="checkbox" name="is_current" value="1" <?= !empty($item['is_current']) ? 'checked' : '' ?>> Currently studying</label></div>
                            <div class="form-actions form-field--full"><button class="btn btn--secondary" type="submit">Save</button></div>
                        </form>
                        <form method="post" action="<?= e(app_url('/jobseeker/education/' . (int) $item['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this record?');">
                            <?= csrf_field() ?>
                            <button class="btn btn--danger" type="submit">Delete</button>
                        </form>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $countries */
/** @var array<string, list<string>> $errors */
$old = $old ?? [];
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="panel">
    <h2 class="panel__title">Add work experience</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/experience')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-field"><label for="company_name">Company</label><input id="company_name" name="company_name" required value="<?= e((string) ($old['company_name'] ?? '')) ?>"><?= $fieldError($errors, 'company_name') ?></div>
        <div class="form-field"><label for="job_title">Position</label><input id="job_title" name="job_title" required value="<?= e((string) ($old['job_title'] ?? '')) ?>"><?= $fieldError($errors, 'job_title') ?></div>
        <div class="form-field">
            <label for="country_id">Country</label>
            <select id="country_id" name="country_id">
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>"><?= e((string) $country['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field"><label for="start_date">Start</label><input id="start_date" type="date" name="start_date"></div>
        <div class="form-field"><label for="end_date">End</label><input id="end_date" type="date" name="end_date"></div>
        <div class="form-field form-field--full"><label><input type="checkbox" name="is_current" value="1"> Currently working</label></div>
        <div class="form-field form-field--full"><label for="description">Description</label><textarea id="description" name="description" rows="3"></textarea></div>
        <div class="form-actions form-field--full"><button class="btn btn--primary" type="submit">Add experience</button></div>
    </form>
</section>

<section class="panel">
    <h2 class="panel__title">Your experience</h2>
    <?php if ($items === []): ?>
        <p class="muted">No work experience yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) $item['job_title']) ?></strong>
                        <p><?= e((string) $item['company_name']) ?><?= !empty($item['country_name']) ? ' · ' . e((string) $item['country_name']) : '' ?></p>
                        <p class="muted"><?= e((string) ($item['start_date'] ?? '')) ?> – <?= !empty($item['is_current']) ? 'Present' : e((string) ($item['end_date'] ?? '')) ?></p>
                        <?php if (!empty($item['description'])): ?><p><?= nl2br(e((string) $item['description'])) ?></p><?php endif; ?>
                    </div>
                    <details>
                        <summary>Edit</summary>
                        <form method="post" action="<?= e(app_url('/jobseeker/experience/' . (int) $item['id'])) ?>" class="form-grid">
                            <?= csrf_field() ?>
                            <div class="form-field"><label>Company</label><input name="company_name" required value="<?= e((string) $item['company_name']) ?>"></div>
                            <div class="form-field"><label>Position</label><input name="job_title" required value="<?= e((string) $item['job_title']) ?>"></div>
                            <div class="form-field">
                                <label>Country</label>
                                <select name="country_id">
                                    <option value="">Select</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= (int) $country['id'] ?>" <?= ((int) ($item['country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>><?= e((string) $country['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field"><label>Start</label><input type="date" name="start_date" value="<?= e((string) ($item['start_date'] ?? '')) ?>"></div>
                            <div class="form-field"><label>End</label><input type="date" name="end_date" value="<?= e((string) ($item['end_date'] ?? '')) ?>"></div>
                            <div class="form-field form-field--full"><label><input type="checkbox" name="is_current" value="1" <?= !empty($item['is_current']) ? 'checked' : '' ?>> Currently working</label></div>
                            <div class="form-field form-field--full"><label>Description</label><textarea name="description" rows="3"><?= e((string) ($item['description'] ?? '')) ?></textarea></div>
                            <div class="form-actions form-field--full"><button class="btn btn--secondary" type="submit">Save</button></div>
                        </form>
                        <form method="post" action="<?= e(app_url('/jobseeker/experience/' . (int) $item['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this record?');">
                            <?= csrf_field() ?>
                            <button class="btn btn--danger" type="submit">Delete</button>
                        </form>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

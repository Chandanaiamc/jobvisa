<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $catalog */
/** @var array<string, list<string>> $errors */
$levels = ['basic' => 'Basic', 'conversational' => 'Conversational', 'fluent' => 'Fluent', 'native' => 'Native'];
?>
<section class="panel">
    <h2 class="panel__title">Add language</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/languages')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-field">
            <label for="language_id">Language</label>
            <select id="language_id" name="language_id" required>
                <option value="">Select</option>
                <?php foreach ($catalog as $lang): ?>
                    <option value="<?= (int) $lang['id'] ?>"><?= e((string) $lang['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= empty($errors['language_id'][0]) ? '' : '<p class="field-error">' . e($errors['language_id'][0]) . '</p>' ?>
        </div>
        <?php foreach (['speaking' => 'Speaking', 'reading' => 'Reading', 'writing' => 'Writing'] as $field => $label): ?>
            <div class="form-field">
                <label for="<?= e($field) ?>"><?= e($label) ?></label>
                <select id="<?= e($field) ?>" name="<?= e($field) ?>">
                    <?php foreach ($levels as $val => $text): ?>
                        <option value="<?= e($val) ?>"><?= e($text) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <div class="form-actions form-field--full"><button class="btn btn--primary" type="submit">Add language</button></div>
    </form>
</section>

<section class="panel">
    <h2 class="panel__title">Your languages</h2>
    <?php if ($items === []): ?>
        <p class="muted">No languages yet.</p>
    <?php else: ?>
        <ul class="record-list">
            <?php foreach ($items as $item): ?>
                <li class="record">
                    <div>
                        <strong><?= e((string) $item['language_name']) ?></strong>
                        <p class="muted">Speaking: <?= e((string) ($item['speaking'] ?? $item['proficiency'])) ?> · Reading: <?= e((string) ($item['reading'] ?? $item['proficiency'])) ?> · Writing: <?= e((string) ($item['writing'] ?? $item['proficiency'])) ?></p>
                    </div>
                    <details>
                        <summary>Edit</summary>
                        <form method="post" action="<?= e(app_url('/jobseeker/languages/' . (int) $item['id'])) ?>" class="form-grid">
                            <?= csrf_field() ?>
                            <div class="form-field">
                                <label>Language</label>
                                <select name="language_id" required>
                                    <?php foreach ($catalog as $lang): ?>
                                        <option value="<?= (int) $lang['id'] ?>" <?= ((int) $item['language_id'] === (int) $lang['id']) ? 'selected' : '' ?>><?= e((string) $lang['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php foreach (['speaking' => 'Speaking', 'reading' => 'Reading', 'writing' => 'Writing'] as $field => $label): ?>
                                <div class="form-field">
                                    <label><?= e($label) ?></label>
                                    <select name="<?= e($field) ?>">
                                        <?php foreach ($levels as $val => $text): ?>
                                            <option value="<?= e($val) ?>" <?= (($item[$field] ?? $item['proficiency']) === $val) ? 'selected' : '' ?>><?= e($text) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-actions form-field--full"><button class="btn btn--secondary" type="submit">Save</button></div>
                        </form>
                        <form method="post" action="<?= e(app_url('/jobseeker/languages/' . (int) $item['id'] . '/delete')) ?>" onsubmit="return confirm('Remove language?');">
                            <?= csrf_field() ?>
                            <button class="btn btn--danger" type="submit">Delete</button>
                        </form>
                    </details>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

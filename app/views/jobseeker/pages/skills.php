<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $catalog */
/** @var array<string, list<string>> $errors */
$old = $old ?? [];
?>
<section class="panel">
    <h2 class="panel__title">Add skill</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/skills')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-field">
            <label for="skill_id">Master skill</label>
            <select id="skill_id" name="skill_id">
                <option value="">Select from catalog</option>
                <?php foreach ($catalog as $skill): ?>
                    <option value="<?= (int) $skill['id'] ?>"><?= e((string) $skill['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="custom_skill">Or create custom skill</label>
            <input id="custom_skill" name="custom_skill" maxlength="100" value="<?= e((string) ($old['custom_skill'] ?? '')) ?>">
            <?= empty($errors['skill_id'][0]) ? '' : '<p class="field-error">' . e($errors['skill_id'][0]) . '</p>' ?>
        </div>
        <div class="form-field">
            <label for="proficiency">Skill level</label>
            <select id="proficiency" name="proficiency">
                <?php foreach (['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $val => $label): ?>
                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions form-field--full"><button class="btn btn--primary" type="submit">Save skill</button></div>
    </form>
</section>

<section class="panel">
    <h2 class="panel__title">Your skills</h2>
    <?php if ($items === []): ?>
        <p class="muted">No skills yet.</p>
    <?php else: ?>
        <ul class="chip-list">
            <?php foreach ($items as $item): ?>
                <li class="chip">
                    <span><?= e((string) $item['skill_name']) ?> · <?= e((string) ($item['proficiency'] ?? 'intermediate')) ?></span>
                    <form method="post" action="<?= e(app_url('/jobseeker/skills/' . (int) $item['id'] . '/delete')) ?>" onsubmit="return confirm('Remove skill?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="chip__remove" aria-label="Remove">×</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

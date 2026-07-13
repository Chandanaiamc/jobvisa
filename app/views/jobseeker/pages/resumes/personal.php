<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $resume
 * @var \JobVisa\App\Domain\Resume\DTO\ResumePersonalDTO $personal
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $cities
 * @var array{score: int, sections: array} $completion
 * @var bool $canEdit
 * @var array<string, list<string>> $errors
 * @var array<string, mixed> $old
 */

$resumeSection = 'personal';
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
$preferred = $old['preferred_country_ids'] ?? [];
if (!is_array($preferred)) {
    $preferred = [];
}
$preferred = array_map('intval', $preferred);
$readonly = !$canEdit;
?>
<?php require base_path('app/views/jobseeker/partials/resume-subnav.php'); ?>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="panel__title">Personal information</h2>
            <p class="panel__lead">
                Shared profile details are reused for this resume. Passport, visa, licence and multi-country preferences are resume-specific.
            </p>
        </div>
        <div class="completeness" style="min-width:200px">
            <div class="completeness__meta">
                <span>Resume completion</span>
                <strong><?= (int) $completion['score'] ?>%</strong>
            </div>
            <div class="completeness__bar" role="progressbar" aria-valuenow="<?= (int) $completion['score'] ?>" aria-valuemin="0" aria-valuemax="100">
                <span style="width: <?= (int) $completion['score'] ?>%"></span>
            </div>
        </div>
    </div>

    <?php if (!empty($errors['form'][0])): ?>
        <div class="flash flash--error"><?= e($errors['form'][0]) ?></div>
    <?php endif; ?>

    <div class="avatar-row" style="margin-bottom:1.25rem">
        <?php if (!empty($old['avatar_path'])): ?>
            <img class="avatar" src="<?= e(app_url('/jobseeker/media/avatar')) ?>" alt="Profile photo">
        <?php else: ?>
            <div class="avatar avatar--empty" aria-hidden="true">?</div>
        <?php endif; ?>
        <?php if ($canEdit): ?>
            <div class="stack-form">
                <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/photo')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label for="photo">Profile photo (JPG, PNG, WebP · max 3 MB)</label>
                    <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    <button type="submit" class="btn btn--secondary">Upload / replace photo</button>
                </form>
                <?php if (!empty($old['avatar_path'])): ?>
                    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/photo/delete')) ?>" onsubmit="return confirm('Remove photo?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--danger">Delete photo</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(app_url('/jobseeker/resumes/' . (int) $resume['id'] . '/personal')) ?>" class="form-grid" <?= $readonly ? 'onsubmit="return false;"' : '' ?>>
        <?= csrf_field() ?>

        <h3 class="form-section form-field--full">Identity</h3>
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" required maxlength="80" value="<?= e((string) ($old['first_name'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'first_name') ?>
        </div>
        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" required maxlength="80" value="<?= e((string) ($old['last_name'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'last_name') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="headline">Professional headline</label>
            <input id="headline" name="headline" maxlength="255" value="<?= e((string) ($old['headline'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'headline') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="summary">Summary / About me</label>
            <textarea id="summary" name="summary" rows="4" <?= $readonly ? 'readonly' : '' ?>><?= e((string) ($old['summary'] ?? '')) ?></textarea>
        </div>
        <div class="form-field">
            <label for="date_of_birth">Date of birth</label>
            <input id="date_of_birth" type="date" name="date_of_birth" value="<?= e((string) ($old['date_of_birth'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'date_of_birth') ?>
        </div>
        <div class="form-field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['gender'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'gender') ?>
        </div>
        <div class="form-field">
            <label for="nationality_country_id">Nationality</label>
            <select id="nationality_country_id" name="nationality_country_id" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= ((int) ($old['nationality_country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>><?= e((string) $country['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'nationality_country_id') ?>
        </div>
        <div class="form-field">
            <label for="marital_status">Marital status</label>
            <select id="marital_status" name="marital_status" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach (['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['marital_status'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'marital_status') ?>
        </div>
        <div class="form-field">
            <label for="nic_number">NIC number</label>
            <input id="nic_number" name="nic_number" maxlength="64" value="<?= e((string) ($old['nic_number'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'nic_number') ?>
        </div>

        <h3 class="form-section form-field--full">Passport &amp; visa</h3>
        <div class="form-field">
            <label for="passport_number">Passport number</label>
            <input id="passport_number" name="passport_number" maxlength="64" value="<?= e((string) ($old['passport_number'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'passport_number') ?>
        </div>
        <div class="form-field">
            <label for="passport_expiry">Passport expiry date</label>
            <input id="passport_expiry" type="date" name="passport_expiry" value="<?= e((string) ($old['passport_expiry'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'passport_expiry') ?>
        </div>
        <div class="form-field">
            <label for="visa_status">Visa status</label>
            <select id="visa_status" name="visa_status" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach (['none' => 'None', 'tourist' => 'Tourist', 'work' => 'Work', 'resident' => 'Resident', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['visa_status'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'visa_status') ?>
        </div>
        <div class="form-field">
            <label for="driving_licence_status">Driving licence status</label>
            <select id="driving_licence_status" name="driving_licence_status" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach (['none' => 'None', 'light' => 'Light vehicle', 'heavy' => 'Heavy vehicle', 'motorcycle' => 'Motorcycle', 'international' => 'International'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($old['driving_licence_status'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'driving_licence_status') ?>
        </div>

        <h3 class="form-section form-field--full">Location &amp; contact</h3>
        <div class="form-field">
            <label for="current_country_id">Current country</label>
            <select id="current_country_id" name="current_country_id" data-city-filter <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= ((int) ($old['current_country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>><?= e((string) $country['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'current_country_id') ?>
        </div>
        <div class="form-field">
            <label for="current_city_id">Current city</label>
            <select id="current_city_id" name="current_city_id" <?= $readonly ? 'disabled' : '' ?>>
                <option value="">Select</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= (int) $city['id'] ?>" data-country="<?= (int) $city['country_id'] ?>" <?= ((int) ($old['current_city_id'] ?? 0) === (int) $city['id']) ? 'selected' : '' ?>><?= e((string) $city['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field form-field--full">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2" <?= $readonly ? 'readonly' : '' ?>><?= e((string) ($old['address'] ?? '')) ?></textarea>
            <?= $fieldError($errors, 'address') ?>
        </div>
        <div class="form-field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" maxlength="32" value="<?= e((string) ($old['phone'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'phone') ?>
        </div>
        <div class="form-field">
            <label for="whatsapp">WhatsApp number</label>
            <input id="whatsapp" name="whatsapp" maxlength="32" value="<?= e((string) ($old['whatsapp'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'whatsapp') ?>
        </div>
        <div class="form-field">
            <label for="email">Email (read only)</label>
            <input id="email" type="email" value="<?= e((string) ($old['email'] ?? $personal->email)) ?>" readonly>
        </div>

        <h3 class="form-section form-field--full">Salary &amp; preferences</h3>
        <div class="form-field">
            <label for="expected_salary">Expected salary</label>
            <input id="expected_salary" type="number" min="0" step="0.01" name="expected_salary" value="<?= e((string) ($old['expected_salary'] ?? '')) ?>" <?= $readonly ? 'readonly' : '' ?>>
            <?= $fieldError($errors, 'expected_salary') ?>
        </div>
        <div class="form-field">
            <label for="salary_currency">Salary currency</label>
            <select id="salary_currency" name="salary_currency" <?= $readonly ? 'disabled' : '' ?>>
                <?php foreach (['LKR', 'USD', 'AED', 'QAR', 'SAR', 'EUR', 'GBP'] as $cur): ?>
                    <option value="<?= e($cur) ?>" <?= (($old['salary_currency'] ?? 'LKR') === $cur) ? 'selected' : '' ?>><?= e($cur) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $fieldError($errors, 'salary_currency') ?>
        </div>
        <div class="form-field form-field--full">
            <label for="preferred_country_ids">Preferred job countries</label>
            <select id="preferred_country_ids" name="preferred_country_ids[]" multiple size="6" <?= $readonly ? 'disabled' : '' ?>>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= in_array((int) $country['id'], $preferred, true) ? 'selected' : '' ?>><?= e((string) $country['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">Hold Ctrl/Cmd to select multiple countries.</p>
            <?= $fieldError($errors, 'preferred_country_ids') ?>
        </div>

        <?php if ($canEdit): ?>
            <div class="form-actions form-field--full">
                <button type="submit" class="btn btn--primary">Save personal information</button>
            </div>
        <?php else: ?>
            <p class="muted form-field--full">Read-only view. Employers cannot edit resume personal data.</p>
        <?php endif; ?>
    </form>
</section>

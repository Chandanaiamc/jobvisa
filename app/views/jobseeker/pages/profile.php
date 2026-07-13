<?php

declare(strict_types=1);

/**
 * @var array<string, mixed>|null $profile
 * @var list<array<string, mixed>> $countries
 * @var list<array<string, mixed>> $cities
 * @var array<string, list<string>> $errors
 */

$p = $profile ?? [];
$fieldError = static function (array $errors, string $field): string {
    return empty($errors[$field][0]) ? '' : '<p class="field-error">' . e($errors[$field][0]) . '</p>';
};

$email = (string) ($p['user_email'] ?? '');
$phone = (string) ($p['user_phone'] ?? ($p['phone'] ?? ''));
?>
<section class="panel">
    <h2 class="panel__title">Profile photo</h2>
    <div class="avatar-row">
        <?php if (!empty($p['avatar_path'])): ?>
            <img class="avatar" src="<?= e(app_url('/jobseeker/media/avatar')) ?>" alt="Profile photo">
        <?php else: ?>
            <div class="avatar avatar--empty" aria-hidden="true">?</div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/jobseeker/profile/avatar')) ?>" enctype="multipart/form-data" class="stack-form">
            <?= csrf_field() ?>
            <label for="avatar">Upload JPG, PNG or WebP (max 2MB)</label>
            <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
            <button type="submit" class="btn btn--secondary">Update photo</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2 class="panel__title">Personal information</h2>
    <form method="post" action="<?= e(app_url('/jobseeker/profile')) ?>" class="form-grid" novalidate>
        <?= csrf_field() ?>

        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" required value="<?= e((string) ($p['first_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'first_name') ?>
        </div>
        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" required value="<?= e((string) ($p['last_name'] ?? '')) ?>">
            <?= $fieldError($errors, 'last_name') ?>
        </div>
        <div class="form-field">
            <label for="nic_passport">NIC / Passport</label>
            <input id="nic_passport" name="nic_passport" value="<?= e((string) ($p['nic_passport'] ?? '')) ?>">
        </div>
        <div class="form-field">
            <label for="date_of_birth">Date of birth</label>
            <input id="date_of_birth" type="date" name="date_of_birth" value="<?= e((string) ($p['date_of_birth'] ?? '')) ?>">
        </div>
        <div class="form-field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <?php foreach (['' => 'Select', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($p['gender'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="marital_status">Marital status</label>
            <select id="marital_status" name="marital_status">
                <?php foreach (['' => 'Select', 'single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($p['marital_status'] ?? '') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="nationality_country_id">Nationality</label>
            <select id="nationality_country_id" name="nationality_country_id">
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= ((int) ($p['nationality_country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>>
                        <?= e((string) $country['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="expected_salary">Expected salary</label>
            <input id="expected_salary" name="expected_salary" type="number" min="0" step="0.01" value="<?= e((string) ($p['expected_salary'] ?? '')) ?>">
        </div>

        <div class="form-field form-field--full">
            <label for="headline">Professional headline</label>
            <input id="headline" name="headline" maxlength="255" value="<?= e((string) ($p['headline'] ?? '')) ?>">
        </div>
        <div class="form-field form-field--full">
            <label for="summary">Summary / About me</label>
            <textarea id="summary" name="summary" rows="5"><?= e((string) ($p['summary'] ?? '')) ?></textarea>
        </div>

        <div class="form-field">
            <label for="preferred_country_id">Preferred country</label>
            <select id="preferred_country_id" name="preferred_country_id">
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= ((int) ($p['preferred_country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>>
                        <?= e((string) $country['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="current_country_id">Current country</label>
            <select id="current_country_id" name="current_country_id" data-city-filter>
                <option value="">Select</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= (int) $country['id'] ?>" <?= ((int) ($p['current_country_id'] ?? 0) === (int) $country['id']) ? 'selected' : '' ?>>
                        <?= e((string) $country['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="current_city_id">Current city</label>
            <select id="current_city_id" name="current_city_id">
                <option value="">Select</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= (int) $city['id'] ?>"
                            data-country="<?= (int) $city['country_id'] ?>"
                            <?= ((int) ($p['current_city_id'] ?? 0) === (int) $city['id']) ? 'selected' : '' ?>>
                        <?= e((string) $city['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field form-field--full">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2"><?= e((string) ($p['address'] ?? '')) ?></textarea>
        </div>
        <div class="form-field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="<?= e($phone) ?>">
        </div>
        <div class="form-field">
            <label for="whatsapp">WhatsApp</label>
            <input id="whatsapp" name="whatsapp" value="<?= e((string) ($p['whatsapp'] ?? '')) ?>">
        </div>
        <div class="form-field">
            <label for="email">Email (read only)</label>
            <input id="email" type="email" value="<?= e($email) ?>" readonly>
        </div>
        <div class="form-field">
            <label for="visibility">Profile visibility</label>
            <select id="visibility" name="visibility">
                <?php foreach (['employers' => 'Employers only', 'public' => 'Public', 'private' => 'Private'] as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= (($p['visibility'] ?? 'employers') === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn btn--primary">Save profile</button>
        </div>
    </form>
</section>

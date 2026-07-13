<?php

declare(strict_types=1);

/**
 * @var array<string, list<string>> $errors
 * @var string $token
 * @var string $email
 */

$fieldError = static function (array $errors, string $field): string {
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="auth-card" aria-labelledby="reset-heading">
    <h1 id="reset-heading" class="auth-card__title">Reset password</h1>
    <p class="auth-card__lead">Choose a new password for <?= e($email) ?>.</p>

    <form class="auth-form" method="post" action="<?= e(app_url('/reset-password')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <input type="hidden" name="email" value="<?= e($email) ?>">

        <div class="form-field <?= empty($errors['password']) ? '' : 'is-invalid' ?>">
            <label for="password">New password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"
                       aria-invalid="<?= empty($errors['password']) ? 'false' : 'true' ?>">
                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">Show</button>
            </div>
            <p class="field-hint">At least 8 characters.</p>
            <?= $fieldError($errors, 'password') ?>
        </div>

        <div class="form-field">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-field">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8">
                <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password">Show</button>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Update password</button>
    </form>

    <p class="auth-card__alt"><a href="<?= e(app_url('/login')) ?>">Back to sign in</a></p>
</section>

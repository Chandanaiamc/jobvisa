<?php

declare(strict_types=1);

/**
 * @var array<string, list<string>> $errors
 * @var array{email: string, remember: string} $old
 */

$fieldError = static function (array $errors, string $field): string {
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="auth-card" aria-labelledby="login-heading">
    <h1 id="login-heading" class="auth-card__title">Sign in</h1>
    <p class="auth-card__lead">Access your JobVisa.lk account securely.</p>

    <div class="auth-api-status" data-api-auth-status hidden role="status" aria-live="polite"></div>

    <form class="auth-form" method="post" action="<?= e(app_url('/login')) ?>"
          data-api-auth-login novalidate>
        <?= csrf_field() ?>

        <?php if (!empty($errors['form'][0])): ?>
            <p class="auth-form-error" role="alert"><?= e($errors['form'][0]) ?></p>
        <?php endif; ?>

        <div class="form-field <?= empty($errors['email']) ? '' : 'is-invalid' ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="username" required
                   value="<?= e($old['email']) ?>"
                   aria-invalid="<?= empty($errors['email']) ? 'false' : 'true' ?>"
                   aria-describedby="<?= empty($errors['email']) ? '' : 'email-error' ?>">
            <?= $fieldError($errors, 'email') ?>
        </div>

        <div class="form-field <?= empty($errors['password']) ? '' : 'is-invalid' ?>">
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       aria-invalid="<?= empty($errors['password']) ? 'false' : 'true' ?>"
                       aria-describedby="<?= empty($errors['password']) ? '' : 'password-error' ?>">
                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">Show</button>
            </div>
            <?= $fieldError($errors, 'password') ?>
        </div>

        <div class="form-field form-field--check">
            <label class="check-label" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1" <?= $old['remember'] !== '' ? 'checked' : '' ?>>
                <span>Remember me</span>
            </label>
        </div>

        <button type="submit" class="btn btn--primary">Sign in</button>
    </form>

    <p class="auth-card__alt">
        <a href="<?= e(app_url('/forgot-password')) ?>">Forgot password?</a>
        ·
        <a href="<?= e(app_url('/register')) ?>">Create an account</a>
    </p>
</section>

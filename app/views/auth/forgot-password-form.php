<?php

declare(strict_types=1);

/**
 * @var array<string, list<string>> $errors
 * @var array{email: string} $old
 */

$fieldError = static function (array $errors, string $field): string {
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="auth-card" aria-labelledby="forgot-heading">
    <h1 id="forgot-heading" class="auth-card__title">Forgot password</h1>
    <p class="auth-card__lead">
        Enter your email and we will send reset instructions if an account exists.
        For privacy, the response is always the same.
    </p>

    <form class="auth-form" method="post" action="<?= e(app_url('/forgot-password')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-field <?= empty($errors['email']) ? '' : 'is-invalid' ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="<?= e($old['email']) ?>"
                   aria-invalid="<?= empty($errors['email']) ? 'false' : 'true' ?>">
            <?= $fieldError($errors, 'email') ?>
        </div>

        <button type="submit" class="btn btn--primary">Send reset link</button>
    </form>

    <p class="auth-card__alt"><a href="<?= e(app_url('/login')) ?>">Back to sign in</a></p>
</section>

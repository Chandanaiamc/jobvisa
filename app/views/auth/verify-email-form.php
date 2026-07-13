<?php

declare(strict_types=1);

/**
 * @var array<string, list<string>> $errors
 * @var array<string, mixed>|null $user
 * @var string $email
 */

$fieldError = static function (array $errors, string $field): string {
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="auth-card" aria-labelledby="verify-heading">
    <h1 id="verify-heading" class="auth-card__title">Verify your email</h1>
    <p class="auth-card__lead">
        We sent a verification link to your inbox. Open the link to activate full access.
        In local development the message is written to <code>storage/logs/mail-*.log</code>.
    </p>

    <form class="auth-form" method="post" action="<?= e(app_url('/email/verification-notification')) ?>">
        <?= csrf_field() ?>

        <?php if ($user === null): ?>
            <div class="form-field <?= empty($errors['email']) ? '' : 'is-invalid' ?>">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" required value="<?= e($email) ?>"
                       aria-invalid="<?= empty($errors['email']) ? 'false' : 'true' ?>">
                <?= $fieldError($errors, 'email') ?>
            </div>
        <?php else: ?>
            <p class="field-hint">Signed in as <strong><?= e($email) ?></strong></p>
        <?php endif; ?>

        <button type="submit" class="btn btn--primary">Resend verification email</button>
    </form>

    <div class="auth-card__alt">
        <a href="<?= e(app_url('/login')) ?>">Back to sign in</a>
        <?php if ($user !== null): ?>
            <span aria-hidden="true"> · </span>
            <form method="post" action="<?= e(app_url('/logout')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="linkish">Sign out</button>
            </form>
        <?php endif; ?>
    </div>
</section>

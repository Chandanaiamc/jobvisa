<?php

declare(strict_types=1);

/**
 * @var array<string, list<string>> $errors
 * @var array{first_name: string, last_name: string, email: string, phone: string, role: string, terms: string} $old
 */

$fieldError = static function (array $errors, string $field): string {
    if (empty($errors[$field][0])) {
        return '';
    }

    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field][0]) . '</p>';
};
?>
<section class="auth-card" aria-labelledby="register-heading">
    <h1 id="register-heading" class="auth-card__title">Create your account</h1>
    <p class="auth-card__lead">Join JobVisa.lk to explore overseas opportunities or hire Sri Lankan talent.</p>

    <form class="auth-form" method="post" action="<?= e(app_url('/register')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-field <?= empty($errors['first_name']) ? '' : 'is-invalid' ?>">
                <label for="first_name">First name</label>
                <input id="first_name" name="first_name" type="text" autocomplete="given-name" required
                       value="<?= e($old['first_name']) ?>"
                       aria-invalid="<?= empty($errors['first_name']) ? 'false' : 'true' ?>"
                       aria-describedby="<?= empty($errors['first_name']) ? '' : 'first_name-error' ?>">
                <?= $fieldError($errors, 'first_name') ?>
            </div>
            <div class="form-field <?= empty($errors['last_name']) ? '' : 'is-invalid' ?>">
                <label for="last_name">Last name</label>
                <input id="last_name" name="last_name" type="text" autocomplete="family-name" required
                       value="<?= e($old['last_name']) ?>"
                       aria-invalid="<?= empty($errors['last_name']) ? 'false' : 'true' ?>"
                       aria-describedby="<?= empty($errors['last_name']) ? '' : 'last_name-error' ?>">
                <?= $fieldError($errors, 'last_name') ?>
            </div>
        </div>

        <div class="form-field <?= empty($errors['email']) ? '' : 'is-invalid' ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="<?= e($old['email']) ?>"
                   aria-invalid="<?= empty($errors['email']) ? 'false' : 'true' ?>"
                   aria-describedby="<?= empty($errors['email']) ? '' : 'email-error' ?>">
            <?= $fieldError($errors, 'email') ?>
        </div>

        <div class="form-field <?= empty($errors['phone']) ? '' : 'is-invalid' ?>">
            <label for="phone">Phone number <span class="optional">(optional)</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel"
                   value="<?= e($old['phone']) ?>"
                   aria-invalid="<?= empty($errors['phone']) ? 'false' : 'true' ?>"
                   aria-describedby="<?= empty($errors['phone']) ? '' : 'phone-error' ?>">
            <?= $fieldError($errors, 'phone') ?>
        </div>

        <fieldset class="form-fieldset <?= empty($errors['role']) ? '' : 'is-invalid' ?>">
            <legend>Account type</legend>
            <div class="choice-grid" role="radiogroup" aria-label="Account type">
                <label class="choice">
                    <input type="radio" name="role" value="seeker" <?= $old['role'] === 'seeker' ? 'checked' : '' ?>>
                    <span>Job Seeker</span>
                </label>
                <label class="choice">
                    <input type="radio" name="role" value="employer" <?= $old['role'] === 'employer' ? 'checked' : '' ?>>
                    <span>Employer</span>
                </label>
            </div>
            <?= $fieldError($errors, 'role') ?>
        </fieldset>

        <div class="form-field <?= empty($errors['password']) ? '' : 'is-invalid' ?>">
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"
                       aria-invalid="<?= empty($errors['password']) ? 'false' : 'true' ?>"
                       aria-describedby="password-hint <?= empty($errors['password']) ? '' : 'password-error' ?>">
                <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password">Show</button>
            </div>
            <p class="field-hint" id="password-hint">At least 8 characters.</p>
            <?= $fieldError($errors, 'password') ?>
        </div>

        <div class="form-field <?= empty($errors['password']) ? '' : 'is-invalid' ?>">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-field">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8">
                <button type="button" class="password-toggle" data-toggle-password="password_confirmation" aria-label="Show password">Show</button>
            </div>
        </div>

        <div class="form-field form-field--check <?= empty($errors['terms']) ? '' : 'is-invalid' ?>">
            <label class="check-label" for="terms">
                <input id="terms" name="terms" type="checkbox" value="1" <?= $old['terms'] !== '' ? 'checked' : '' ?> required
                       aria-invalid="<?= empty($errors['terms']) ? 'false' : 'true' ?>"
                       aria-describedby="<?= empty($errors['terms']) ? '' : 'terms-error' ?>">
                <span>I agree to the Terms and Conditions</span>
            </label>
            <?= $fieldError($errors, 'terms') ?>
        </div>

        <button type="submit" class="btn btn--primary">Create account</button>
    </form>

    <p class="auth-card__alt">Already have an account? <a href="<?= e(app_url('/login')) ?>">Sign in</a></p>
</section>

<?php

declare(strict_types=1);

/** @var mixed $flashSuccess */
/** @var mixed $flashError */
/** @var array<string, list<string>> $errors */
?>
<?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="flash flash--success" role="status"><?= e($flashSuccess) ?></div>
<?php endif; ?>

<?php if (is_string($flashError) && $flashError !== ''): ?>
    <div class="flash flash--error" role="alert"><?= e($flashError) ?></div>
<?php endif; ?>

<?php if (!empty($errors['form'])): ?>
    <div class="flash flash--error" role="alert">
        <?php foreach ($errors['form'] as $message): ?>
            <p><?= e($message) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

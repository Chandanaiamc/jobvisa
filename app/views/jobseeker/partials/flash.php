<?php

declare(strict_types=1);

use JobVisa\App\Security\SessionManager;

$success = SessionManager::getFlash('success');
$error = SessionManager::getFlash('error');
?>
<?php if ($success): ?>
    <div class="flash flash--success" role="status"><?= e((string) $success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash--error" role="alert"><?= e((string) $error) ?></div>
<?php endif; ?>

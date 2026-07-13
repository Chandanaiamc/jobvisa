<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $tokens
 * @var string $csrf
 * @var mixed $plain_token
 * @var mixed $flash_error
 * @var mixed $flash_success
 */
?>
<section class="dp-section">
    <h2>API tokens</h2>
    <p class="lead">Create and revoke personal access tokens for the JSON API. Raw secrets are never stored.</p>

    <?php if (is_string($flash_success) && $flash_success !== ''): ?>
        <div class="dp-alert dp-alert-ok"><?= e($flash_success) ?></div>
    <?php endif; ?>
    <?php if (is_string($flash_error) && $flash_error !== ''): ?>
        <div class="dp-alert dp-alert-warn"><?= e($flash_error) ?></div>
    <?php endif; ?>
    <?php if (is_string($plain_token) && $plain_token !== ''): ?>
        <div class="dp-alert dp-alert-warn">
            Copy this token now — it will not be shown again.
            <pre class="dp-code"><?= e($plain_token) ?></pre>
        </div>
    <?php endif; ?>

    <form class="dp-form" method="post" action="<?= e(url('/developers/tokens')) ?>">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <label>
            Token name / device
            <input type="text" name="name" maxlength="120" required placeholder="CI bot, laptop, …">
        </label>
        <button class="dp-btn dp-btn-primary" type="submit">Create token</button>
    </form>

    <table class="dp-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Prefix</th>
            <th>Last used</th>
            <th>Expires</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if ($tokens === []): ?>
            <tr><td colspan="5">No tokens yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($tokens as $token): ?>
            <tr>
                <td><?= e((string) ($token['name'] ?? '')) ?></td>
                <td><code><?= e((string) ($token['token_prefix'] ?? '')) ?></code></td>
                <td><?= e((string) ($token['last_used_at'] ?? '—')) ?></td>
                <td><?= e((string) ($token['expires_at'] ?? 'never')) ?></td>
                <td>
                    <?php if (empty($token['revoked_at'])): ?>
                        <form method="post" action="<?= e(url('/developers/tokens/' . (int) ($token['id'] ?? 0) . '/revoke')) ?>">
                            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
                            <button class="dp-btn dp-btn-ghost" type="submit">Revoke</button>
                        </form>
                    <?php else: ?>
                        <span class="dp-badge">Revoked</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

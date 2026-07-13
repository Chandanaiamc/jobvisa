<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $document */
/** @var string $api_json_url */
?>
<section class="dp-section">
    <h2>OpenAPI</h2>
    <p class="lead">Machine-readable contract for generators and SDK tooling.</p>

    <p>
        <a class="dp-btn dp-btn-primary" href="<?= e($api_json_url) ?>">Fetch JSON via API</a>
        <a class="dp-btn dp-btn-ghost" href="<?= e(url('/api/v1/docs/openapi')) ?>">OpenAPI probe</a>
    </p>

    <?php if ($document === null): ?>
        <div class="dp-alert dp-alert-warn">OpenAPI document missing.</div>
    <?php else: ?>
        <div class="dp-panel">
            <h3><?= e((string) ($document['info']['title'] ?? 'JobVisa API')) ?></h3>
            <p>OpenAPI <?= e((string) ($document['openapi'] ?? '')) ?> · version <?= e((string) ($document['info']['version'] ?? '')) ?></p>
        </div>
        <pre class="dp-code"><?= e(json_encode([
            'openapi' => $document['openapi'] ?? null,
            'info' => $document['info'] ?? null,
            'paths' => array_keys($document['paths'] ?? []),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}') ?></pre>
    <?php endif; ?>
</section>

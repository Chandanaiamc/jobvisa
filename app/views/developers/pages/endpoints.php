<?php

declare(strict_types=1);

/** @var list<array{group: string, method: string, path: string, auth: bool, summary: string}> $endpoints */
/** @var string $api_base */
?>
<section class="dp-section">
    <h2>Endpoints</h2>
    <p class="lead">Base URL <code><?= e($api_base) ?></code></p>
    <table class="dp-table">
        <thead>
        <tr>
            <th>Group</th>
            <th>Method</th>
            <th>Path</th>
            <th>Auth</th>
            <th>Summary</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($endpoints as $row): ?>
            <tr>
                <td><?= e($row['group']) ?></td>
                <td><span class="dp-badge"><?= e($row['method']) ?></span></td>
                <td><code><?= e($row['path']) ?></code></td>
                <td><?= $row['auth'] ? 'Bearer' : 'Public' ?></td>
                <td><?= e($row['summary']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

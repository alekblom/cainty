<h1><?= e($adminPageTitle) ?></h1>

<div class="section-header">
    <a href="<?= cainty_admin_url('agents') ?>" class="btn btn-outline">&larr; Back to Agents</a>
</div>

<?php if (!empty($runs)): ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Agent</th>
            <th>Topic</th>
            <th>Model</th>
            <th>Tokens</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($runs as $r): ?>
        <tr>
            <td class="text-muted"><?= cainty_time_ago($r['created_at']) ?></td>
            <td><?= e($r['agent_name'] ?? '—') ?></td>
            <td><?= e(mb_strimwidth($r['topic_prompt'], 0, 60, '...')) ?></td>
            <td>
                <small class="text-muted"><?= e($r['provider_used']) ?></small><br>
                <small><?= e($r['model_used']) ?></small>
            </td>
            <td>
                <small><?= number_format($r['input_tokens']) ?> in</small><br>
                <small><?= number_format($r['output_tokens']) ?> out</small>
            </td>
            <td class="text-muted"><?= number_format($r['duration_ms'] / 1000, 1) ?>s</td>
            <td>
                <span class="status-badge status-<?= $r['status'] === 'completed' ? 'published' : ($r['status'] === 'failed' ? 'archived' : 'draft') ?>">
                    <?= e($r['status']) ?>
                </span>
            </td>
            <td>
                <?php if ($r['post_id']): ?>
                    <a href="<?= cainty_admin_url('posts/' . $r['post_id'] . '/edit') ?>" class="btn btn-sm">View Post</a>
                <?php endif; ?>
                <?php if ($r['status'] === 'failed' && $r['error_message']): ?>
                    <button class="btn btn-sm" onclick="alert('<?= e(addslashes($r['error_message'])) ?>')">Error</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="text-muted">No runs yet.</p>
<?php endif; ?>

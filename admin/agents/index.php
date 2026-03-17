<h1>AI Agents</h1>

<div class="section-header">
    <div>
        <a href="<?= cainty_admin_url('agents/new') ?>" class="btn btn-accent">New Agent</a>
        <a href="<?= cainty_admin_url('agents/runs') ?>" class="btn btn-outline">All Runs</a>
    </div>
</div>

<?php if (!empty($agents)): ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Model</th>
            <th>Runs</th>
            <th>Last Run</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($agents as $a): ?>
        <tr>
            <td>
                <a href="<?= cainty_admin_url('agents/' . $a['agent_id'] . '/edit') ?>">
                    <strong><?= e($a['name']) ?></strong>
                </a>
                <?php if ($a['description']): ?>
                    <br><small class="text-muted"><?= e(mb_strimwidth($a['description'], 0, 80, '...')) ?></small>
                <?php endif; ?>
            </td>
            <td>
                <span class="text-muted"><?= e($a['model_provider'] ?? '—') ?></span><br>
                <small><?= e($a['model_slug'] ?? '') ?></small>
            </td>
            <td><?= (int) ($a['run_count'] ?? 0) ?></td>
            <td class="text-muted">
                <?= $a['last_run_at'] ? cainty_time_ago($a['last_run_at']) : '—' ?>
            </td>
            <td>
                <span class="status-badge status-<?= $a['is_active'] ? 'published' : 'draft' ?>">
                    <?= $a['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </td>
            <td class="actions">
                <a href="<?= cainty_admin_url('agents/' . $a['agent_id'] . '/edit') ?>" class="btn btn-sm">Edit</a>
                <a href="<?= cainty_admin_url('agents/' . $a['agent_id'] . '/runs') ?>" class="btn btn-sm">Runs</a>
                <button class="btn btn-sm btn-danger" onclick="deleteAgent(<?= $a['agent_id'] ?>)">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="empty-state">
        <p class="text-muted">No agents configured yet.</p>
        <a href="<?= cainty_admin_url('agents/new') ?>" class="btn btn-accent">Create Your First Agent</a>
    </div>
<?php endif; ?>

<script>
function deleteAgent(id) {
    if (!confirm('Delete this agent and all its memory? This cannot be undone.')) return;
    fetch(CAINTY.adminUrl + '/agents/' + id + '/delete', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CAINTY.csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.error); });
}
</script>

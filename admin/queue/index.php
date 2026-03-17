<h1>Content Queue</h1>

<div class="status-tabs">
    <a href="<?= cainty_admin_url('queue') ?>" class="tab <?= empty($_GET['status']) ? 'active' : '' ?>">
        All (<?= array_sum($counts) ?>)
    </a>
    <a href="<?= cainty_admin_url('queue') ?>?status=pending_review" class="tab <?= ($_GET['status'] ?? '') === 'pending_review' ? 'active' : '' ?>">
        Pending (<?= $counts['pending_review'] ?? 0 ?>)
    </a>
    <a href="<?= cainty_admin_url('queue') ?>?status=approved" class="tab <?= ($_GET['status'] ?? '') === 'approved' ? 'active' : '' ?>">
        Approved (<?= $counts['approved'] ?? 0 ?>)
    </a>
    <a href="<?= cainty_admin_url('queue') ?>?status=rejected" class="tab <?= ($_GET['status'] ?? '') === 'rejected' ? 'active' : '' ?>">
        Rejected (<?= $counts['rejected'] ?? 0 ?>)
    </a>
</div>

<?php if (!empty($items)): ?>
<table class="admin-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Agent</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td>
                <a href="<?= cainty_admin_url('queue/' . $item['queue_id'] . '/review') ?>">
                    <strong><?= e($item['title']) ?></strong>
                </a>
                <?php if ($item['excerpt']): ?>
                    <br><small class="text-muted"><?= e(mb_strimwidth($item['excerpt'], 0, 100, '...')) ?></small>
                <?php endif; ?>
            </td>
            <td class="text-muted"><?= e($item['agent_name'] ?? '—') ?></td>
            <td>
                <?php
                $statusClass = match($item['status']) {
                    'pending_review' => 'draft',
                    'approved' => 'published',
                    'rejected' => 'archived',
                    default => 'draft',
                };
                ?>
                <span class="status-badge status-<?= $statusClass ?>">
                    <?= str_replace('_', ' ', e($item['status'])) ?>
                </span>
            </td>
            <td class="text-muted"><?= cainty_time_ago($item['created_at']) ?></td>
            <td>
                <?php if ($item['status'] === 'pending_review'): ?>
                    <a href="<?= cainty_admin_url('queue/' . $item['queue_id'] . '/review') ?>" class="btn btn-sm btn-accent">Review</a>
                <?php else: ?>
                    <a href="<?= cainty_admin_url('queue/' . $item['queue_id'] . '/review') ?>" class="btn btn-sm">View</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="empty-state">
        <p class="text-muted">No items in the queue. Run an agent to generate content.</p>
        <a href="<?= cainty_admin_url('agents') ?>" class="btn btn-accent">Go to Agents</a>
    </div>
<?php endif; ?>

<?php
$isNew = empty($agent);
$agentId = $agent['agent_id'] ?? '';
?>

<div class="editor-header">
    <h1><?= $isNew ? 'New Agent' : 'Edit Agent' ?></h1>
    <div class="editor-actions">
        <button class="btn btn-accent" id="saveAgentBtn">Save Agent</button>
        <?php if (!$isNew): ?>
        <button class="btn btn-outline" id="executeBtn">Execute</button>
        <?php endif; ?>
    </div>
</div>

<form id="agentForm" class="agent-form">
    <input type="hidden" name="agent_id" value="<?= $agentId ?>">

    <div class="form-columns">
        <div class="form-main">
            <div class="form-group">
                <label for="agent-name">Name</label>
                <input type="text" id="agent-name" name="name" value="<?= e($agent['name'] ?? '') ?>" placeholder="e.g. Tech Blog Writer" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="agent-slug">Slug</label>
                    <input type="text" id="agent-slug" name="slug" value="<?= e($agent['slug'] ?? '') ?>" placeholder="auto-generated">
                </div>
                <div class="form-group">
                    <label for="agent-active">
                        <input type="checkbox" id="agent-active" name="is_active" <?= ($agent['is_active'] ?? 1) ? 'checked' : '' ?>> Active
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="agent-description">Description</label>
                <input type="text" name="description" id="agent-description" value="<?= e($agent['description'] ?? '') ?>" placeholder="Brief description of what this agent does">
            </div>

            <div class="form-group">
                <label for="agent-prompt">System Prompt</label>
                <textarea id="agent-prompt" name="system_prompt" rows="12" placeholder="You are an expert blog writer..."><?= e($agent['system_prompt'] ?? '') ?></textarea>
                <small class="text-muted">The core instructions for this agent. Categories, memory, output schema, and quality checklist are appended automatically.</small>
            </div>

            <div class="form-group">
                <label for="agent-voice">Voice Rules (JSON array)</label>
                <textarea id="agent-voice" name="voice_rules" rows="4" placeholder='["Write in first person", "Use active voice", "Be concise"]'><?= e($agent['voice_rules'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="agent-shortcodes">Shortcode Rules (JSON object)</label>
                <textarea id="agent-shortcodes" name="shortcode_rules" rows="4" placeholder='{"table": "Use [table caption=\"X\"]rows[/table] for data tables"}'><?= e($agent['shortcode_rules'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="agent-schema">Output Schema Override (JSON)</label>
                <textarea id="agent-schema" name="output_schema" rows="4" placeholder="Leave empty for default schema"><?= e($agent['output_schema'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="agent-checklist">Quality Checklist (JSON array)</label>
                <textarea id="agent-checklist" name="quality_checklist" rows="4" placeholder='["Includes introduction", "Has actionable conclusion", "contains:keyword"]'><?= e($agent['quality_checklist'] ?? '') ?></textarea>
                <small class="text-muted">Use "contains:keyword" for automatic content validation.</small>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="sidebar-section">
                <h3>Model</h3>
                <div class="form-group">
                    <label for="agent-provider">Provider</label>
                    <select id="agent-provider" name="model_provider">
                        <?php foreach ($models as $provName => $provModels): ?>
                        <option value="<?= e($provName) ?>" <?= ($agent['model_provider'] ?? '') === $provName ? 'selected' : '' ?>>
                            <?= ucfirst(e($provName)) ?>
                            <?= in_array($provName, $availableProviders) ? '' : '(no key)' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="agent-model">Model</label>
                    <select id="agent-model" name="model_slug">
                        <?php foreach ($models as $provName => $provModels): ?>
                            <?php foreach ($provModels as $m): ?>
                            <option value="<?= e($m['slug']) ?>" data-provider="<?= e($provName) ?>" <?= ($agent['model_slug'] ?? '') === $m['slug'] ? 'selected' : '' ?>>
                                <?= e($m['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Post Length</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Min Words</label>
                        <input type="number" name="post_length_min" value="<?= (int) ($agent['post_length_min'] ?? 800) ?>" min="100" step="100">
                    </div>
                    <div class="form-group">
                        <label>Max Words</label>
                        <input type="number" name="post_length_max" value="<?= (int) ($agent['post_length_max'] ?? 1500) ?>" min="200" step="100">
                    </div>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>Default Categories (JSON)</h3>
                <textarea name="categories" rows="3" placeholder='["technology", "tutorials"]'><?= e($agent['categories'] ?? '') ?></textarea>
            </div>

            <div class="sidebar-section">
                <h3>Tags Strategy (JSON)</h3>
                <textarea name="tags_strategy" rows="3" placeholder='{"mode": "auto", "max": 5}'><?= e($agent['tags_strategy'] ?? '') ?></textarea>
            </div>

            <?php if (!$isNew): ?>
            <div class="sidebar-section">
                <h3>Execute Agent</h3>
                <div class="form-group">
                    <label for="execute-topic">Topic Prompt</label>
                    <textarea id="execute-topic" rows="3" placeholder="Write about..."></textarea>
                </div>
                <button type="button" class="btn btn-accent btn-block" id="executeAgentBtn">Run Agent</button>
                <div id="executeResult" class="execute-result" style="display:none;"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script src="<?= cainty_url('admin/assets/js/agent-editor.js') ?>?v=<?= time() ?>"></script>

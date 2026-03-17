<h1>Settings</h1>

<div class="settings-nav">
    <a href="<?= cainty_admin_url('settings') ?>" class="active">General</a>
    <a href="<?= cainty_admin_url('settings/llm-keys') ?>">LLM API Keys</a>
</div>

<?php
$site = cainty_current_site();
?>

<form id="settingsForm" class="settings-form">
    <div class="form-group">
        <label for="set-site-name">Site Name</label>
        <input type="text" id="set-site-name" name="site_name" value="<?= e($site['site_name'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="set-tagline">Tagline</label>
        <input type="text" id="set-tagline" name="site_tagline" value="<?= e($site['site_tagline'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="set-ppp">Posts Per Page</label>
        <input type="number" id="set-ppp" name="posts_per_page" value="<?= e($settings['posts_per_page'] ?? '12') ?>" min="1" max="100">
    </div>

    <div class="form-group">
        <label for="set-excerpt">Default Excerpt Length (characters)</label>
        <input type="number" id="set-excerpt" name="excerpt_length" value="<?= e($settings['excerpt_length'] ?? '200') ?>" min="50" max="1000">
    </div>

    <div class="form-group">
        <label for="set-theme">Theme</label>
        <select id="set-theme" name="theme">
            <option value="default" <?= ($settings['theme'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default (Dark)</option>
        </select>
    </div>

    <button type="button" class="btn btn-accent" id="saveSettingsBtn">Save Settings</button>
</form>

<script>
document.getElementById('saveSettingsBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);

    fetch(CAINTY.adminUrl + '/settings/save', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CAINTY.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams(formData),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.success) {
            btn.textContent = 'Saved!';
            setTimeout(() => { btn.textContent = 'Save Settings'; }, 1500);
        } else {
            btn.textContent = 'Save Settings';
            alert(d.error || 'Save failed');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Save Settings';
        alert('Network error');
    });
});
</script>

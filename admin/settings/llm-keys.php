<h1>LLM API Keys</h1>

<div class="settings-nav">
    <a href="<?= cainty_admin_url('settings') ?>">General</a>
    <a href="<?= cainty_admin_url('settings/llm-keys') ?>" class="active">LLM API Keys</a>
</div>

<p class="text-muted" style="margin-bottom: 20px;">
    Configure API keys for each LLM provider. Keys are stored encrypted.
    You only need keys for the providers you want to use.
</p>

<?php
$configuredProviders = array_column($keys, 'provider');
$providerLabels = [
    'anthropic' => 'Anthropic (Claude)',
    'openai' => 'OpenAI (GPT)',
    'google' => 'Google (Gemini)',
    'deepseek' => 'DeepSeek',
    'xai' => 'xAI (Grok)',
    'ollama' => 'Ollama (Local)',
];
?>

<?php foreach ($providers as $prov): ?>
<?php $isConfigured = in_array($prov, $configuredProviders); ?>
<div class="llm-key-card">
    <h3>
        <?= $providerLabels[$prov] ?? ucfirst($prov) ?>
        <span class="key-status <?= $isConfigured ? 'configured' : 'not-configured' ?>">
            <?= $isConfigured ? 'Configured' : 'Not Set' ?>
        </span>
    </h3>

    <div class="form-row">
        <div class="form-group">
            <label><?= $prov === 'ollama' ? 'Base URL' : 'API Key' ?></label>
            <input type="<?= $prov === 'ollama' ? 'url' : 'password' ?>"
                   id="key-<?= $prov ?>"
                   placeholder="<?= $isConfigured ? '••••••••••••' : ($prov === 'ollama' ? 'http://localhost:11434' : 'Enter API key') ?>"
                   autocomplete="off">
        </div>

        <?php if ($prov === 'ollama'): ?>
        <div class="form-group" style="max-width:200px;">
            <label>Base URL</label>
            <input type="url" id="baseurl-<?= $prov ?>" placeholder="http://localhost:11434">
        </div>
        <?php endif; ?>

        <button class="btn btn-accent" onclick="saveKey('<?= $prov ?>')">Save</button>
        <button class="btn btn-outline" onclick="testKey('<?= $prov ?>')">Test</button>

        <?php if ($isConfigured): ?>
        <button class="btn btn-danger" onclick="removeKey('<?= $prov ?>')">Remove</button>
        <?php endif; ?>
    </div>

    <div id="result-<?= $prov ?>" class="execute-result" style="display:none; margin-top:8px;"></div>
</div>
<?php endforeach; ?>

<?php if (cainty_config('OOMPH_ENABLED') === 'true'): ?>
<div class="llm-key-card">
    <h3>
        Alexiuz Oomph
        <span class="key-status configured">Connected</span>
    </h3>
    <p class="text-muted">
        Oomph is enabled. Users can use Oomph credits for AI operations without configuring individual API keys.
    </p>
</div>
<?php endif; ?>

<script>
function saveKey(provider) {
    const key = document.getElementById('key-' + provider).value;
    const baseUrlEl = document.getElementById('baseurl-' + provider);
    const baseUrl = baseUrlEl ? baseUrlEl.value : '';
    const resultEl = document.getElementById('result-' + provider);

    fetch(CAINTY.adminUrl + '/settings/llm-keys/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': CAINTY.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ provider, api_key: key, base_url: baseUrl }),
    })
    .then(r => r.json())
    .then(d => {
        resultEl.style.display = 'block';
        resultEl.className = 'execute-result ' + (d.success ? 'success' : 'error');
        resultEl.textContent = d.message || d.error;
        if (d.success) setTimeout(() => location.reload(), 1000);
    });
}

function testKey(provider) {
    const resultEl = document.getElementById('result-' + provider);
    resultEl.style.display = 'block';
    resultEl.className = 'execute-result';
    resultEl.textContent = 'Testing connection...';

    fetch(CAINTY.adminUrl + '/settings/llm-keys/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': CAINTY.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ provider }),
    })
    .then(r => r.json())
    .then(d => {
        resultEl.className = 'execute-result ' + (d.success ? 'success' : 'error');
        resultEl.textContent = d.message || d.error;
    });
}

function removeKey(provider) {
    if (!confirm('Remove API key for ' + provider + '?')) return;
    document.getElementById('key-' + provider).value = '';
    saveKey(provider);
}
</script>

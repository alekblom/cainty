<h1>Template Agent</h1>
<p class="text-muted" style="margin-bottom:24px;">Generate customized website content for a client based on a theme template. The AI replaces all demo content with realistic content tailored to the client's business.</p>

<?php if (empty($themes)): ?>
<div style="padding:24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);">
    <p>No themes with demo content found. Themes need a <code>demo/content.json</code> file to be used with the Template Agent.</p>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;">
    <!-- Left: Input Form -->
    <div>
        <h2>Business Details</h2>
        <form id="templateForm">
            <div class="form-group">
                <label for="ta-theme">Theme</label>
                <select id="ta-theme" name="theme" class="form-input">
                    <option value="">-- Select theme --</option>
                    <?php foreach ($themes as $slug => $meta): ?>
                    <option value="<?= e($slug) ?>"><?= e($meta['name']) ?><?= $meta['parent'] ? ' (child of ' . e($meta['parent']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ta-name">Business Name *</label>
                <input type="text" id="ta-name" name="business_name" class="form-input" placeholder="e.g. Ocean's Pearl Restaurant" required>
            </div>

            <div class="form-group">
                <label for="ta-tagline">Tagline</label>
                <input type="text" id="ta-tagline" name="tagline" class="form-input" placeholder="e.g. Fresh seafood and Nordic tradition">
            </div>

            <div class="form-group">
                <label for="ta-address">Address</label>
                <input type="text" id="ta-address" name="address" class="form-input" placeholder="e.g. 123 Main Street, City">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label for="ta-phone">Phone</label>
                    <input type="text" id="ta-phone" name="phone" class="form-input" placeholder="+1 555 000 000">
                </div>
                <div class="form-group">
                    <label for="ta-email">Email</label>
                    <input type="email" id="ta-email" name="email" class="form-input" placeholder="hello@business.com">
                </div>
            </div>

            <div class="form-group">
                <label for="ta-hours">Opening Hours</label>
                <input type="text" id="ta-hours" name="hours" class="form-input" placeholder="Mon-Fri 9-17, Sat 10-14">
            </div>

            <div class="form-group">
                <label for="ta-desc">Additional Business Information</label>
                <textarea id="ta-desc" name="description" rows="4" placeholder="Describe the business, specialties, history, etc. More info = better results."></textarea>
            </div>

            <div class="form-group">
                <label for="ta-provider">AI Model</label>
                <select id="ta-provider" name="provider" class="form-input">
                    <?php
                    $hasProvider = false;
                    foreach ($models as $prov => $provModels):
                        if (in_array($prov, $availableProviders)):
                            $hasProvider = true;
                            foreach ($provModels as $m): ?>
                            <option value="<?= e($prov) ?>|<?= e($m['slug']) ?>"
                                <?= ($prov === 'anthropic' && $m['slug'] === 'claude-sonnet-4-5-20250929') ? 'selected' : '' ?>>
                                <?= e($m['name']) ?>
                            </option>
                    <?php   endforeach;
                        endif;
                    endforeach;
                    if (!$hasProvider): ?>
                    <option value="" disabled>No AI providers configured</option>
                    <?php endif; ?>
                </select>
                <?php if (!$hasProvider): ?>
                <p class="text-muted" style="margin-top:4px;font-size:0.8rem;">Configure an API key in <a href="<?= cainty_admin_url('settings/llm-keys') ?>">Settings > LLM Keys</a> first.</p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-accent" id="generateBtn" <?= !$hasProvider ? 'disabled' : '' ?>>Generate Content</button>
            </div>
        </form>
    </div>

    <!-- Right: Output -->
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2>Generated Content</h2>
            <div id="tokenInfo" class="text-muted" style="font-size:0.8rem;display:none;"></div>
        </div>

        <div id="statusMsg" style="display:none;padding:12px;border-radius:var(--radius);margin-bottom:12px;font-size:0.9rem;"></div>

        <div id="outputWrap" style="position:relative;">
            <textarea id="outputJson" rows="30" style="font-family:'SF Mono',Monaco,Consolas,monospace;font-size:0.8rem;line-height:1.5;resize:vertical;" readonly placeholder="Generated JSON will appear here..."></textarea>
        </div>

        <div class="form-actions" style="margin-top:12px;">
            <button type="button" class="btn btn-accent" id="saveBtn" disabled>Save to Theme</button>
            <button type="button" class="btn btn-secondary" id="copyBtn" disabled>Copy JSON</button>
        </div>
    </div>
</div>

<script>
(function() {
    const generateBtn = document.getElementById('generateBtn');
    const saveBtn = document.getElementById('saveBtn');
    const copyBtn = document.getElementById('copyBtn');
    const outputJson = document.getElementById('outputJson');
    const statusMsg = document.getElementById('statusMsg');
    const tokenInfo = document.getElementById('tokenInfo');

    let currentTheme = '';

    function showStatus(msg, type) {
        statusMsg.style.display = 'block';
        statusMsg.textContent = msg;
        statusMsg.style.background = type === 'error' ? 'rgba(255,68,68,0.1)' : type === 'success' ? 'rgba(68,204,102,0.1)' : 'rgba(255,228,84,0.1)';
        statusMsg.style.border = '1px solid ' + (type === 'error' ? 'var(--danger)' : type === 'success' ? 'var(--success)' : 'var(--accent)');
        statusMsg.style.color = type === 'error' ? 'var(--danger)' : type === 'success' ? 'var(--success)' : 'var(--accent)';
    }

    function hideStatus() {
        statusMsg.style.display = 'none';
    }

    // Generate
    generateBtn.addEventListener('click', function() {
        const theme = document.getElementById('ta-theme').value;
        const name = document.getElementById('ta-name').value.trim();
        const providerModel = document.getElementById('ta-provider').value.split('|');

        if (!theme) { showStatus('Please select a theme first.', 'error'); return; }
        if (!name) { showStatus('Business name is required.', 'error'); return; }

        hideStatus();
        currentTheme = theme;
        generateBtn.disabled = true;
        generateBtn.textContent = 'Generating...';
        outputJson.value = '';
        outputJson.placeholder = 'AI is working... This may take 30-60 seconds.';
        saveBtn.disabled = true;
        copyBtn.disabled = true;
        tokenInfo.style.display = 'none';

        const formData = new URLSearchParams({
            theme: theme,
            business_name: name,
            tagline: document.getElementById('ta-tagline').value,
            address: document.getElementById('ta-address').value,
            phone: document.getElementById('ta-phone').value,
            email: document.getElementById('ta-email').value,
            hours: document.getElementById('ta-hours').value,
            description: document.getElementById('ta-desc').value,
            provider: providerModel[0] || 'anthropic',
            model: providerModel[1] || '',
        });

        fetch(CAINTY.adminUrl + '/template-agent/generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CAINTY.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData,
        })
        .then(r => r.json())
        .then(d => {
            generateBtn.disabled = false;
            generateBtn.textContent = 'Generate Content';

            if (d.success) {
                outputJson.value = JSON.stringify(d.content, null, 4);
                outputJson.readOnly = false;
                saveBtn.disabled = false;
                copyBtn.disabled = false;
                showStatus('Content generated successfully!', 'success');

                if (d.tokens) {
                    tokenInfo.style.display = 'block';
                    tokenInfo.textContent = 'Tokens: ' + d.tokens.input + ' in / ' + d.tokens.output + ' out | ' + Math.round((d.duration_ms || 0) / 1000) + 's';
                }
            } else {
                showStatus(d.error || 'Unknown error', 'error');
                if (d.raw) {
                    outputJson.value = d.raw;
                }
            }
        })
        .catch(err => {
            generateBtn.disabled = false;
            generateBtn.textContent = 'Generate Content';
            showStatus('Network error: ' + err.message, 'error');
        });
    });

    // Save
    saveBtn.addEventListener('click', function() {
        if (!currentTheme || !outputJson.value.trim()) return;

        try {
            JSON.parse(outputJson.value);
        } catch(e) {
            showStatus('Invalid JSON. Fix errors before saving.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch(CAINTY.adminUrl + '/template-agent/save', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CAINTY.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                theme: currentTheme,
                content: outputJson.value,
            }),
        })
        .then(r => r.json())
        .then(d => {
            saveBtn.disabled = false;
            if (d.success) {
                saveBtn.textContent = 'Saved!';
                showStatus('Content saved to themes/' + currentTheme + '/demo/content.json', 'success');
                setTimeout(() => { saveBtn.textContent = 'Save to Theme'; }, 2000);
            } else {
                saveBtn.textContent = 'Save to Theme';
                showStatus(d.error || 'Save failed', 'error');
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save to Theme';
            showStatus('Network error: ' + err.message, 'error');
        });
    });

    // Copy
    copyBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(outputJson.value).then(() => {
            copyBtn.textContent = 'Copied!';
            setTimeout(() => { copyBtn.textContent = 'Copy JSON'; }, 1500);
        });
    });
})();
</script>

<?php endif; ?>

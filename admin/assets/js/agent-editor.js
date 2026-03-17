/**
 * Agent Editor JS
 */
(function() {
    'use strict';

    // --- Model select filtering ---
    const providerSelect = document.getElementById('agent-provider');
    const modelSelect = document.getElementById('agent-model');

    if (providerSelect && modelSelect) {
        function filterModels() {
            const selectedProvider = providerSelect.value;
            const options = modelSelect.querySelectorAll('option');
            let firstVisible = null;
            let currentStillVisible = false;

            options.forEach(opt => {
                const show = opt.dataset.provider === selectedProvider;
                opt.style.display = show ? '' : 'none';
                opt.disabled = !show;
                if (show && !firstVisible) firstVisible = opt;
                if (show && opt.selected) currentStillVisible = true;
            });

            if (!currentStillVisible && firstVisible) {
                firstVisible.selected = true;
            }
        }

        providerSelect.addEventListener('change', filterModels);
        filterModels();
    }

    // --- Auto-slug ---
    const nameInput = document.getElementById('agent-name');
    const slugInput = document.getElementById('agent-slug');
    let slugEdited = !!(slugInput && slugInput.value);

    if (nameInput && slugInput) {
        slugInput.addEventListener('input', function() {
            slugEdited = true;
        });

        nameInput.addEventListener('input', function() {
            if (!slugEdited) {
                slugInput.value = nameInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });
    }

    // --- Save Agent ---
    const saveBtn = document.getElementById('saveAgentBtn');
    const form = document.getElementById('agentForm');

    if (saveBtn && form) {
        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData(form);

            // Checkbox handling
            if (!form.querySelector('[name="is_active"]').checked) {
                formData.set('is_active', '0');
            }

            fetch(CAINTY.adminUrl + '/agents/save', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CAINTY.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(formData),
            })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Agent';

                if (data.success) {
                    saveBtn.textContent = 'Saved!';
                    setTimeout(() => { saveBtn.textContent = 'Save Agent'; }, 1500);
                    if (data.redirect && !form.querySelector('[name="agent_id"]').value) {
                        window.location = data.redirect;
                    }
                } else {
                    alert(data.error || 'Save failed');
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Agent';
                alert('Network error');
            });
        });

        // Ctrl+S
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveBtn.click();
            }
        });
    }

    // --- Execute Agent ---
    const execBtn = document.getElementById('executeAgentBtn');
    const execTopic = document.getElementById('execute-topic');
    const execResult = document.getElementById('executeResult');

    if (execBtn && execTopic) {
        execBtn.addEventListener('click', function() {
            const topic = execTopic.value.trim();
            if (!topic) {
                alert('Enter a topic prompt');
                return;
            }

            const agentId = form.querySelector('[name="agent_id"]').value;
            execBtn.disabled = true;
            execBtn.textContent = 'Running...';
            execResult.style.display = 'block';
            execResult.className = 'execute-result';
            execResult.innerHTML = '<p>Calling LLM... This may take a minute.</p>';

            fetch(CAINTY.adminUrl + '/agents/' + agentId + '/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': CAINTY.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ agent_id: agentId, topic: topic }),
            })
            .then(r => r.json())
            .then(data => {
                execBtn.disabled = false;
                execBtn.textContent = 'Run Agent';

                if (data.success) {
                    execResult.className = 'execute-result success';
                    let html = '<p><strong>Success!</strong></p>';
                    html += '<p>Tokens: ' + (data.tokens?.input || 0) + ' in / ' + (data.tokens?.output || 0) + ' out</p>';
                    html += '<p>Duration: ' + ((data.duration_ms || 0) / 1000).toFixed(1) + 's</p>';
                    if (data.parsed?.title) html += '<p>Title: ' + data.parsed.title + '</p>';
                    html += '<p><a href="' + CAINTY.adminUrl + '/queue">View in Queue &rarr;</a></p>';
                    execResult.innerHTML = html;
                } else {
                    execResult.className = 'execute-result error';
                    let html = '<p><strong>Failed</strong></p>';
                    html += '<p>' + (data.error || 'Unknown error') + '</p>';
                    if (data.validation_errors) {
                        html += '<ul>';
                        data.validation_errors.forEach(e => html += '<li>' + e + '</li>');
                        html += '</ul>';
                    }
                    execResult.innerHTML = html;
                }
            })
            .catch(() => {
                execBtn.disabled = false;
                execBtn.textContent = 'Run Agent';
                execResult.className = 'execute-result error';
                execResult.innerHTML = '<p>Network error</p>';
            });
        });
    }
})();

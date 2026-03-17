/**
 * Cainty Admin — Post Editor
 */

let sourceMode = false;

function execCmd(cmd, value) {
    document.execCommand(cmd, false, value || null);
    document.getElementById('editor-content').focus();
}

function formatBlock(tag) {
    document.execCommand('formatBlock', false, '<' + tag + '>');
    document.getElementById('editor-content').focus();
}

function insertBlockquote() {
    document.execCommand('formatBlock', false, '<blockquote>');
    document.getElementById('editor-content').focus();
}

function insertLink() {
    const url = prompt('Enter URL:');
    if (url) {
        document.execCommand('createLink', false, url);
    }
}

function insertImage() {
    const url = prompt('Enter image URL:');
    if (url) {
        document.execCommand('insertImage', false, url);
    }
}

function toggleSource() {
    const editor = document.getElementById('editor-content');
    const source = document.getElementById('editor-source');
    const btn = document.getElementById('source-toggle');

    if (sourceMode) {
        editor.innerHTML = source.value;
        editor.style.display = 'block';
        source.style.display = 'none';
        btn.style.color = '';
        sourceMode = false;
    } else {
        source.value = editor.innerHTML;
        editor.style.display = 'none';
        source.style.display = 'block';
        btn.style.color = 'var(--accent)';
        sourceMode = true;
    }
}

function getContent() {
    if (sourceMode) {
        return document.getElementById('editor-source').value;
    }
    return document.getElementById('editor-content').innerHTML;
}

// Auto-slug from title
const titleInput = document.getElementById('post-title');
const slugInput = document.getElementById('post-slug');
let slugManuallyEdited = !!slugInput.value;

if (titleInput) {
    titleInput.addEventListener('input', function () {
        if (!slugManuallyEdited) {
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });
}
if (slugInput) {
    slugInput.addEventListener('input', function () {
        slugManuallyEdited = true;
    });
}

// Save post via AJAX
function savePost(forceStatus) {
    const form = document.getElementById('post-form');
    const statusEl = document.getElementById('save-status');
    const data = new FormData(form);

    // Set content from editor
    data.set('content', getContent());

    // Override status if force-saving as draft
    if (forceStatus) {
        data.set('status', forceStatus);
    }

    statusEl.textContent = 'Saving...';
    statusEl.style.color = 'var(--text-muted)';

    fetch(CAINTY.adminUrl + '/posts/save', {
        method: 'POST',
        body: data,
    })
    .then(function (r) { return r.json(); })
    .then(function (result) {
        if (result.success) {
            statusEl.textContent = 'Saved!';
            statusEl.style.color = 'var(--success)';
            // Update URL to edit mode if new post
            if (result.redirect && !data.get('post_id')) {
                window.history.replaceState(null, '', result.redirect);
                // Set post_id for future saves
                const idInput = form.querySelector('[name="post_id"]');
                if (idInput) idInput.value = result.post_id;
            }
            setTimeout(function () { statusEl.textContent = ''; }, 3000);
        } else {
            statusEl.textContent = 'Error: ' + (result.error || 'Unknown error');
            statusEl.style.color = 'var(--danger)';
        }
    })
    .catch(function (err) {
        statusEl.textContent = 'Network error';
        statusEl.style.color = 'var(--danger)';
    });
}

// Featured image preview
const imageInput = document.getElementById('featured-image-input');
if (imageInput) {
    imageInput.addEventListener('change', function () {
        const preview = document.getElementById('featured-image-preview');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="" style="max-width:100%;border-radius:4px;">';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Keyboard shortcut: Ctrl+S to save
document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        savePost();
    }
});

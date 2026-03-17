/**
 * Cainty Admin — General JS
 */

function ajaxPost(url, data, callback) {
    var formData;
    if (data instanceof FormData) {
        formData = data;
    } else {
        formData = new FormData();
        for (var key in data) {
            formData.append(key, data[key]);
        }
    }
    formData.append('_csrf_token', CAINTY.csrfToken);

    fetch(url, { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(callback)
        .catch(function (err) {
            alert('Network error: ' + err.message);
        });
}

// Posts
function deletePost(id) {
    if (!confirm('Are you sure you want to delete this post?')) return;
    ajaxPost(CAINTY.adminUrl + '/posts/' + id + '/delete', {}, function (r) {
        if (r.success) location.reload();
        else alert(r.error || 'Failed to delete');
    });
}

// Categories
function saveCategory(e) {
    e.preventDefault();
    var form = document.getElementById('category-form');
    var data = new FormData(form);
    ajaxPost(CAINTY.adminUrl + '/categories/save', data, function (r) {
        if (r.success) location.reload();
        else alert(r.error || 'Failed to save');
    });
}

function editCategory(cat) {
    document.getElementById('cat-form-title').textContent = 'Edit Category';
    document.getElementById('cat-id').value = cat.category_id;
    document.getElementById('cat-name').value = cat.cat_name;
    document.getElementById('cat-slug').value = cat.cat_slug;
    document.getElementById('cat-parent').value = cat.parent_id || '';
    document.getElementById('cat-desc').value = cat.cat_desc || '';
    document.getElementById('cat-cancel').style.display = 'inline-block';
}

function resetCatForm() {
    document.getElementById('cat-form-title').textContent = 'Add Category';
    document.getElementById('category-form').reset();
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-cancel').style.display = 'none';
}

function deleteCategory(id) {
    if (!confirm('Delete this category?')) return;
    ajaxPost(CAINTY.adminUrl + '/categories/' + id + '/delete', {}, function (r) {
        if (r.success) location.reload();
        else alert(r.error || 'Failed to delete');
    });
}

// Tags
function saveTag(e) {
    e.preventDefault();
    var form = document.getElementById('tag-form');
    var data = new FormData(form);
    ajaxPost(CAINTY.adminUrl + '/tags/save', data, function (r) {
        if (r.success) location.reload();
        else alert(r.error || 'Failed to save');
    });
}

function deleteTag(id) {
    if (!confirm('Delete this tag?')) return;
    ajaxPost(CAINTY.adminUrl + '/tags/' + id + '/delete', {}, function (r) {
        if (r.success) location.reload();
        else alert(r.error || 'Failed to delete');
    });
}

// Media
function uploadMedia(input) {
    if (!input.files || !input.files[0]) return;
    var data = new FormData();
    data.append('file', input.files[0]);
    data.append('_csrf_token', CAINTY.csrfToken);

    fetch(CAINTY.adminUrl + '/media/upload', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (r) {
            if (r.success) location.reload();
            else alert(r.error || 'Upload failed');
        });
}

function copyMediaUrl(url) {
    navigator.clipboard.writeText(url).then(function () {
        alert('URL copied!');
    });
}

function deleteMedia(id) {
    if (!confirm('Delete this media?')) return;
    ajaxPost(CAINTY.adminUrl + '/media/' + id + '/delete', {}, function (r) {
        if (r.success) {
            var el = document.getElementById('media-' + id);
            if (el) el.remove();
        } else {
            alert(r.error || 'Failed to delete');
        }
    });
}

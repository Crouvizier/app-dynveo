// assets/js/admin.js
let currentType = 'sites';
let currentItems = [];
let toolsLoaded = false;
var quill;

document.addEventListener('DOMContentLoaded', () => {
    quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['clean']] }
    });
});

function showSection(id) {
    document.querySelectorAll('.section').forEach(el => el.style.display = 'none');
    document.getElementById(`sec-${id}`).style.display = 'block';
    if(id === 'dashboard') loadDashboard();
    if(id === 'sites') { currentType = 'sites'; loadList('sites'); }
    if(id === 'projects') { currentType = 'projects'; loadList('projects'); }
    if(id === 'users') loadUsers();
    if(id === 'tools') loadTools();
}

async function loadDashboard() {
    const res = await fetch('api.php?action=dashboard_stats');
    const json = await res.json();
    if(json.success) {
        document.getElementById('stat-visits').textContent = json.data.total_visits;
        document.getElementById('stat-sites').textContent = json.data.count_sites;
        document.getElementById('stat-projects').textContent = json.data.count_projects;
    }
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (m) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
}

function safeUrlDisplay(url) {
    const u = String(url ?? '').trim();
    if(!u) return '';
    return u.replace(/^https?:\/\//i, '');
}

function buildAdminItemHTML(item, type) {
    const name = escapeHtml(item.name);
    const url = escapeHtml(item.url);
    const urlDisplay = escapeHtml(safeUrlDisplay(item.url));
    const img = item.image_path ? `<img src="../${escapeHtml(item.image_path)}" alt="">` : '';

    const meta = (() => {
        if(type === 'projects') {
            const badges = [];
            if(item.status) badges.push(`<span class="admin-badge">${escapeHtml(item.status)}</span>`);
            if(item.tech_stack) badges.push(`<span class="admin-badge admin-badge-muted">${escapeHtml(item.tech_stack)}</span>`);
            return badges.length ? `<div class="admin-item-meta">${badges.join('')}</div>` : '';
        }
        if(type === 'sites') {
            const badges = [];
            if(item.cms) badges.push(`<span class="admin-badge admin-badge-muted">${escapeHtml(item.cms)}</span>`);
            if(item.version) badges.push(`<span class="admin-badge admin-badge-muted">${escapeHtml(item.version)}</span>`);
            return badges.length ? `<div class="admin-item-meta">${badges.join('')}</div>` : '';
        }
        return '';
    })();

    return `
        <div class="admin-item">
            <div class="admin-item-left">
                <div class="admin-item-thumb">
                    ${img || '<span style="opacity:.7">📄</span>'}
                </div>
                <div class="admin-item-main">
                    <div class="admin-item-title">${name}</div>
                    ${url ? `<a class="admin-item-url" href="${url}" target="_blank" rel="noopener">${urlDisplay || url}</a>` : ''}
                    ${meta}
                </div>
            </div>
            <div class="admin-item-actions">
                <button class="icon-btn" type="button" title="Éditer" onclick="editItemById(${item.id})">✏️</button>
                <button class="icon-btn icon-btn-danger" type="button" title="Supprimer" onclick="deleteItem(${item.id}, '${type}')">🗑️</button>
            </div>
        </div>
    `;
}

async function loadList(type) {
    const res = await fetch(`api.php?action=list_${type}`);
    const json = await res.json();
    currentItems = json.data;
    const container = document.getElementById(`admin-${type}-list`);

    container.innerHTML = (json.data || []).map(item => buildAdminItemHTML(item, type)).join('');
}

async function loadUsers() {
    const res = await fetch('api.php?action=list_users');
    const json = await res.json();

    document.getElementById('admin-users-list').innerHTML = (json.data || []).map(u => `
        <div class="admin-item">
            <div class="admin-item-left">
                <div class="admin-item-thumb"><span style="opacity:.7">👤</span></div>
                <div class="admin-item-main">
                    <div class="admin-item-title">${escapeHtml(u.username)}</div>
                    <div class="admin-item-meta">
                        <span class="admin-badge admin-badge-muted">${escapeHtml(u.role)}</span>
            </div>
                </div>
            </div>
        </div>
    `).join('');
}

function openModal(type, data = null) {
    currentType = type;
    document.getElementById('crud-modal').classList.add('active');
    document.getElementById('crud-form').reset();
    document.getElementById('inp-id').value = '';
    quill.root.innerHTML = ''; // Reset éditeur

    document.querySelector('.specific-site').style.display = type === 'sites' ? 'block' : 'none';
    document.querySelector('.specific-project').style.display = type === 'projects' ? 'block' : 'none';

    if(data) {
        document.getElementById('inp-id').value = data.id;
        document.getElementById('inp-name').value = data.name;
        document.getElementById('inp-url').value = data.url;
        quill.root.innerHTML = data.description || ''; // Charge le HTML
        document.getElementById('inp-img').value = data.image_path || '';

        if(type === 'sites') {
            document.getElementById('inp-cms').value = data.cms;
            document.getElementById('inp-ver').value = data.version;
        } else {
            document.getElementById('inp-stack').value = data.tech_stack;
            if(data.status) document.getElementById('inp-status').value = data.status;
        }
    }
}

function closeModal() { document.getElementById('crud-modal').classList.remove('active'); }
function editItemById(id) { const item = currentItems.find(i => i.id == id); if(item) openModal(currentType, item); }

document.getElementById('crud-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    document.getElementById('inp-desc').value = quill.root.innerHTML; // Sauvegarde le HTML

    const formData = new FormData(e.target);
    const action = `save_${currentType.slice(0, -1)}`;

    try {
        const res = await fetch(`api.php?action=${action}`, { method: 'POST', body: formData });
        const json = await res.json();
        if(json.success) { closeModal(); loadList(currentType); loadDashboard(); }
        else alert(json.error);
    } catch(e) { console.error(e); }
});

async function deleteItem(id, type) {
    if(!confirm('Supprimer ?')) return;
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);
    const res = await fetch(`api.php?action=delete_${type.slice(0,-1)}`, { method: 'POST', body: formData });
    if((await res.json()).success) loadList(type);
}

async function loadTools() {
    const wrap = document.getElementById('admin-tools-wrap');
    if(!wrap) return;

    if(toolsLoaded) return;

    wrap.innerHTML = `<div class="admin-item"><div class="admin-item-left"><div class="admin-item-thumb">⏳</div><div class="admin-item-main"><div class="admin-item-title">Chargement…</div></div></div></div>`;

    try{
        const res = await fetch('debug-tools.php?partial=1', { credentials: 'same-origin' });
        const html = await res.text();
        wrap.innerHTML = html;
        toolsLoaded = true;
    }catch(e){
        wrap.innerHTML = `<div class="admin-item"><div class="admin-item-left"><div class="admin-item-thumb">❌</div><div class="admin-item-main"><div class="admin-item-title">Erreur de chargement</div><div style="opacity:.85;font-size:.9rem;">${String(e)}</div></div></div></div>`;
    }
}

loadDashboard();
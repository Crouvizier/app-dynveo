// assets/js/admin.js
let currentType = 'sites';
let currentItems = [];
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

async function loadList(type) {
    const res = await fetch(`api.php?action=list_${type}`);
    const json = await res.json();
    currentItems = json.data;
    const container = document.getElementById(`admin-${type}-list`);

    container.innerHTML = json.data.map(item => `
        <div class="table-row">
            <div style="display:flex; align-items:center; gap:1rem;">
                ${item.image_path ? `<img src="../${item.image_path}" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">` : '📄'}
                <div><strong>${item.name}</strong><br><small>${item.url}</small></div>
            </div>
            <div>
                <button class="btn-sm" onclick="editItemById(${item.id})">✏️</button>
                <button class="btn-sm btn-del" onclick="deleteItem(${item.id}, '${type}')">🗑️</button>
            </div>
        </div>
    `).join('');
}

async function loadUsers() {
    const res = await fetch('api.php?action=list_users');
    const json = await res.json();
    document.getElementById('admin-users-list').innerHTML = json.data.map(u =>
        `<div class="table-row">${u.username} (${u.role})</div>`
    ).join('');
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

loadDashboard();
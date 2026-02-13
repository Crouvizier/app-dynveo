// assets/js/admin.js

let currentType = 'sites';
let currentItems = []; // Stockage temporaire des données pour éviter les erreurs de syntaxe HTML

function showSection(id) {
    document.querySelectorAll('.section').forEach(el => el.style.display = 'none');
    document.getElementById(`sec-${id}`).style.display = 'block';
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    // Gestion du click event si présent
    if(event && event.target) event.target.classList.add('active');

    if (id === 'dashboard') loadDashboard();
    if (id === 'sites') { currentType = 'sites'; loadList('sites'); }
    if (id === 'projects') { currentType = 'projects'; loadList('projects'); }
    if (id === 'users') loadUsers();
}

async function loadDashboard() {
    try {
    const res = await fetch('api.php?action=dashboard_stats');
    const json = await res.json();
    if(json.success) {
        document.getElementById('stat-visits').textContent = json.data.total_visits;
        document.getElementById('stat-sites').textContent = json.data.count_sites;
        document.getElementById('stat-projects').textContent = json.data.count_projects;

        document.getElementById('top-sites-list').innerHTML = json.data.top_sites.map(s =>
            `<div class="table-row"><span>${s.name}</span> <span class="badge">${s.clicks} clics</span></div>`
        ).join('');
    }
    } catch(e) { console.error("Erreur dashboard", e); }
}

async function loadList(type) {
    try {
    const res = await fetch(`api.php?action=list_${type}`);
    const json = await res.json();
    const container = document.getElementById(`admin-${type}-list`);

    if(json.success) {
            // 1. On sauvegarde les données en mémoire
            currentItems = json.data;

            // 2. On génère le HTML en utilisant SEULEMENT l'ID (plus de SyntaxError possible)
        container.innerHTML = json.data.map(item => `
            <div class="table-row">
                <div style="display:flex;align-items:center;gap:1rem">
                    ${item.image_path 
                        ? `<img src="../${item.image_path}" style="width:40px;height:40px;border-radius:4px;object-fit:cover">` 
                        : '<span style="font-size:1.5rem">📄</span>'}
                    <div><strong>${item.name}</strong><br><small>${item.url}</small></div>
                </div>
                <div class="actions">
                        <button class="btn-sm" onclick="editItemById(${item.id})">✏️</button>
                    <button class="btn-sm btn-del" onclick="deleteItem(${item.id}, '${type}')">🗑️</button>
                </div>
            </div>
        `).join('');
    }
    } catch(e) { console.error("Erreur liste", e); }
}

async function loadUsers() {
    try {
    const res = await fetch(`api.php?action=list_users`);
    const json = await res.json();
    document.getElementById('admin-users-list').innerHTML = json.data.map(u =>
        `<div class="table-row"><span>${u.username} (${u.role})</span> <span>Dernière connexion: ${u.last_login||'-'}</span></div>`
    ).join('');
    } catch(e) { console.error("Erreur users", e); }
}

// === CRUD ===

function openModal(type, data = null) {
    currentType = type;
    const modal = document.getElementById('crud-modal');
    const form = document.getElementById('crud-form');
    form.reset();
    document.getElementById('inp-id').value = '';

    // Toggle fields
    const siteFields = document.querySelector('.specific-site');
    const projFields = document.querySelector('.specific-project');
    if(siteFields) siteFields.style.display = type === 'sites' ? 'block' : 'none';
    if(projFields) projFields.style.display = type === 'projects' ? 'block' : 'none';

    document.getElementById('modal-title').textContent = data ? `Modifier ${data.name}` : `Ajouter ${type === 'sites' ? 'un Site' : 'un Projet'}`;

    if(data) {
        document.getElementById('inp-id').value = data.id;
        document.getElementById('inp-name').value = data.name;
        document.getElementById('inp-url').value = data.url;
        document.getElementById('inp-desc').value = data.description;
        document.getElementById('inp-img').value = data.image_path || '';

        if(type === 'sites') {
            document.getElementById('inp-cms').value = data.cms;
            document.getElementById('inp-ver').value = data.version;
        } else {
            document.getElementById('inp-stack').value = data.tech_stack;
            if(data.status) {
                const statusSelect = document.querySelector('select[name="status"]');
                if(statusSelect) statusSelect.value = data.status;
            }
        }
    }

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('crud-modal').classList.remove('active');
}

// Nouvelle fonction sûre : Trouve l'item dans la mémoire via son ID
function editItemById(id) {
    const item = currentItems.find(i => i.id == id);
    if (item) {
        openModal(currentType, item);
    } else {
        alert("Erreur : Impossible de retrouver cet élément.");
    }
}

document.getElementById('crud-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const singular = currentType.slice(0, -1);
    const action = `save_${singular}`;

    try {
        const res = await fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if(json.success) {
            closeModal();
            loadList(currentType);
            loadDashboard();
        } else {
            alert('Erreur: ' + (json.error || 'Inconnue'));
        }
    } catch(e) { console.error(e); alert("Erreur réseau"); }
});

async function deleteItem(id, type) {
    if(!confirm('Supprimer définitivement ?')) return;
    const singular = type.slice(0, -1);
    const formData = new FormData();
    formData.append('id', id);
    // Récupération token CSRF depuis le formulaire caché
    const csrf = document.querySelector('[name=csrf_token]').value;
    formData.append('csrf_token', csrf);

    try {
    const res = await fetch(`api.php?action=delete_${singular}`, { method: 'POST', body: formData });
    const json = await res.json();
    if(json.success) loadList(type);
        else alert("Erreur suppression: " + json.error);
    } catch(e) { console.error(e); }
}

// Init
loadDashboard();
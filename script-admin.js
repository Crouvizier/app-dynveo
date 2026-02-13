/**
 * Script Admin Sécurisé
 * Gestion des appels API avec protection CSRF et XSS
 */

let currentTab = 'sites';
let csrfToken = null; // Stockage du jeton de sécurité

// === UTILITAIRES DE SÉCURITÉ ===

// Nettoyage XSS (Indispensable pour empêcher l'injection de code HTML)
function escapeHtml(text) {
    if (!text) return '';
    return text
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Wrapper API centralisé (Gère le CSRF et les erreurs)
async function apiCall(action, method = 'GET', body = null, isJson = false) {
    const url = `api.php?action=${action}`;
    const options = {
        method: method,
        headers: {}
    };

    // Injection du Token CSRF pour toutes les requêtes d'écriture
    if (method !== 'GET' && csrfToken) {
        options.headers['X-CSRF-Token'] = csrfToken;
    }

    if (body) {
        if (isJson) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        } else {
            // Pour FormData, ne pas mettre de Content-Type (le navigateur le gère avec les boundaries)
            options.body = body;
        }
    }

    try {
        const response = await fetch(url, options);

        // Gestion de l'expiration de session (401/403)
        if (response.status === 401 || response.status === 403) {
            window.location.href = 'login.php';
            return { success: false, error: 'Session expirée' };
        }

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Erreur réseau:', error);
        return { success: false, error: 'Erreur de connexion au serveur' };
    }
}

// === INITIALISATION ===

async function init() {
    createParticles();

    // 1. Vérifier l'authentification et récupérer le CSRF Token
    const auth = await apiCall('auth_check');

    if (!auth.success || !auth.logged_in) {
        window.location.href = 'login.php';
        return;
    }

    // On stocke le token pour les futures requêtes
    csrfToken = auth.csrf_token;
    console.log('🔒 Sécurité active. Token reçu.');

    // 2. Charger les données
    loadData('sites');
    loadData('projects');
}

// === GESTION DES DONNÉES ===

async function loadData(type) {
    // API attend 'site' ou 'project' (singulier), mais l'UI utilise le pluriel parfois
    const apiType = type.endsWith('s') ? type.slice(0, -1) : type;

    const result = await apiCall(`list&type=${apiType}`);

    if (result.success) {
        render(type, result.data);
    } else {
        console.error(`Erreur chargement ${type}:`, result.error);
    }
}

function render(type, items) {
    const grid = document.getElementById(type + '-grid');
    if (!grid) return;

    if (items.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">${type === 'sites' ? '🌐' : '🚀'}</div>
                <h3>Aucun ${type === 'sites' ? 'site' : 'projet'} configuré</h3>
                <p>Utilisez le bouton + pour commencer.</p>
            </div>`;
        return;
    }

    grid.innerHTML = items.map(item => createCard(item)).join('');
    initDragDrop(type + '-grid');
}

function createCard(item) {
    // Sécurisation des données avant affichage (XSS Prevention)
    const safeName = escapeHtml(item.name);
    const safeDesc = escapeHtml(item.description);
    const safeCms = escapeHtml(item.cms);
    const safeVer = escapeHtml(item.version || '');
    const safeUrl = escapeHtml(item.url);
    const imageSrc = item.image_path ? escapeHtml(item.image_path) : '';

    // SafeName pour l'attribut onclick (échappement supplémentaire pour JS)
    const jsSafeName = safeName.replace(/'/g, "\\'");

    return `
        <div class="site-card" draggable="true" data-id="${item.id}" data-type="${item.type}">
            <div class="drag-handle" title="Glisser pour réordonner">⠿</div>
            
            <div class="card-preview ${!imageSrc ? 'no-image' : ''}"
                 ${imageSrc ? `onclick="openLightbox('${imageSrc}', '${jsSafeName}')"` : ''}>
                ${imageSrc
        ? `<img src="${imageSrc}" alt="${safeName}">`
        : '🌐'}
                ${imageSrc ? `<div class="card-overlay"><div class="card-zoom-hint">🔍 Agrandir</div></div>` : ''}
            </div>

            <div class="card-content">
                <div class="card-name">${safeName}</div>
                <div class="card-description">${safeDesc}</div>
                <div class="card-meta">
                    <span class="meta-badge">${safeCms}</span>
                    ${safeVer ? `<span class="meta-badge">v${safeVer}</span>` : ''}
                </div>
                
                <button class="visit-btn" onclick="window.open('${safeUrl}', '_blank')">🔗 Visiter</button>
                
                <div class="card-actions">
                    <div class="action-btn" onclick="editSite(${item.id})" title="Modifier">✏️</div>
                    <div class="action-btn" onclick="deleteSite(${item.id})" title="Supprimer">🗑️</div>
                </div>
            </div>
        </div>
    `;
}

// === ACTIONS CRUD ===

async function saveSite(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Sauvegarde...';

    const formData = new FormData(e.target);
    // Ajout explicite du token CSRF dans le body au cas où le header est strippé (parfois utile)
    formData.append('csrf_token', csrfToken);

    const id = document.getElementById('site-id').value;
    const action = id ? 'update' : 'create';

    const result = await apiCall(action, 'POST', formData);

    if (result.success) {
        closeModal();
        loadData('sites');
        loadData('projects');
        showToast('✅ Enregistré avec succès');
    } else {
        alert('Erreur: ' + result.error);
    }

    btn.disabled = false;
    btn.textContent = originalText;
}

async function deleteSite(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) return;

    // Pour delete, on utilise FormData pour passer l'ID
    const formData = new FormData();
    formData.append('id', id);

    const result = await apiCall('delete', 'POST', formData);

    if (result.success) {
        loadData('sites');
        loadData('projects');
        showToast('🗑️ Élément supprimé');
    } else {
        alert('Erreur: ' + result.error);
    }
}

async function editSite(id) {
    // Récupération des détails via API
    const result = await apiCall(`get&id=${id}`);

    if (result.success) {
        openModal(result.data);
    } else {
        alert('Impossible de charger les données: ' + result.error);
    }
}

// === INTERFACE ET EVENEMENTS ===

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');

    document.getElementById('sites-grid').style.display = tab === 'sites' ? 'grid' : 'none';
    document.getElementById('projects-grid').style.display = tab === 'projects' ? 'grid' : 'none';
}

function openModal(data = null) {
    const modal = document.getElementById('modal');
    const form = document.getElementById('site-form');
    form.reset();

    // Reset preview
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('file-name').textContent = 'Aucun fichier sélectionné';
    document.getElementById('site-id').value = '';
    document.getElementById('existing-image').value = '';

    if (data) {
        // Mode Édition
        document.getElementById('modal-title').textContent = `Modifier ${data.type === 'site' ? 'Site' : 'Projet'}`;
        document.getElementById('site-id').value = data.id;
        document.getElementById('site-type').value = data.type; // Garde le type original
        document.getElementById('site-name').value = data.name;
        document.getElementById('site-url').value = data.url;
        document.getElementById('site-description').value = data.description;
        document.getElementById('site-cms').value = data.cms;
        document.getElementById('site-version').value = data.version || '';
        document.getElementById('existing-image').value = data.image_path || '';

        if (data.image_path) {
            document.getElementById('preview-img').src = data.image_path;
            document.getElementById('image-preview').style.display = 'block';
        }
    } else {
        // Mode Création
        const type = currentTab === 'sites' ? 'site' : 'project';
        document.getElementById('modal-title').textContent = `Ajouter un ${type === 'site' ? 'Site' : 'Projet'}`;
        document.getElementById('site-type').value = type;
    }

    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

// Prévisualisation image uploadée
function previewImage(input) {
    const fileName = input.files[0]?.name || 'Aucun fichier sélectionné';
    document.getElementById('file-name').textContent = fileName;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImagePreview() {
    document.getElementById('site-image').value = ''; // Reset input file
    document.getElementById('file-name').textContent = 'Aucun fichier sélectionné';
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('existing-image').value = ''; // Marquer pour suppression coté serveur si nécessaire
}

// === DRAG & DROP & UX ===

function initDragDrop(gridId) {
    const grid = document.getElementById(gridId);
    let dragSrcId = null;

    grid.addEventListener('dragstart', e => {
        const card = e.target.closest('.site-card');
        if(!card) return;
        dragSrcId = card.dataset.id;
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragend', () => {
        document.querySelectorAll('.site-card').forEach(c => c.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom'));
    });

    grid.addEventListener('dragover', e => {
        e.preventDefault();
        const card = e.target.closest('.site-card');
        if (!card || card.dataset.id === dragSrcId) return;

        const rect = card.getBoundingClientRect();
        const mid = rect.top + rect.height / 2;

        document.querySelectorAll('.site-card').forEach(c => c.classList.remove('drag-over-top', 'drag-over-bottom'));

        if (e.clientY < mid) card.classList.add('drag-over-top');
        else card.classList.add('drag-over-bottom');
    });

    grid.addEventListener('drop', async e => {
        e.preventDefault();
        const target = e.target.closest('.site-card');
        if (!target || !dragSrcId || target.dataset.id === dragSrcId) return;

        // Logique visuelle
        const rect = target.getBoundingClientRect();
        const mid = rect.top + rect.height / 2;
        const position = e.clientY < mid ? 'before' : 'after';

        const srcCard = document.querySelector(`.site-card[data-id="${dragSrcId}"]`);
        if (position === 'before') target.parentNode.insertBefore(srcCard, target);
        else target.parentNode.insertBefore(srcCard, target.nextSibling);

        // Sauvegarde de l'ordre
        const ids = Array.from(grid.querySelectorAll('.site-card')).map(c => c.dataset.id);

        // Envoi au serveur (avec JSON cette fois)
        await apiCall('reorder', 'POST', { ids: ids }, true);
        showToast('Ordre mis à jour');
    });
}

function showToast(msg) {
    const t = document.getElementById('order-toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Fond animé
function createParticles() {
    const container = document.getElementById('particles');
    if(!container) return;
    container.innerHTML = '';
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 5 + 's';
        container.appendChild(particle);
    }
}

// Lightbox
function openLightbox(src, title) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-title').textContent = title;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}

// Listeners globaux
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeLightbox(); }});
document.getElementById('modal')?.addEventListener('click', e => { if (e.target.id === 'modal') closeModal(); });

// Démarrage
init();
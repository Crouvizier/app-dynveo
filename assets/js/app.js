// assets/js/app.js

// 1. GESTION DES THÈMES
const themeBtns = document.querySelectorAll('.theme-btn');
const savedTheme = localStorage.getItem('site_theme') || 'dark';

function setTheme(theme) {
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('site_theme', theme);

    // UI Update
    themeBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === theme);
    });
}

// Init Theme
setTheme(savedTheme);
themeBtns.forEach(btn => btn.addEventListener('click', () => setTheme(btn.dataset.theme)));


// 2. ANALYTICS
function track(type, id = null) {
    fetch(`api.php?action=track&type=${type}${id ? '&target_id='+id : ''}`)
        .catch(err => console.error('Tracking error', err));
}

// Track visite homepage
if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/') {
    track('visit');
}


// 3. CHARGEMENT DONNÉES
async function loadContent(type) {
    const grid = document.getElementById(`${type}-grid`);
    if(!grid) return;

    try {
        const res = await fetch(`api.php?action=list_${type}`);
        const json = await res.json();

        if (json.success) {
            if (json.data.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-muted)">Aucun élément disponible</div>';
                return;
            }

            grid.innerHTML = json.data.map(item => `
                <div class="card">
                    <div class="card-img">
                        ${item.image_path
                ? `<img src="${item.image_path}" loading="lazy" alt="${item.name}">`
                : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem">🚀</div>`}
                    </div>
                    <div class="card-body">
                        <div class="card-title">${item.name}</div>
                        <div class="card-desc">${item.description || ''}</div>
                        <div class="card-meta">
                            ${item.cms ? `<span class="badge">${item.cms} ${item.version||''}</span>` : ''}
                            ${item.tech_stack ? `<span class="badge">${item.tech_stack}</span>` : ''}
                            ${item.status ? `<span class="badge status-${item.status}">${item.status}</span>` : ''}
                        </div>
                        <a href="${item.url}" target="_blank" class="btn-visit" 
                           onclick="track('click_${type.slice(0,-1)}', ${item.id})">
                           Visiter
                        </a>
                    </div>
                </div>
            `).join('');
        }
    } catch (e) {
        console.error(e);
    }
}

// Tabs logic
const tabs = document.querySelectorAll('.tab');
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        document.getElementById('sites-grid').style.display = 'none';
        document.getElementById('projects-grid').style.display = 'none';

        const target = tab.dataset.target; // 'sites' or 'projects'
        document.getElementById(`${target}-grid`).style.display = 'grid';

        // Load data if empty
        if(document.getElementById(`${target}-grid`).children.length === 0) {
            loadContent(target);
        }
    });
});

// Init load
loadContent('sites'); // Default tab
loadContent('projects');
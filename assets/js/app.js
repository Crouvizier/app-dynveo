// assets/js/app.js

// Themes & Analytics
const themeBtns = document.querySelectorAll('.theme-btn');
const savedTheme = localStorage.getItem('site_theme') || 'dark';
function setTheme(theme) {
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('site_theme', theme);
    themeBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.theme === theme));
}
setTheme(savedTheme);
themeBtns.forEach(btn => btn.addEventListener('click', () => setTheme(btn.dataset.theme)));

function track(type, id = null) {
    fetch(`api.php?action=track&type=${type}${id ? '&target_id='+id : ''}`).catch(e=>{});
}
if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/') track('visit');

// --- LOAD CONTENT ---
async function loadContent(type) {
    const grid = document.getElementById(`${type}-grid`);
    if(!grid) return;

    try {
        const res = await fetch(`api.php?action=list_${type}`);
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            grid.innerHTML = json.data.map(item => `
                <div class="site-card-wrapper">
                    <div class="site-card">
                        <div class="card-preview" onclick="openLightbox('${item.image_path}', '${item.name.replace(/'/g, "\\'")}')">
                            ${item.image_path
                ? `<img src="${item.image_path}" loading="lazy" alt="${item.name}">`
                : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem">🚀</div>`}
                            <div class="card-overlay">
                                <div class="card-zoom-hint">🔍 Cliquer pour agrandir</div>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="card-name">${item.name}</div>
                            
                            <div class="card-description">${item.description || ''}</div>
                            
                            <div class="card-meta">
                                ${item.cms ? `<span class="meta-badge">${item.cms}</span>` : ''}
                                ${item.version ? `<span class="meta-badge">v${item.version}</span>` : ''}
                                ${item.tech_stack ? `<span class="meta-badge">${item.tech_stack}</span>` : ''}
                                ${item.status ? `<span class="meta-badge status-${item.status}">${item.status}</span>` : ''}
                            </div>

                            <a href="${item.url}" target="_blank" class="visit-btn" 
                               onclick="track('click_${type.slice(0,-1)}', ${item.id})">
                               Visiter le site
                            </a>
                        </div>
                        <!--<div class="card-url">${item.url}</div> -->
                    </div>
                </div>
            `).join('');
        } else {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--text-muted)">Aucun élément disponible.</div>';
        }
    } catch (e) { console.error(e); }
}

const tabs = document.querySelectorAll('.tab');
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('sites-grid').style.display = 'none';
        document.getElementById('projects-grid').style.display = 'none';
        const target = tab.dataset.target;
        document.getElementById(`${target}-grid`).style.display = 'grid';
        if(document.getElementById(`${target}-grid`).children.length === 0) loadContent(target);
    });
});

loadContent('sites');
loadContent('projects');

// --- LIGHTBOX FIX ---
function openLightbox(src, title) {
    if(!src || src === 'null' || src === '') return;
    const lb = document.getElementById('lightbox');
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-title').textContent = title;

    // Reset scroll position
    const scrollContainer = lb.querySelector('.lightbox-scroll');
    if(scrollContainer) scrollContainer.scrollTop = 0;

    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
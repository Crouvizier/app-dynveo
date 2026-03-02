// assets/js/app.js

function escapeHTML(str) {
    if (!str) return "";
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

// Nettoie le HTML en ne gardant que les balises sûres (produites par Quill)
function sanitizeHTML(html) {
    if (!html) return "";
    const allowed = ['P','BR','STRONG','EM','U','S','B','I','UL','OL','LI','A','SPAN','H1','H2','H3','H4','BLOCKQUOTE','PRE','CODE','SUB','SUP'];
    const doc = new DOMParser().parseFromString(html, 'text/html');

    function clean(node) {
        const children = [...node.childNodes];
        children.forEach(child => {
            if (child.nodeType === Node.ELEMENT_NODE) {
                if (!allowed.includes(child.tagName)) {
                    // Remplace la balise interdite par son contenu texte
                    child.replaceWith(document.createTextNode(child.textContent));
                } else {
                    // Nettoyer les attributs dangereux (on garde href pour <a>)
                    [...child.attributes].forEach(attr => {
                        if (attr.name === 'href' && child.tagName === 'A') return;
                        if (attr.name === 'class' || attr.name === 'style') return;
                        child.removeAttribute(attr.name);
                    });
                    // Sécuriser les liens
                    if (child.tagName === 'A') {
                        child.setAttribute('target', '_blank');
                        child.setAttribute('rel', 'noopener noreferrer');
                    }
                    clean(child);
                }
            }
        });
    }
    clean(doc.body);
    return doc.body.innerHTML;
}

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
            grid.innerHTML = json.data.map(item => {
                const safeName = escapeHTML(item.name);
                const safeDesc = sanitizeHTML(item.description);
                const safeCms = escapeHTML(item.cms);
                const safeVersion = escapeHTML(item.version);
                const safeStack = escapeHTML(item.tech_stack);

                return `
                <div class="site-card-wrapper">
                    <div class="site-card">
                        <div class="card-preview" onclick="openLightbox('${item.image_path}', '${safeName.replace(/'/g, "\\'")}')">
                            ${item.image_path
                                ? `<img src="${item.image_path}" loading="lazy" alt="${safeName}">`
                : `<div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem">🚀</div>`}
                            <div class="card-overlay">
                                <div class="card-zoom-hint">🔍 Cliquer pour agrandir</div>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="card-name">${safeName}</div>
                            
                            <div class="card-description">${safeDesc}</div>
                            
                            <div class="card-meta">
                                ${item.cms ? `<span class="meta-badge">${safeCms}</span>` : ''}
                                ${item.version ? `<span class="meta-badge">v${safeVersion}</span>` : ''}
                                ${item.tech_stack ? `<span class="meta-badge">${safeStack}</span>` : ''}
                                ${item.status ? `<span class="meta-badge status-${item.status}">${item.status}</span>` : ''}
                            </div>

                            <a href="${item.url}" target="_blank" class="visit-btn" 
                               onclick="track('click_${type.slice(0,-1)}', ${item.id})">
                               Visiter le site
                            </a>
                        </div>
                    </div>
                </div>
            `}).join('');
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



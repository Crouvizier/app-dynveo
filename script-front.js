        let currentTab = 'sites';

        // === LIGHTBOX ===
        function openLightbox(src, title) {
            const lb = document.getElementById('lightbox');
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox-img').alt = title || '';
            document.getElementById('lightbox-title').textContent = title || '';
            lb.querySelector('.lightbox-scroll').scrollTop = 0;
            lb.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => { document.getElementById('lightbox-img').src = ''; }, 350);
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

                // Initialize
        function init() {
            createParticles();
            loadData('sites');
            loadData('projects');
        }

        // Create particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // Load data from API
        async function loadData(type) {
            try {
                // Construction de l'URL complète pour éviter les problèmes de chemin
                const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
                const apiUrl = baseUrl + 'api.php';
                
                // Convertir le type pluriel en singulier pour l'API
                const apiType = type.endsWith('s') ? type.slice(0, -1) : type;
                
                console.log('[loadData] Type:', type);
                console.log('[loadData] API Type:', apiType);
                console.log('[loadData] API URL:', apiUrl);
                
                const response = await fetch(`${apiUrl}?action=list&type=${apiType}`);
                console.log('[loadData] Response status:', response.status);
                
                const result = await response.json();
                console.log('[loadData] Result:', result);
                
                if (result.success) {
                    render(type, result.data);
                } else {
                    console.error('Erreur API:', result.error);
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        // Render data
        function render(type, items) {
            const grid = document.getElementById(type + '-grid');
            
            if (items.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">${type === 'sites' ? '🌐' : '🚀'}</div>
                        <h3>Aucun ${type === 'sites' ? 'site' : 'projet'} disponible</h3>
                        <p>Revenez plus tard pour découvrir nos ${type === 'sites' ? 'sites' : 'projets'} !</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = items.map(item => createCard(item)).join('');
        }

        // Create card HTML
        function createCard(item) {
            const imageSrc = item.image_path || '';
            const hasImage = imageSrc !== '';
            const safeName = item.name.replace(/'/g, '&#39;');

            return `
                <div class="site-card">
                    <div class="card-preview ${!hasImage ? 'no-image' : ''}"
                         ${hasImage ? `onclick="openLightbox('${imageSrc}', '${safeName}')"` : ''}>
                        ${hasImage ? `<img src="${imageSrc}" alt="${item.name}">` : '🌐'}
                        ${hasImage ? `
                        <div class="card-overlay">
                            <div class="card-zoom-hint">🔍 Cliquer pour agrandir</div>
                        </div>` : ''}
                    </div>
                    <div class="card-content">
                        <div class="card-name">${item.name}</div>
                        <div class="card-description">${item.description}</div>
                        <div class="card-meta">
                            <span class="meta-badge">${item.cms}</span>
                            ${item.version ? `<span class="meta-badge">v${item.version}</span>` : ''}
                        </div>
                        <button class="visit-btn" onclick="window.open('${item.url}', '_blank')">🔗 Visiter le site</button>
                    </div>
                </div>
            `;
        }

        // Switch tabs
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            
            document.getElementById('sites-grid').style.display = tab === 'sites' ? 'grid' : 'none';
            document.getElementById('projects-grid').style.display = tab === 'projects' ? 'grid' : 'none';
        }

        // Initialize
        init();


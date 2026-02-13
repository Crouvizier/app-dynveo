        let currentTab = 'sites';
        let editingId = null;

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
                
                console.log('[ADMIN loadData] Type:', type);
                console.log('[ADMIN loadData] API Type:', apiType);
                console.log('[ADMIN loadData] API URL:', apiUrl);
                
                const response = await fetch(`${apiUrl}?action=list&type=${apiType}`);
                console.log('[ADMIN loadData] Response status:', response.status);
                
                const result = await response.json();
                console.log('[ADMIN loadData] Result:', result);
                
                if (result.success) {
                    render(type, result.data);
                } else {
                    console.error('[ADMIN] Erreur API:', result.error);
                }
            } catch (error) {
                console.error('[ADMIN] Error loading data:', error);
            }
        }

        // Render data
        function render(type, items) {
            const grid = document.getElementById(type + '-grid');
            
            if (items.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">${type === 'sites' ? '🌐' : '🚀'}</div>
                        <h3>Aucun ${type === 'sites' ? 'site' : 'projet'} ajouté</h3>
                        <p>Cliquez sur le bouton + pour ajouter votre premier ${type === 'sites' ? 'site' : 'projet'}</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = items.map(item => createCard(item)).join('');
            initDragDrop(type + '-grid');
        }

        // Create card HTML
        function createCard(item) {
            const imageSrc = item.image_path || '';
            const hasImage = imageSrc !== '';
            const safeName = item.name.replace(/'/g, '&#39;');

            return `
                <div class="site-card" draggable="true" data-id="${item.id}" data-type="${item.type}">
                    <div class="drag-handle" title="Glisser pour réordonner">⠿</div>
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
                        <div class="card-actions">
                            <div class="action-btn" onclick="editSite(${item.id})" title="Modifier">✏️</div>
                            <div class="action-btn" onclick="deleteSite(${item.id}, '${item.type}')" title="Supprimer">🗑️</div>
                        </div>
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

        // Open modal
        async function openModal(id = null) {
            editingId = id;
            const modal = document.getElementById('modal');
            const title = document.getElementById('modal-title');
            const form = document.getElementById('site-form');
            
            form.reset();
            document.getElementById('image-preview').style.display = 'none';
            document.getElementById('file-name').textContent = 'Aucun fichier sélectionné';
            
            if (id) {
                try {
                    const response = await fetch(`api.php?action=get&id=${id}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        const item = result.data;
                        title.textContent = `Modifier ${item.type === 'site' ? 'Site' : 'Projet'}`;
                        document.getElementById('site-id').value = item.id;
                        document.getElementById('site-type').value = item.type;
                        document.getElementById('site-name').value = item.name;
                        document.getElementById('site-url').value = item.url;
                        document.getElementById('site-description').value = item.description;
                        document.getElementById('site-cms').value = item.cms;
                        document.getElementById('site-version').value = item.version || '';
                        document.getElementById('existing-image').value = item.image_path || '';
                        
                        if (item.image_path) {
                            document.getElementById('preview-img').src = item.image_path;
                            document.getElementById('image-preview').style.display = 'block';
                        }
                    }
                } catch (error) {
                    console.error('Error loading item:', error);
                }
            } else {
                title.textContent = currentTab === 'sites' ? 'Ajouter un Site' : 'Ajouter un Projet';
                document.getElementById('site-type').value = currentTab === 'sites' ? 'site' : 'project';
            }
            
            modal.classList.add('active');
        }

        // Close modal
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            editingId = null;
        }

        // Preview image
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

        // Remove image preview
        function removeImagePreview() {
            document.getElementById('site-image').value = '';
            document.getElementById('file-name').textContent = 'Aucun fichier sélectionné';
            document.getElementById('image-preview').style.display = 'none';
            document.getElementById('existing-image').value = '';
        }

        // Save site
        async function saveSite(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';
            
            const formData = new FormData(e.target);
            const id = document.getElementById('site-id').value;
            const action = id ? 'update' : 'create';
            
            try {
                const response = await fetch(`api.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    loadData('sites');
                    loadData('projects');
                } else {
                    alert('Erreur : ' + result.error);
                }
            } catch (error) {
                console.error('Error saving:', error);
                alert('Erreur lors de l\'enregistrement');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enregistrer';
            }
        }

        // Edit site
        function editSite(id) {
            openModal(id);
        }

        // Delete site
        async function deleteSite(id, type) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('id', id);
            
            try {
                const response = await fetch('api.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadData('sites');
                    loadData('projects');
                } else {
                    alert('Erreur : ' + result.error);
                }
            } catch (error) {
                console.error('Error deleting:', error);
                alert('Erreur lors de la suppression');
            }
        }

        // === DRAG & DROP ===
        let dragSrcId = null;

        function initDragDrop(gridId) {
            const grid = document.getElementById(gridId);
            if (!grid) return;
            grid.addEventListener('dragstart', e => {
                const card = e.target.closest('.site-card[data-id]');
                if (!card) return;
                dragSrcId = card.dataset.id;
                card.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            grid.addEventListener('dragend', () => {
                grid.querySelectorAll('.site-card').forEach(c =>
                    c.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom'));
                dragSrcId = null;
            });
            grid.addEventListener('dragover', e => {
                e.preventDefault();
                const card = e.target.closest('.site-card[data-id]');
                if (!card || card.dataset.id === dragSrcId) return;
                grid.querySelectorAll('.site-card').forEach(c =>
                    c.classList.remove('drag-over-top', 'drag-over-bottom'));
                const mid = card.getBoundingClientRect().top + card.getBoundingClientRect().height / 2;
                card.classList.add(e.clientY < mid ? 'drag-over-top' : 'drag-over-bottom');
                e.dataTransfer.dropEffect = 'move';
            });
            grid.addEventListener('dragleave', e => {
                const card = e.target.closest('.site-card[data-id]');
                if (card) card.classList.remove('drag-over-top', 'drag-over-bottom');
            });
            grid.addEventListener('drop', async e => {
                e.preventDefault();
                const target = e.target.closest('.site-card[data-id]');
                if (!target || !dragSrcId || target.dataset.id === dragSrcId) return;
                const srcCard = grid.querySelector(`.site-card[data-id="${dragSrcId}"]`);
                if (!srcCard) return;
                const mid = target.getBoundingClientRect().top + target.getBoundingClientRect().height / 2;
                grid.insertBefore(srcCard, e.clientY < mid ? target : target.nextSibling);
                const ids = Array.from(grid.querySelectorAll('.site-card[data-id]'))
                                  .map(c => parseInt(c.dataset.id));
                await saveOrder(ids);
                grid.querySelectorAll('.site-card').forEach(c =>
                    c.classList.remove('dragging', 'drag-over-top', 'drag-over-bottom'));
            });
        }

        async function saveOrder(ids) {
            try {
                const r = await fetch('api.php?action=reorder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids })
                });
                const res = await r.json();
                if (res.success) showToast('Ordre sauvegardé !');
            } catch(e) { console.error(e); }
        }

        function showToast(msg) {
            const t = document.getElementById('order-toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        }

        // === LIGHTBOX ===
        function openLightbox(src, title) {
            if (dragSrcId !== null) return;
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
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && document.getElementById('lightbox').classList.contains('active'))
                closeLightbox();
        });

                // Close modal on outside click
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Initialize
        init();

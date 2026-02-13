<?php
require_once 'config.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Web - Alexandre</title>
    <link rel="stylesheet" href="style-admin.css">
</head>
<body>
    <div class="particles" id="particles"></div>

    <header>
        <div class="logo">🔧 ADMIN - PORTAIL WEB</div>
        <div class="header-actions">
            <a href="index.php" class="back-btn">← Retour au portail</a>
            <span style="color: var(--text-muted);">👤 <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </header>

    <div class="tabs-container">
        <div class="tab active" data-tab="sites" onclick="switchTab('sites')">Sites Web</div>
        <div class="tab" data-tab="projects" onclick="switchTab('projects')">Projets</div>
    </div>

    <div class="content">
        <div class="grid" id="sites-grid"></div>
        <div class="grid" id="projects-grid" style="display: none;"></div>
    </div>

    <button class="add-site-btn" onclick="openModal()" title="Ajouter un site">+</button>

    <div class="modal" id="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">Ajouter un Site</h2>
                <div class="close-modal" onclick="closeModal()">×</div>
            </div>
            <form id="site-form" onsubmit="saveSite(event)" enctype="multipart/form-data">
                <input type="hidden" id="site-id" name="id">
                <input type="hidden" id="site-type" name="type">
                <input type="hidden" id="existing-image" name="existing_image">
                
                <div class="form-group">
                    <label for="site-name">Nom du Site</label>
                    <input type="text" id="site-name" name="name" required placeholder="Ex: Dynveo">
                </div>

                <div class="form-group">
                    <label for="site-url">URL</label>
                    <input type="url" id="site-url" name="url" required placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="site-description">Description</label>
                    <textarea id="site-description" name="description" required placeholder="Description courte du site..."></textarea>
                </div>

                <div class="form-group">
                    <label>Image d'aperçu</label>
                    <div class="file-input-wrapper">
                        <label class="file-input-label">
                            <span>📁 Choisir une image</span>
                            <span id="file-name">Aucun fichier sélectionné</span>
                            <input type="file" id="site-image" name="image" accept="image/*" onchange="previewImage(this)">
                        </label>
                    </div>
                    <div id="image-preview" class="image-preview" style="display: none;">
                        <img id="preview-img" src="" alt="Aperçu">
                        <button type="button" class="remove-image-btn" onclick="removeImagePreview()">×</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="site-cms">CMS / Technologie</label>
                    <select id="site-cms" name="cms">
                        <option value="PrestaShop">PrestaShop</option>
                        <option value="Shopify">Shopify</option>
                        <option value="WordPress">WordPress</option>
                        <option value="Custom">Custom</option>
                        <option value="React">React</option>
                        <option value="Vue">Vue.js</option>
                        <option value="PHP">PHP</option>
                        <option value="Node.js">Node.js</option>
                        <option value="Python">Python</option>
                        <option value="Other">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="site-version">Version</label>
                    <input type="text" id="site-version" name="version" placeholder="Ex: 1.7.8.10">
                </div>

                <button type="submit" class="submit-btn" id="submit-btn">Enregistrer</button>
            </form>
        </div>
    </div>

    <script src="script-admin.js"></script>
    <div id="order-toast" class="order-toast"></div>

    <!-- LIGHTBOX -->
    <div id="lightbox" class="lightbox">
        <div class="lightbox-backdrop" onclick="closeLightbox()"></div>
        <div class="lightbox-inner">
            <div class="lightbox-header">
                <span class="lightbox-title" id="lightbox-title"></span>
                <button class="lightbox-close" onclick="closeLightbox()" title="Fermer (Échap)">×</button>
            </div>
            <div class="lightbox-scroll">
                <img id="lightbox-img" src="" alt="">
            </div>
            <div class="lightbox-footer">Scroll pour voir la page entière &nbsp;|&nbsp; Clic hors image ou Échap pour fermer</div>
        </div>
    </div>
</body>
</html>

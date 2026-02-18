<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body data-theme="dark">
<div class="container">
    <header class="admin-header">
        <div class="admin-topbar">
            <div class="admin-title">ADMINISTRATION</div>

            <div class="admin-user">
                <div class="admin-user-name">
                    <span class="admin-user-icon">👤</span>
                    <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                </div>

                <div class="admin-actions">
                    <a class="admin-link" href="../index.php">Voir le site</a>
                    <a class="admin-link admin-link-danger" href="../logout.php">Déconnexion</a>
                </div>
            </div>
        </div>

        <nav class="admin-nav admin-nav-top">
            <button class="nav-item active" type="button" onclick="showSection('dashboard')">📊 Dashboard</button>
            <button class="nav-item" type="button" onclick="showSection('sites')">🌐 Sites</button>
            <button class="nav-item" type="button" onclick="showSection('projects')">🧪 Projets</button>
            <button class="nav-item" type="button" onclick="showSection('users')">👥 Admins</button>
            <button class="nav-item" type="button" onclick="showSection('tools')">🛠 Outils</button>
        </nav>
    </header>

    <div id="sec-dashboard" class="section">
        <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 2rem;">
            <div class="stat-card"><div>Visites</div><div class="stat-num" id="stat-visits">-</div></div>
            <div class="stat-card"><div>Sites</div><div class="stat-num" id="stat-sites">-</div></div>
            <div class="stat-card"><div>Projets</div><div class="stat-num" id="stat-projects">-</div></div>
        </div>
    </div>

    <div id="sec-sites" class="section" style="display:none">
        <button class="visit-btn" style="width:auto; margin-bottom:1rem;" onclick="openModal('sites')">+ Ajouter un Site</button>
        <div id="admin-sites-list"></div>
    </div>

    <div id="sec-projects" class="section" style="display:none">
        <button class="visit-btn" style="width:auto; margin-bottom:1rem;" onclick="openModal('projects')">+ Ajouter un Projet</button>
        <div id="admin-projects-list"></div>
    </div>

    <div id="sec-users" class="section" style="display:none">
        <h3>Gestion des Admins</h3>
        <div id="admin-users-list"></div>
    </div>

    <div id="sec-tools" class="section" style="display:none">
        <div id="admin-tools-wrap"></div>
    </div>
</div>

<div id="crud-modal" class="modal">
    <div class="modal-box">
        <h2 id="modal-title" style="margin-bottom:1.5rem; color:var(--accent);">Édition</h2>
        <form id="crud-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" id="inp-id">

            <div class="form-group"><label>Nom</label><input type="text" name="name" id="inp-name" class="form-control" required></div>
            <div class="form-group"><label>URL</label><input type="url" name="url" id="inp-url" class="form-control" required></div>

            <div class="form-group">
                <label>Description (Riche)</label>
                <div id="quill-editor"></div>
                <input type="hidden" name="description" id="inp-desc">
            </div>

            <div class="specific-site">
                <div class="form-group"><label>CMS</label><input type="text" name="cms" id="inp-cms" class="form-control"></div>
                <div class="form-group"><label>Version</label><input type="text" name="version" id="inp-ver" class="form-control"></div>
            </div>

            <div class="specific-project" style="display:none">
                <div class="form-group"><label>Tech Stack</label><input type="text" name="tech_stack" id="inp-stack" class="form-control"></div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="inp-status" class="form-control">
                        <option value="dev">Développement</option>
                        <option value="prod">Production</option>
                        <option value="archived">Archivé</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" class="form-control">
                <input type="hidden" name="existing_image" id="inp-img">
            </div>

            <div style="display:flex; gap:1rem; margin-top:2rem; flex-direction: column;">
                <button type="submit" class="visit-btn">Enregistrer</button>
                <button type="button" class="visit-btn" style="background:grey; border:1px solid #666;" onclick="closeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>



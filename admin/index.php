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
    <style>
        /* Styles Admin Spécifiques (non présents dans le CSS front) */
        .admin-nav { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .nav-item { cursor: pointer; padding: 0.5rem 1rem; color: var(--text-muted); font-weight: bold; }
        .nav-item.active { color: var(--accent); border-bottom: 2px solid var(--accent); }
        .stat-card { background: var(--bg-card); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; }
        .stat-num { font-size: 2.5rem; font-weight: 800; color: var(--accent); margin: 0.5rem 0; }

        /* Modal & Form */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-box { background: var(--bg-dark); padding: 2rem; border-radius: 12px; width: 700px; max-width: 95%; border: 2px solid var(--accent); max-height: 95vh; overflow-y: auto; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--accent); font-weight:bold; }
        .form-control { width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-light); border-radius: 6px; }

        /* Override Quill pour le thème sombre */
        .ql-toolbar { background: #e0e0e0; border-radius: 6px 6px 0 0; }
        .ql-container { background: rgba(255,255,255,0.05); color: white; border: 1px solid var(--border-color) !important; border-radius: 0 0 6px 6px; height: 150px; }

        .table-row { display: flex; justify-content: space-between; padding: 1rem; border-bottom: 1px solid var(--border-color); align-items: center; }
        .btn-sm { padding: 0.3rem 0.6rem; margin-left: 5px; cursor: pointer; border-radius: 4px; border:none; }
        .btn-del { background: #ff4757; color: white; }
    </style>
</head>
<body data-theme="dark">
<div class="container">
    <header>
        <div class="logo">ADMINISTRATION</div>
        <div class="controls">
            <span>👤 <?php echo $_SESSION['username'] ?? 'Admin'; ?></span>
            <a href="../index.php" target="_blank" class="theme-btn">Voir le site</a>
            <a href="../logout.php" class="theme-btn" style="border-color:#ff4757;color:#ff4757">Déconnexion</a>
        </div>
    </header>

    <nav class="admin-nav">
        <div class="nav-item active" onclick="showSection('dashboard')">📊 Dashboard</div>
        <div class="nav-item" onclick="showSection('sites')">🌐 Sites</div>
        <div class="nav-item" onclick="showSection('projects')">🚀 Projets</div>
        <div class="nav-item" onclick="showSection('users')">👥 Admins</div>
        <a href="debug-tools.php" class="nav-item">🛠️ Outils</a>
    </nav>

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
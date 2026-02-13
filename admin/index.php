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
    <style>
        /* Surcharges spécifiques Admin */
        .admin-nav { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
        .nav-item { cursor: pointer; padding: 0.5rem 1rem; color: var(--text-muted); font-weight: bold; }
        .nav-item.active { color: var(--accent); border-bottom: 2px solid var(--accent); }
        .stat-card { background: var(--bg-card); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); text-align: center; }
        .stat-num { font-size: 2.5rem; font-weight: 800; color: var(--accent); margin: 0.5rem 0; }
        .table-row { display: flex; justify-content: space-between; padding: 1rem; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.1); align-items: center; }
        .actions { display: flex; gap: 0.5rem; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; background: var(--border); border: none; color: white; cursor: pointer; border-radius: 4px; }
        .btn-del { background: #ff4757; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-box { background: var(--bg-body); padding: 2rem; border-radius: 12px; width: 500px; max-width: 90%; border: 1px solid var(--accent); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); }
        .form-control { width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: white; border-radius: 6px; }
    </style>
</head>
<body data-theme="dark"> <div class="container">
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
        <div class="grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
            <div class="stat-card">
                <div>Visites Totales</div>
                <div class="stat-num" id="stat-visits">-</div>
            </div>
            <div class="stat-card">
                <div>Sites</div>
                <div class="stat-num" id="stat-sites">-</div>
            </div>
            <div class="stat-card">
                <div>Projets</div>
                <div class="stat-num" id="stat-projects">-</div>
            </div>
        </div>
        <h3>Top 5 Sites consultés</h3>
        <div id="top-sites-list" style="margin-top:1rem"></div>
    </div>

    <div id="sec-sites" class="section" style="display:none">
        <button class="btn-visit" style="width:auto; margin-bottom:1rem;" onclick="openModal('sites')">+ Ajouter un Site</button>
        <div id="admin-sites-list"></div>
    </div>

    <div id="sec-projects" class="section" style="display:none">
        <button class="btn-visit" style="width:auto; margin-bottom:1rem;" onclick="openModal('projects')">+ Ajouter un Projet</button>
        <div id="admin-projects-list"></div>
    </div>

    <div id="sec-users" class="section" style="display:none">
        <div class="card" style="padding: 1rem; border-color: #ff4757;">
            <h3>⚠️ Gestion des Administrateurs</h3>
            <p>Pour ajouter un admin, utilisez la base de données ou créez un formulaire sécurisé ici.</p>
            <div id="admin-users-list" style="margin-top:1rem"></div>
        </div>
    </div>

</div>

<div id="crud-modal" class="modal">
    <div class="modal-box">
        <h2 id="modal-title" style="margin-bottom:1.5rem">Éditer</h2>
        <form id="crud-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" id="inp-id">

            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="name" id="inp-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="url" name="url" id="inp-url" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="inp-desc" class="form-control"></textarea>
            </div>

            <div class="specific-site">
                <div class="form-group"><label>CMS</label><input type="text" name="cms" id="inp-cms" class="form-control"></div>
                <div class="form-group"><label>Version</label><input type="text" name="version" id="inp-ver" class="form-control"></div>
            </div>

            <div class="specific-project" style="display:none">
                <div class="form-group"><label>Tech Stack</label><input type="text" name="tech_stack" id="inp-stack" class="form-control"></div>
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
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

            <div style="display:flex; gap:1rem; margin-top:2rem">
                <button type="submit" class="btn-visit">Enregistrer</button>
                <button type="button" class="btn-visit" style="background:grey" onclick="closeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
</body>
</html>
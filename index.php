<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Web - Alexandre</title>
    <link rel="stylesheet" href="style-front.css">
</head>
<body>
    <div class="particles" id="particles"></div>

    <header>
        <div class="logo">PORTAIL WEB</div>
        <a href="admin.php" class="admin-btn">
            <span>🔧</span>
            <span>Administration</span>
        </a>
    </header>

    <div class="tabs-container">
        <div class="tab active" data-tab="sites" onclick="switchTab('sites')">Sites Web</div>
        <div class="tab" data-tab="projects" onclick="switchTab('projects')">Projets</div>
    </div>

    <div class="content">
        <div class="grid" id="sites-grid"></div>
        <div class="grid" id="projects-grid" style="display: none;"></div>
    </div>

    <script src="script-front.js"></script>
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



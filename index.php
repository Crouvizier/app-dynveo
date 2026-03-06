<?php require 'templates/header.php'; ?>

<div class="tabs">
    <div class="tab active" data-target="projects" onclick="switchTab('projects')">Outils</div>
    <div class="tab" data-target="sites" onclick="switchTab('sites')">Sites Web</div>
</div>

<main>
    <div id="projects-grid" class="grid"></div>
    <div id="sites-grid" class="grid" style="display:none"></div>
</main>

<div id="lightbox" class="lightbox">
    <div class="lightbox-backdrop" onclick="closeLightbox()"></div>
    <div class="lightbox-inner">
        <div class="lightbox-header">
            <span class="lightbox-title" id="lightbox-title"></span>
            <button class="lightbox-close" onclick="closeLightbox()">×</button>
        </div>
        <div class="lightbox-scroll">
            <img id="lightbox-img" src="" alt="">
        </div>
        <div class="lightbox-footer">Scroll pour voir la page entière | Clic hors image ou Échap pour fermer</div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body></html>
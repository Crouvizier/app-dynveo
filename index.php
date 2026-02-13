<?php require 'templates/header.php'; ?>

    <div class="tabs">
        <div class="tab active" data-target="sites">Sites Web</div>
        <div class="tab" data-target="projects">Projets en cours</div>
    </div>

    <main>
        <div id="sites-grid" class="grid"></div>
        <div id="projects-grid" class="grid" style="display:none"></div>
    </main>

    <script src="assets/js/app.js"></script>

<?php
// Pas de footer complexe nécessaire, juste la fermeture
echo '</div></body></html>';
?>
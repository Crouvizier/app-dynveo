<?php
// admin/debug-tools.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin(); // SÉCURITÉ ABSOLUE
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Outils Admin</title>
    <style>
        body { background: #111; color: #eee; font-family: monospace; padding: 2rem; }
        .card { border: 1px solid #333; padding: 1rem; margin-bottom: 1rem; background: #222; }
        .ok { color: #90EE90; } .err { color: #ff6b6b; }
        h2 { border-bottom: 1px solid #555; padding-bottom: 0.5rem; }
    </style>
</head>
<body>
<h1>🛠️ Outils de Diagnostic (Admin Only)</h1>

<div class="card">
    <h2>1. Test Base de Données</h2>
    <?php
    try {
        $pdo = getDB();
        echo "<div class='ok'>✅ Connexion PDO : OK</div>";
        echo "<div>Base : " . DB_NAME . "</div>";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<div>Tables trouvées : " . implode(', ', $tables) . "</div>";
    } catch (Exception $e) {
        echo "<div class='err'>❌ Erreur : " . $e->getMessage() . "</div>";
    }
    ?>
</div>

<div class="card">
    <h2>2. Permissions Dossiers</h2>
    <?php
    $uploadDir = __DIR__ . '/../uploads';
    if (is_writable($uploadDir)) {
        echo "<div class='ok'>✅ Uploads : Écriture permise ($uploadDir)</div>";
    } else {
        echo "<div class='err'>❌ Uploads : Non inscriptible ! (Faites un chmod 755 ou 777)</div>";
    }
    ?>
</div>

<div class="card">
    <h2>3. Info PHP (Extrait)</h2>
    <div>Version PHP: <?= phpversion() ?></div>
    <div>Upload Max Size: <?= ini_get('upload_max_filesize') ?></div>
    <div>Post Max Size: <?= ini_get('post_max_size') ?></div>
</div>

<a href="index.php" style="color: #90EE90">← Retour Dashboard</a>
</body>
</html>
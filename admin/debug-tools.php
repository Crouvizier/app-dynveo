<?php
// admin/debug-tools.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$isPartial = isset($_GET['partial']) && $_GET['partial'] == '1';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

ob_start();
?>
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin: 0 0 1rem 0;">
  <h3 style="margin:0; color:var(--accent); text-shadow: var(--glow);">🛠️ Outils de Diagnostic (Admin Only)</h3>
  <button class="visit-btn" style="width:auto;" type="button" onclick="showSection('dashboard')">← Retour Dashboard</button>
        </div>

        <div class="admin-item" style="align-items:flex-start;">
            <div class="admin-item-left" style="align-items:flex-start;">
                <div class="admin-item-thumb" style="font-size:18px;">🗄️</div>
                <div class="admin-item-main">
                    <div class="admin-item-title">1. Test Base de Données</div>
                    <div style="margin-top:.35rem; color: rgba(255,255,255,.85); font-size:.9rem; line-height:1.5;">
                        <?php
                        try {
                            $pdo = getDB();
                            echo "<div style='color: var(--accent); font-weight:800;'>✅ Connexion PDO : OK</div>";
            echo "<div>Base : " . h(DB_NAME) . "</div>";
                            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<div>Tables trouvées : " . h(implode(', ', $tables)) . "</div>";
                        } catch (Exception $e) {
            echo "<div style='color:#ff6b6b; font-weight:800;'>❌ Erreur : " . h($e->getMessage()) . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-item" style="align-items:flex-start;">
            <div class="admin-item-left" style="align-items:flex-start;">
                <div class="admin-item-thumb" style="font-size:18px;">📁</div>
                <div class="admin-item-main">
                    <div class="admin-item-title">2. Permissions Dossiers</div>
                    <div style="margin-top:.35rem; color: rgba(255,255,255,.85); font-size:.9rem; line-height:1.5;">
                        <?php
                        $uploadDir = __DIR__ . '/../uploads';
                        if (is_writable($uploadDir)) {
                            echo "<div style='color: var(--accent); font-weight:800;'>✅ Uploads : Écriture permise</div>";
            echo "<div style='opacity:.85; font-size:.85rem;'>" . h($uploadDir) . "</div>";
                        } else {
                            echo "<div style='color:#ff6b6b; font-weight:800;'>❌ Uploads : Non inscriptible</div>";
            echo "<div style='opacity:.85; font-size:.85rem;'>(" . h($uploadDir) . ")</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-item" style="align-items:flex-start;">
            <div class="admin-item-left" style="align-items:flex-start;">
                <div class="admin-item-thumb" style="font-size:18px;">🐘</div>
                <div class="admin-item-main">
                    <div class="admin-item-title">3. Info PHP (Extrait)</div>
                    <div style="margin-top:.35rem; color: rgba(255,255,255,.85); font-size:.9rem; line-height:1.6;">
        <div>Version PHP : <strong><?= h(phpversion()) ?></strong></div>
        <div>Upload Max Size : <strong><?= h(ini_get('upload_max_filesize')) ?></strong></div>
        <div>Post Max Size : <strong><?= h(ini_get('post_max_size')) ?></strong></div>
      </div>
                    </div>
                </div>
            </div>
<?php
$content = ob_get_clean();

if ($isPartial) {
    header('Content-Type: text/html; charset=UTF-8');
    echo $content;
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Outils Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-theme="dark">
  <div class="container">
    <header class="admin-header" style="margin-bottom:1rem;">
      <div class="admin-topbar">
        <div class="admin-title">ADMINISTRATION</div>
        <div class="admin-actions">
          <a class="admin-link" href="index.php">← Retour Dashboard</a>
        </div>
    </div>
    </header>
    <div class="section" style="display:block">
      <?= $content ?>
    </div>
</div>
</body>
</html>

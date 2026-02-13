<?php
// TEST DIRECT API - Sans JavaScript
require_once 'config.php';

echo "<html><head><meta charset='UTF-8'><title>Test Direct API</title>";
echo "<style>body{font-family:Arial;padding:2rem;background:#1e3a5f;color:white;}";
echo "pre{background:rgba(0,0,0,0.3);padding:1rem;border-radius:5px;overflow:auto;}";
echo ".success{color:#90EE90;} .error{color:#ff6b6b;}</style></head><body>";

echo "<h1>🔍 TEST DIRECT API (sans JavaScript)</h1>";
echo "<hr>";

// Test 1: Connexion BDD
echo "<h2>Test 1: Connexion à la base de données</h2>";
try {
    $pdo = getDBConnection();
    echo "<p class='success'>✅ Connexion réussie</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Requête directe SQL
echo "<hr><h2>Test 2: Requête SQL directe</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM sites WHERE type = 'site' ORDER BY created_at DESC");
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Requête réussie - " . count($sites) . " site(s) trouvé(s)</p>";
    
    if (count($sites) > 0) {
        echo "<pre>" . print_r($sites, true) . "</pre>";
    } else {
        echo "<p class='error'>⚠️ Aucun site dans la table</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur SQL: " . $e->getMessage() . "</p>";
}

// Test 3: Simulation appel API
echo "<hr><h2>Test 3: Simulation de l'appel API (comme le fait JavaScript)</h2>";

// Simuler $_GET
$_GET['action'] = 'list';
$_GET['type'] = 'site';

// Capturer la sortie de l'API
ob_start();
include 'api.php';
$apiOutput = ob_get_clean();

echo "<p><strong>Réponse de l'API:</strong></p>";
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";

// Essayer de parser le JSON
$decoded = json_decode($apiOutput, true);
if ($decoded) {
    echo "<p class='success'>✅ JSON valide</p>";
    echo "<p>Success: " . ($decoded['success'] ? 'true' : 'false') . "</p>";
    echo "<p>Nombre de sites: " . (isset($decoded['data']) ? count($decoded['data']) : 0) . "</p>";
    
    if (isset($decoded['data']) && count($decoded['data']) > 0) {
        echo "<h3>Détails des sites:</h3>";
        foreach ($decoded['data'] as $site) {
            echo "<div style='background:rgba(255,255,255,0.1);margin:10px 0;padding:10px;border-radius:5px;'>";
            echo "<strong>ID:</strong> {$site['id']}<br>";
            echo "<strong>Nom:</strong> {$site['name']}<br>";
            echo "<strong>URL:</strong> {$site['url']}<br>";
            echo "<strong>Type:</strong> {$site['type']}<br>";
            echo "<strong>CMS:</strong> {$site['cms']}<br>";
            echo "<strong>Image:</strong> " . ($site['image_path'] ?? 'Aucune') . "<br>";
            echo "</div>";
        }
    }
} else {
    echo "<p class='error'>❌ JSON invalide ! Erreur: " . json_last_error_msg() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Conclusion</h2>";
echo "<p>Si tous les tests ci-dessus sont ✅ mais que index.php n'affiche rien, c'est un problème JavaScript.</p>";
echo "<p><a href='index-debug.php' style='color:#90EE90'>→ Tester avec index-debug.php</a></p>";
echo "<p><a href='index.php' style='color:#90EE90'>→ Retour à index.php</a></p>";

echo "</body></html>";
?>

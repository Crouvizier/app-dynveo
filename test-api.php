<?php
// TEST API - Fichier de diagnostic
// Accédez à ce fichier dans votre navigateur pour tester l'API

echo "<h1>Test de l'API - Portail Web</h1>";
echo "<hr>";

// Test 1: Connexion à la base de données
echo "<h2>Test 1: Connexion à la BDD</h2>";
try {
    require_once 'config.php';
    $pdo = getDBConnection();
    echo "✅ <strong>Connexion réussie!</strong><br>";
    echo "Base de données: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "❌ <strong>Erreur de connexion:</strong> " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Comptage des sites
echo "<hr><h2>Test 2: Comptage des sites dans la BDD</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sites");
    $result = $stmt->fetch();
    echo "✅ <strong>Nombre total de sites:</strong> " . $result['total'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sites WHERE type = 'site'");
    $result = $stmt->fetch();
    echo "✅ <strong>Sites web:</strong> " . $result['total'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sites WHERE type = 'project'");
    $result = $stmt->fetch();
    echo "✅ <strong>Projets:</strong> " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ <strong>Erreur:</strong> " . $e->getMessage() . "<br>";
}

// Test 3: Affichage des sites
echo "<hr><h2>Test 3: Liste des sites</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM sites ORDER BY created_at DESC");
    $sites = $stmt->fetchAll();
    
    if (count($sites) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #1e3a5f; color: white;'>";
        echo "<th>ID</th><th>Type</th><th>Nom</th><th>URL</th><th>Description</th><th>Image</th><th>CMS</th><th>Version</th></tr>";
        
        foreach ($sites as $site) {
            echo "<tr>";
            echo "<td>" . $site['id'] . "</td>";
            echo "<td>" . $site['type'] . "</td>";
            echo "<td><strong>" . $site['name'] . "</strong></td>";
            echo "<td><a href='" . $site['url'] . "' target='_blank'>" . $site['url'] . "</a></td>";
            echo "<td>" . substr($site['description'], 0, 50) . "...</td>";
            echo "<td>" . ($site['image_path'] ? '✅ Oui' : '❌ Non') . "</td>";
            echo "<td>" . $site['cms'] . "</td>";
            echo "<td>" . ($site['version'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ <strong>Aucun site trouvé dans la base de données</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>Erreur:</strong> " . $e->getMessage() . "<br>";
}

// Test 4: Test de l'API
echo "<hr><h2>Test 4: Appel API direct</h2>";
echo "<p>Cliquez sur les liens ci-dessous pour tester l'API:</p>";
echo "<ul>";
echo "<li><a href='api.php?action=list&type=site' target='_blank'>api.php?action=list&type=site</a> (devrait retourner du JSON)</li>";
echo "<li><a href='api.php?action=list&type=project' target='_blank'>api.php?action=list&type=project</a> (devrait retourner du JSON)</li>";
echo "</ul>";

echo "<hr><h2>Test 5: Simulation de l'appel JavaScript</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api.php?action=list&type=site");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>URL appelée:</strong> http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api.php?action=list&type=site</p>";
echo "<p><strong>Code HTTP:</strong> " . $httpCode . "</p>";
echo "<p><strong>Réponse:</strong></p>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; overflow: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ <strong>API fonctionne correctement!</strong><br>";
        echo "<p>Nombre de sites retournés: " . count($data['data']) . "</p>";
    } else {
        echo "❌ <strong>L'API retourne une erreur ou des données invalides</strong><br>";
    }
} else {
    echo "❌ <strong>Erreur HTTP " . $httpCode . "</strong><br>";
}

echo "<hr>";
echo "<h2>📋 Diagnostic</h2>";
echo "<p>Si tous les tests ci-dessus sont ✅, mais que les sites ne s'affichent toujours pas dans l'interface:</p>";
echo "<ol>";
echo "<li>Ouvrez la <strong>Console JavaScript</strong> de votre navigateur (F12 → Console)</li>";
echo "<li>Rechargez <strong>index.php</strong> et vérifiez les erreurs JavaScript</li>";
echo "<li>Vérifiez l'onglet <strong>Network</strong> pour voir si l'appel à api.php réussit</li>";
echo "<li>Vérifiez que le dossier <strong>/uploads/</strong> existe et a les bonnes permissions</li>";
echo "</ol>";
?>

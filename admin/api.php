<?php
// admin/api.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/analytics.php';

header('Content-Type: application/json');

// 1. Vérification Auth & Session
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax',
]);
if (!isAdmin()) {
    http_response_code(401);
    jsonResponse(false, null, 'Non autorisé');
}

$pdo = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 2. Protection CSRF pour les écritures
if ($method === 'POST') {
    validateCSRF();
}

try {
    switch ($action) {

        // === DASHBOARD & STATS ===
        case 'dashboard_stats':
            $data = Analytics::getStats();
            // Ajout des compteurs
            $data['count_sites'] = $pdo->query("SELECT COUNT(*) FROM sites")->fetchColumn();
            $data['count_projects'] = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
            jsonResponse(true, $data);
            break;

        // === GESTION DES SITES ===
        case 'list_sites':
            $stmt = $pdo->query("SELECT * FROM sites ORDER BY sort_order ASC, created_at DESC");
            jsonResponse(true, $stmt->fetchAll());
            break;

        case 'save_site':
            $id = $_POST['id'] ?? null;
            $isUpdate = !empty($id);

            // 1. Gestion Image
            // Par défaut, on garde l'image existante (cachée dans le champ existing_image)
            $imagePath = $_POST['existing_image'] ?? null;
            if (empty($imagePath)) $imagePath = null; // Assure que c'est NULL si vide

            // Si une NOUVELLE image est envoyée, on tente l'upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $up = secureUpload($_FILES['image']);
                if ($up['success']) {
                    $imagePath = $up['path']; // On remplace par le nouveau chemin
                } else {
                    jsonResponse(false, null, $up['error']); // On stop tout si erreur upload
                }
            }

            // 2. Sauvegarde BDD
            if ($isUpdate) {
                $sql = "UPDATE sites SET name=?, url=?, description=?, cms=?, version=?, image_path=? WHERE id=?";
                $params = [$_POST['name'], $_POST['url'], $_POST['description'], $_POST['cms'], $_POST['version'], $imagePath, $id];
            } else {
                $sql = "INSERT INTO sites (name, url, description, cms, version, image_path) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$_POST['name'], $_POST['url'], $_POST['description'], $_POST['cms'], $_POST['version'], $imagePath];
            }

            $pdo->prepare($sql)->execute($params);
            jsonResponse(true);
            break;

        case 'save_project':
            $id = $_POST['id'] ?? null;
            $isUpdate = !empty($id);

            // Même logique image
            $imagePath = $_POST['existing_image'] ?? null;
            if (empty($imagePath)) $imagePath = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $up = secureUpload($_FILES['image']);
                if ($up['success']) {
                    $imagePath = $up['path'];
                } else {
                    jsonResponse(false, null, $up['error']);
                }
            }

            if ($isUpdate) {
                $sql = "UPDATE projects SET name=?, url=?, description=?, tech_stack=?, status=?, image_path=? WHERE id=?";
                $params = [$_POST['name'], $_POST['url'], $_POST['description'], $_POST['tech_stack'], $_POST['status'], $imagePath, $id];
            } else {
                $sql = "INSERT INTO projects (name, url, description, tech_stack, status, image_path) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$_POST['name'], $_POST['url'], $_POST['description'], $_POST['tech_stack'], $_POST['status'], $imagePath];
            }

            $pdo->prepare($sql)->execute($params);
            jsonResponse(true);
            break;

        case 'delete_site':
            $id = $_POST['id'];
            // Suppression image
            $img = $pdo->query("SELECT image_path FROM sites WHERE id=$id")->fetchColumn();
            if ($img && file_exists(__DIR__ . '/../' . $img)) unlink(__DIR__ . '/../' . $img);

            $pdo->prepare("DELETE FROM sites WHERE id=?")->execute([$id]);
            jsonResponse(true);
            break;

        // === GESTION DES PROJETS (Table séparée !) ===
        case 'list_projects':
            $stmt = $pdo->query("SELECT * FROM projects ORDER BY sort_order ASC, created_at DESC");
            jsonResponse(true, $stmt->fetchAll());
            break;

        case 'delete_project':
            $id = $_POST['id'];
            $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
            jsonResponse(true);
            break;

        // === GESTION DES ADMINS (Utilisateurs) ===
        case 'list_users':
            // On ne renvoie jamais les mots de passe !
            $stmt = $pdo->query("SELECT id, username, role, last_login FROM users ORDER BY created_at DESC");
            jsonResponse(true, $stmt->fetchAll());
            break;

        case 'save-admin':
            $id = $_POST['id'] ?? null;
            $username = htmlspecialchars($_POST['username']);
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

            if (!$email) {
                echo json_encode(['success' => false, 'error' => 'Format d\'email invalide']);
                exit;
            }

            $params = [$username, $email];
            $sql = "UPDATE admins SET username = ?, email = ?";

            // Modification optionnelle du mot de passe
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            // Gestion de la photo de profil (réutilise votre fonction uploadImage)
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['photo']);
                if ($uploadResult['success']) {
                    $sql .= ", photo_path = ?";
                    $params[] = $uploadResult['path'];
                }
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true]);
            break;

        case 'delete-admin':
            $id = (int)($_POST['id'] ?? 0);

            // Sécurité Critique : Empêcher de supprimer le dernier admin
            $check = $pdo->query("SELECT COUNT(*) FROM admins");
            if ($check->fetchColumn() <= 1) {
                echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le dernier administrateur.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            jsonResponse(false, null, "Action inconnue");
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, "Erreur serveur: " . $e->getMessage());
}
?>
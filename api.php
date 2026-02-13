<?php
/**
 * API Sécurisée - Version 2.0
 * Protection CSRF, Validation stricte, Upload sécurisé
 */
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Fonctions de Sécurité
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function validateCSRF() {
    // On récupère le token envoyé par le header HTTP (pratique standard en AJAX)
    $headers = apache_request_headers();
    $token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';

    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        sendError("Échec de sécurité (Token CSRF invalide). Veuillez rafraîchir la page.", 403);
    }
}

function secureUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur transfert fichier'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Fichier trop lourd (Max 5Mo)'];
    }

    // Vérification stricte du type MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Format non autorisé (JPG, PNG, WEBP uniquement)'];
    }

    // Création dossier
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Génération nom sécurisé (Hash aléatoire + extension propre)
    $extension = array_search($mimeType, [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ], true);

    if (!$extension) $extension = 'jpg'; // Fallback

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => UPLOAD_URL . $filename];
    }

    return ['success' => false, 'error' => 'Erreur sauvegarde serveur'];
}

// 2. Gestion de la requête
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Les actions de lecture sont publiques (selon ton ancien code),
// les actions d'écriture nécessitent auth + CSRF
$publicActions = ['list', 'get'];

try {
    $pdo = getDBConnection();

    // Authentification pour les actions sensibles
    if (!in_array($action, $publicActions)) {
        requireAuth(); // Vérifie si logué

        // Protection CSRF pour toute action qui modifie les données
        if ($method === 'POST') {
            validateCSRF();
        }
    }

    switch ($action) {
        case 'list':
            $type = $_GET['type'] ?? 'site';
            // Validation whitelist pour le type
            if (!in_array($type, ['site', 'project'])) $type = 'site';

            // Note: On ajoute sort_order s'il existe dans ta BDD, sinon on retire
            // Pour l'instant on garde created_at pour être sûr que ça marche avec ta structure actuelle
            $stmt = $pdo->prepare("SELECT * FROM sites WHERE type = ? ORDER BY created_at ASC");
            $stmt->execute([$type]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'get':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) sendError("ID invalide");

            $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if ($item) echo json_encode(['success' => true, 'data' => $item]);
            else sendError("Non trouvé", 404);
            break;

        case 'create':
        case 'update':
            // Validation des entrées
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? 'site';

            if (empty($name) || empty($url)) sendError("Nom et URL requis");
            if (!filter_var($url, FILTER_VALIDATE_URL)) sendError("URL invalide");

            $imagePath = null;

            // Gestion de l'image
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = secureUpload($_FILES['image']);
                if (!$upload['success']) sendError($upload['error']);
                $imagePath = $upload['path'];
            } elseif ($action === 'update' && isset($_POST['existing_image'])) {
                // On garde l'ancienne image si pas de nouvelle
                $imagePath = $_POST['existing_image'];
            }

            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO sites (type, name, url, description, image_path, cms, version, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$type, $name, $url, $description, $imagePath, $_POST['cms']??'', $_POST['version']??'']);
            } else {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if (!$id) sendError("ID manquant");

                // Si nouvelle image, on pourrait supprimer l'ancienne ici (optionnel)

                $stmt = $pdo->prepare("UPDATE sites SET name=?, url=?, description=?, image_path=?, cms=?, version=?, type=? WHERE id=?");
                $stmt->execute([$name, $url, $description, $imagePath, $_POST['cms']??'', $_POST['version']??'', $type, $id]);
            }

            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) sendError("ID invalide");

            // On récupère le chemin pour supprimer le fichier image (nettoyage)
            $stmt = $pdo->prepare("SELECT image_path FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $site = $stmt->fetch();

            if ($site && !empty($site['image_path'])) {
                $fileToDelete = __DIR__ . '/' . $site['image_path'];
                if (file_exists($fileToDelete) && is_file($fileToDelete)) {
                    unlink($fileToDelete);
                }
            }

            $pdo->prepare("DELETE FROM sites WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'auth_check':
            // Endpoint pour que le JS récupère le token CSRF et l'état de connexion
            echo json_encode([
                'success' => true,
                'logged_in' => isLoggedIn(),
                'csrf_token' => $_SESSION['csrf_token'] ?? null
            ]);
            break;

        default:
            sendError("Action inconnue");
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    sendError("Erreur serveur interne", 500);
}
?>
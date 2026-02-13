<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Actions publiques (pas besoin d'authentification)
$publicActions = ['list', 'get'];

// Vérifier l'authentification uniquement pour les actions protégées
if (!in_array($action, $publicActions)) {
    requireAuth();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

// Fonction pour uploader une image
function uploadImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
    }

    // Vérifier la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 5 Mo)'];
    }

    // Vérifier le type MIME
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Format d\'image non autorisé'];
    }

    // Créer le dossier uploads s'il n'existe pas
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('site_', true) . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => UPLOAD_URL . $filename];
    }

    return ['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier'];
}

// Fonction pour supprimer une image
function deleteImage($imagePath) {
    if (empty($imagePath)) return;
    
    $filepath = str_replace(UPLOAD_URL, UPLOAD_DIR, $imagePath);
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

try {
    switch ($action) {
        case 'list':
            // Lister tous les sites/projets
            $type = $_GET['type'] ?? 'site';
            $stmt = $pdo->prepare("SELECT * FROM sites WHERE type = ? ORDER BY sort_order ASC, created_at ASC");
            $stmt->execute([$type]);
            $items = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $items]);
            break;

        case 'get':
            // Récupérer un site/projet spécifique
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            if ($item) {
                echo json_encode(['success' => true, 'data' => $item]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Élément non trouvé']);
            }
            break;

        case 'create':
            // Créer un nouveau site/projet
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['path'];
                } else {
                    echo json_encode($uploadResult);
                    exit;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO sites (type, name, url, description, image_path, cms, version) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['type'] ?? 'site',
                $_POST['name'],
                $_POST['url'],
                $_POST['description'],
                $imagePath,
                $_POST['cms'],
                $_POST['version'] ?? null
            ]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'id' => $newId, 'image_path' => $imagePath]);
            break;

        case 'update':
            // Mettre à jour un site/projet
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            // Récupérer l'ancien chemin d'image
            $stmt = $pdo->prepare("SELECT image_path FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $oldData = $stmt->fetch();
            $oldImagePath = $oldData['image_path'] ?? null;

            $imagePath = $oldImagePath;
            
            // Upload nouvelle image si fournie
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['image']);
                if ($uploadResult['success']) {
                    // Supprimer l'ancienne image
                    if ($oldImagePath) {
                        deleteImage($oldImagePath);
                    }
                    $imagePath = $uploadResult['path'];
                } else {
                    echo json_encode($uploadResult);
                    exit;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE sites 
                SET name = ?, url = ?, description = ?, image_path = ?, cms = ?, version = ?, type = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['name'],
                $_POST['url'],
                $_POST['description'],
                $imagePath,
                $_POST['cms'],
                $_POST['version'] ?? null,
                $_POST['type'] ?? 'site',
                $id
            ]);
            
            echo json_encode(['success' => true, 'image_path' => $imagePath]);
            break;

        case 'delete':
            // Supprimer un site/projet
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            // Récupérer et supprimer l'image
            $stmt = $pdo->prepare("SELECT image_path FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data && $data['image_path']) {
                deleteImage($data['image_path']);
            }

            // Supprimer l'entrée
            $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;

        case 'delete-image':
            // Supprimer uniquement l'image d'un site/projet
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT image_path FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data && $data['image_path']) {
                deleteImage($data['image_path']);
                
                // Mettre à jour la BDD
                $stmt = $pdo->prepare("UPDATE sites SET image_path = NULL WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Aucune image à supprimer']);
            }
            break;

        case 'reorder':
            // Mettre à jour l'ordre d'affichage (reçoit JSON: {"ids": [3, 1, 2]})
            $body = json_decode(file_get_contents('php://input'), true);
            $ids = $body['ids'] ?? [];

            if (empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'Aucun ID fourni']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE sites SET sort_order = ? WHERE id = ?");
            foreach ($ids as $order => $id) {
                $stmt->execute([$order, intval($id)]);
            }

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
// includes/functions.php

// 1. Protection CSRF
function validateCSRF() {
    $headers = apache_request_headers();
    $token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';

    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sécurité : Token CSRF invalide (Rafraîchissez la page).']);
        exit;
    }
}

// 2. Gestion Upload Sécurisé (Version Améliorée)
function secureUpload($file) {
    // Vérification basique
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erreur lors du transfert (Code: ' . ($file['error'] ?? 'Inconnu') . ')'];
    }

    // Vérification Type MIME (Sécurité)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mimeType, $allowed)) {
        return ['success' => false, 'error' => 'Format non autorisé. Utilisez JPG, PNG, GIF ou WEBP.'];
    }

    // Récupération de l'extension sûre
    $ext = $allowed[$mimeType];

    // --- LOGIQUE DE NOMMAGE (Nom d'origine + Hash) ---
    // 1. Récupérer le nom de base sans l'extension
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);

    // 2. Nettoyage : minuscules, remplacer espaces/spéciaux par tirets, ne garder que alphanumérique
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($originalName));
    $slug = trim($slug, '-');
    if (empty($slug)) $slug = 'image'; // Fallback si le nom était "..."

    // 3. Ajout d'un hash court (4 chars) pour éviter les doublons
    $hash = bin2hex(random_bytes(2)); // ex: a1b2

    $filename = $slug . '-' . $hash . '.' . $ext;
    // ------------------------------------------------

    // Vérification du dossier (Crée le dossier s'il n'existe pas)
    if (!defined('UPLOAD_DIR')) {
        return ['success' => false, 'error' => 'Erreur config: UPLOAD_DIR non défini'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return ['success' => false, 'error' => 'Impossible de créer le dossier uploads (Permissions ?)'];
        }
    }

    $targetPath = UPLOAD_DIR . $filename;

    // Déplacement final
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // On retourne le chemin relatif à stocker en BDD (ex: "uploads/mon-logo-a1b2.jpg")
        return ['success' => true, 'path' => UPLOAD_URL . $filename];
    }

    return ['success' => false, 'error' => 'Erreur lors de l\'écriture du fichier sur le serveur.'];
}

// 3. Réponse JSON standard
function jsonResponse($success, $data = null, $message = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $message
    ]);
    exit;
}
?>
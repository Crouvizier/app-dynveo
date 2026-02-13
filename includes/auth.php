<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAdmin() {
    if (!isAdmin()) {
        // On redirige vers le login (chemin relatif depuis le dossier admin/)
        header('Location: ../login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isAdmin()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ];
}
?>
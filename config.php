<?php
// Configuration de la base de données
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', ''); // À modifier selon votre configuration
define('DB_PASS', ''); // À modifier selon votre configuration

// Identifiants admin (à personnaliser)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', password_hash('', PASSWORD_DEFAULT)); // Changez ce mot de passe !

// Dossier d'upload des images
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo

// Connexion à la base de données
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Vérifier l'authentification pour les pages protégées
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

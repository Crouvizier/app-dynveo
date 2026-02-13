<?php
/**
 * Configuration Principale - Version BDD
 * @version 2.1.0
 */

// 1. Sécurité : Empêcher l'accès direct au fichier
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès direct interdit.');
}

// 2. Gestion des erreurs
// Mettre à 0 en production pour la sécurité
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 3. Configuration des Sessions (Blindage OWASP)
if (session_status() === PHP_SESSION_NONE) {
    // Empêche le JavaScript d'accéder au cookie de session (Protection XSS)
    ini_set('session.cookie_httponly', 1);

    // Force l'utilisation des cookies uniquement (pas d'ID dans l'URL)
    ini_set('session.use_only_cookies', 1);

    // Empêche l'envoi du cookie lors de requêtes cross-site (Protection CSRF)
    ini_set('session.cookie_samesite', 'Strict');

    // Si HTTPS est détecté, on force le cookie sécurisé
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

// 4. Chargement des identifiants BDD (Secrets)
// On cherche un fichier config.local.php qui contient les vrais mots de passe BDD
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// Valeurs par défaut (au cas où config.local.php n'existe pas ou est incomplet)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'portail_sites');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// 5. Constantes Système
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/'); // Chemin relatif pour le navigateur
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 Mo
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// 6. Connexion BDD (Singleton)
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Sécurité maximale contre les injections SQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log l'erreur dans un fichier serveur, mais n'affiche rien à l'utilisateur
            error_log("Erreur de connexion BDD : " . $e->getMessage());
            die("Service temporairement indisponible (Erreur BDD).");
        }
    }
    return $pdo;
}

// 7. Helpers d'Authentification
function isLoggedIn() {
    // On vérifie que la session est active et que l'empreinte du navigateur correspond
    // (Protection contre le vol de session basique)
    return isset($_SESSION['admin_logged_in']) &&
        $_SESSION['admin_logged_in'] === true &&
        isset($_SESSION['fingerprint']) &&
        $_SESSION['fingerprint'] === md5($_SERVER['HTTP_USER_AGENT']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// 8. Protection CSRF (Cross-Site Request Forgery)
// Génère un jeton unique par session si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
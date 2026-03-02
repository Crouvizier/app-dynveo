<?php
/**
 * Configuration Principale - Version BDD
 * @version 2.1.1
 */

// 1. Sécurité : Empêcher l'accès direct au fichier
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès direct interdit.');
}

// 2. Gestion des erreurs
// Activé temporairement pour diagnostiquer la page blanche.
// À remettre à 0 une fois que la connexion fonctionne.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. Configuration des Sessions (Blindage OWASP)
if (session_status() === PHP_SESSION_NONE) {
    // Empêche le JavaScript d'accéder au cookie de session (Protection XSS)
    ini_set('session.cookie_httponly', 1);

    // Force l'utilisation des cookies uniquement (pas d'ID dans l'URL)
    ini_set('session.use_only_cookies', 1);

    // 'Lax' au lieu de 'Strict' pour éviter les pages blanches/déconnexions lors des redirections de login
    ini_set('session.cookie_samesite', 'Lax');

    // Détection dynamique du HTTPS pour le cookie Secure
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        ini_set('session.cookie_secure', 1);
    }

    session_start();
}

// 4. Chargement des identifiants BDD (Secrets)
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// 5. Constantes Système
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', 'uploads/');
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
                PDO::ATTR_EMULATE_PREPARES => false, // Protection contre les injections SQL
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion BDD : " . $e->getMessage());
            die("Service temporairement indisponible (Erreur BDD).");
        }
    }
    return $pdo;
}

// 7. Helpers d'Authentification
function isLoggedIn() {
    // Vérification de la session active ET de l'empreinte du navigateur pour éviter le vol de session
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
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
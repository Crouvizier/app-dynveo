<?php
// 1. Chargement de la configuration (qui démarre déjà la session de manière sécurisée)
require_once 'config.php';

// Si déjà connecté, redirection vers l'admin
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $pdo = getDBConnection();

            // Correction chirurgicale : Utilisation de la table 'admin' (au lieu de admins ou users)
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

                // === SÉCURISATION DE LA SESSION ===
            // Anti-fixation de session
            session_regenerate_id(true);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];

                // Empreinte numérique (Fingerprint) requise par votre config.php
                $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT']);

                // Jeton CSRF déjà généré par config.php, mais on peut le rafraîchir ici
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Redirection vers votre page d'administration
                header('Location: admin.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
                // Protection contre le brute-force
                usleep(300000);
            }
        } catch (Exception $e) {
            // En cas d'erreur SQL, on affiche un message générique sans exposer la structure
            $error = "Erreur de connexion au service d'authentification.";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration</title>
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0a1628; font-family: sans-serif; margin: 0; }
        .login-box { width: 100%; max-width: 400px; padding: 2rem; background: rgba(30, 58, 95, 0.4); border: 1px solid rgba(144, 238, 144, 0.2); border-radius: 16px; backdrop-filter: blur(10px); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { text-align: center; color: #90EE90; margin-bottom: 2rem; text-transform: uppercase; letter-spacing: 2px; }
        .form-group { margin-bottom: 1.5rem; }
        input { width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; box-sizing: border-box; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #90EE90; }
        .btn-login { width: 100%; padding: 1rem; background: #90EE90; border: none; font-weight: bold; cursor: pointer; border-radius: 8px; color: #0a1628; font-size: 1rem; text-transform: uppercase; transition: transform 0.2s, background 0.2s; }
        .btn-login:hover { background: #4CAF50; transform: translateY(-2px); }
        .error { background: rgba(255, 71, 87, 0.2); color: #ff6b6b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 0.9rem; border: 1px solid rgba(255, 71, 87, 0.3); }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #b0c4de; text-decoration: none; font-size: 0.85rem; }
        .back-link:hover { color: #90EE90; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>🔒 Admin</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <input type="text" name="username" placeholder="Utilisateur" required autofocus autocomplete="username">
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">Se connecter</button>
    </form>

    <a href="index.php" class="back-link">← Retour au portail public</a>
</div>
</body>
</html>
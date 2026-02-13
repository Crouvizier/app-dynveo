<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php'; // Démarre la session

// Si déjà connecté avec la NOUVELLE méthode, on va direct au dashboard
if (isAdmin()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // === C'EST ICI QUE LA MAGIE OPÈRE ===
            // On définit les variables attendues par le nouveau système
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Nouveau Token pour l'admin

            // Anti-fixation de session
            session_regenerate_id(true);

            // Mise à jour dernière connexion
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header('Location: admin/index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
            usleep(300000); // Ralentir le brute-force
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0a1628; }
        .login-box { width: 100%; max-width: 400px; padding: 2rem; background: rgba(30, 58, 95, 0.4); border: 1px solid rgba(144, 238, 144, 0.2); border-radius: 16px; backdrop-filter: blur(10px); }
        .form-group { margin-bottom: 1.5rem; }
        input { width: 100%; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; }
        .btn-login { width: 100%; padding: 1rem; background: #90EE90; border: none; font-weight: bold; cursor: pointer; border-radius: 8px; color: #0a1628; font-size: 1rem; text-transform: uppercase; }
        .btn-login:hover { background: #4CAF50; }
        .error { background: rgba(255, 71, 87, 0.2); color: #ff6b6b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #b0c4de; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="login-box">
    <h2 style="text-align:center; color:#90EE90; margin-bottom:2rem;">ADMINISTRATION</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="username" placeholder="Utilisateur" required autofocus autocomplete="username">
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">Se connecter</button>
    </form>

    <a href="index.php" class="back-link">← Retour au site public</a>
</div>
</body>
</html>
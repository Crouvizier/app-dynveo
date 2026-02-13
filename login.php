<?php
/**
 * Page de Connexion - Version BDD Sécurisée
 */
require_once 'config.php';

// Redirection si déjà connecté
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

            // 1. Récupérer l'utilisateur
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 2. Vérification du mot de passe
            if ($user && password_verify($password, $user['password'])) {
                // ✅ Succès

                // Régénération de l'ID de session (Protection Fixation de Session)
                session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['fingerprint'] = md5($_SERVER['HTTP_USER_AGENT']);

                // Mettre à jour la date de dernière connexion
                $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->execute([$user['id']]);

                // Log (Optionnel si table créée)
                // $pdo->prepare("INSERT INTO login_logs (username, ip_address, status) VALUES (?, ?, 'success')")
                //     ->execute([$username, $_SERVER['REMOTE_ADDR']]);

        header('Location: admin.php');
        exit;
    } else {
                // ❌ Échec
        $error = 'Identifiants incorrects';

                // Ralentir l'exécution pour gêner les attaques force-brute (Time constant)
                usleep(300000); // 300ms de pause

                // Log (Optionnel)
                // $pdo->prepare("INSERT INTO login_logs (username, ip_address, status) VALUES (?, ?, 'failed')")
                //     ->execute([$username, $_SERVER['REMOTE_ADDR']]);
            }
        } catch (Exception $e) {
            // Ne jamais afficher l'erreur technique précise à l'utilisateur
            $error = 'Une erreur technique est survenue.';
            error_log($e->getMessage());
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Portail Web</title>
    <style>
        /* Copie minimale du style login pour l'autonomie de la page */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(144, 238, 144, 0.3);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .logo {
            text-align: center; font-size: 2.5rem; font-weight: 700;
            background: linear-gradient(135deg, #90EE90 0%, #4CAF50 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: #90EE90; margin-bottom: 0.5rem; text-transform: uppercase; font-size: 0.9rem; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 1rem;
            background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(144, 238, 144, 0.2);
            border-radius: 10px; color: #ffffff; font-size: 1rem;
        }
        .login-btn {
            width: 100%; padding: 1.2rem;
            background: linear-gradient(135deg, #90EE90 0%, #4CAF50 100%);
            border: none; border-radius: 10px; color: #1e3a5f;
            font-size: 1.1rem; font-weight: 700; cursor: pointer; text-transform: uppercase;
            margin-top: 1rem;
        }
        .error { background: rgba(255, 100, 100, 0.2); border: 1px solid #ff6b6b; color: #ffb3b3; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
        .back-btn { display: block; text-align: center; margin-top: 1.5rem; color: #90EE90; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🔐 ADMIN</div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Utilisateur</label>
                <input type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="login-btn">Connexion</button>
        </form>
        <a href="index.php" class="back-btn">← Retour au portail</a>
    </div>
</body>
</html>
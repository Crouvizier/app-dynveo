<?php
require_once 'config.php';

$error = '';

// Si déjà connecté, rediriger vers l'admin
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
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
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #90EE90 0%, #4CAF50 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }
        .subtitle {
            text-align: center;
            color: #b0c4de;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            color: #90EE90;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .form-group input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(144, 238, 144, 0.2);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #90EE90;
            box-shadow: 0 0 20px rgba(144, 238, 144, 0.3);
        }
        .login-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #90EE90 0%, #4CAF50 100%);
            border: none;
            border-radius: 10px;
            color: #1e3a5f;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            margin-top: 1rem;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(144, 238, 144, 0.4);
        }
        .error {
            background: rgba(255, 100, 100, 0.2);
            border: 1px solid #ff6b6b;
            border-radius: 10px;
            padding: 1rem;
            color: #ffb3b3;
            margin-bottom: 1rem;
            text-align: center;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #90EE90;
            text-decoration: none;
        }
        .back-btn:hover {
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🔐 ADMIN</div>
        <div class="subtitle">Authentification</div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Connexion</button>
        </form>
        <a href="index.php" class="back-btn">← Retour au portail</a>
    </div>
</body>
</html>

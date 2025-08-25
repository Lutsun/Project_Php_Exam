<?php
// EN-TÊTES ANTI-CACHE ET SESSION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Démarrer la session avant tout output
require 'includes/session_init.php';

// Nettoyage complet si arrivé depuis déconnexion
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirection si déjà connecté 
if (isset($_SESSION['user_id'])) {
    header("Location: ".($_SESSION['role'] === 'Admin' ? 'acceuil_admin.php' : 'listprod.php'));
    exit();
}

// Inclusion de la connexion
require 'includes/connexion.php';

// ==============================================
// TRAITEMENT DU FORMULAIRE
// ==============================================
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['pseudo'] ?? '');
    $password = $_POST['motdepasse'] ?? '';
    $profil = $_POST['groupe'] ?? '';

    if (empty($login) || empty($password) || empty($profil)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE login_user = ? AND profil_user = ? LIMIT 1");
            $stmt->execute([$login, $profil]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_user'])) {
                // Authentification réussie
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['prenom_user'] . ' ' . $user['nom_user'];
                $_SESSION['role'] = $user['profil_user'];
                $_SESSION['photo'] = $user['photo_user'] ?? 'https://i.pravatar.cc/80?img=3';

                // Redirection immédiate selon le rôle
                if ($user['profil_user'] === 'Admin') {
                    header("Location: acceuil_admin.php");
                } else {
                    header("Location: listprod.php");
                }
                exit();
            } else {
                $error = "Identifiants incorrects ou profil non autorisé";
            }
        } catch (PDOException $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            $error = "Erreur système. Veuillez réessayer plus tard.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Connexion - StockNova</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a00ff; /* Changé pour correspondre à loading.php */
            --primary-dark: #7b1fa2;
            --primary-light: #9d4dff;
            --dark: #121212;
            --darker: #0a0a12;
            --light: #f5f5f5;
            --danger: #e91e63;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--dark), var(--darker));
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Conteneur principal */
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 1.2s ease-out forwards 0.5s;
        }

        /* En-tête */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
        }

        /* Formulaire */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
        }

        /* Select personnalisé */
        .custom-select {
            position: relative;
        }

        .custom-select select {
            width: 100%;
            padding: 12px 40px 12px 16px;
            background: rgba(30, 30, 30, 0.9) !important;
            border: 1px solid rgba(106, 0, 255, 0.5) !important;
            border-radius: 8px;
            color: white !important;
            font-size: 1rem;
            appearance: none;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            pointer-events: none;
        }

        /* Options du select */
        .custom-select option {
            background: rgba(40, 40, 40, 0.95) !important;
            color: white !important;
            padding: 12px !important;
        }

        /* Bouton */
        .login-button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
        }

        .login-button:hover {
            background: var(--primary-dark);
        }

        /* Liens */
        .login-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .login-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .login-links a:hover {
            color: var(--primary-light);
        }

        /* Messages d'erreur */
        .error-message {
            color: var(--danger);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: none;
        }

        /* Animations */
        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Écran de préchargement (remplacé par loading.php) -->
    <?php include 'includes/loading.php'; ?>

    <!-- Formulaire de connexion -->
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <span style="color: white;">Stock</span>
                <span style="color: var(--primary);">Nova</span>
            </div>
            <p>Connectez-vous à votre espace</p>
        </div>
        
        <form id="loginForm" method="POST">
            <?php if (!empty($error)): ?>
                <div class="error-message" style="display: block; text-align: center; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" id="pseudo" name="pseudo" required value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
                <div class="error-message" id="pseudo-error"></div>
            </div>
            
            <div class="form-group">
                <label for="motdepasse">Mot de passe</label>
                <input type="password" id="motdepasse" name="motdepasse" required>
                <div class="error-message" id="password-error"></div>
            </div>
            
            <div class="form-group">
                <label for="groupe">Profil</label>
                <div class="custom-select">
                    <select id="groupe" name="groupe" required>
                        <option value="" disabled selected hidden>Sélectionnez...</option>
                        <option value="Admin" <?= (isset($_POST['groupe']) && $_POST['groupe'] === 'Admin' ? 'selected' : '') ?>>Administrateur</option>
                        <option value="User" <?= (isset($_POST['groupe']) && $_POST['groupe'] === 'User' ? 'selected' : '') ?>>Utilisateur</option>
                    </select>
                    <span class="select-arrow">▼</span>
                </div>
                <div class="error-message" id="group-error"></div>
            </div>
            <!-- Dans la partie HTML, après le formulaire de connexion -->
                <div class="login-links">
                    <a href="inscription.php">Créer un compte</a>
                </div>
            
            <button type="submit" class="login-button">Connexion</button>
            
        </form>
    </div>

    <script>
        // Gestion du préchargement (déjà géré par loading.php)
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loading-overlay').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loading-overlay').remove();
                }, 500);
            }, 1500);
        });

        // Gestion du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            const pseudo = document.getElementById('pseudo').value;
            const password = document.getElementById('motdepasse').value;
            const group = document.getElementById('groupe').value;
            
            document.querySelectorAll('.error-message').forEach(el => {
                el.style.display = 'none';
            });
            
            if (pseudo.length < 3) {
                document.getElementById('pseudo-error').textContent = "Pseudo trop court (min 3 caractères)";
                document.getElementById('pseudo-error').style.display = 'block';
                isValid = false;
            }
            
            if (password.length < 6) {
                document.getElementById('password-error').textContent = "Mot de passe trop court (min 6 caractères)";
                document.getElementById('password-error').style.display = 'block';
                isValid = false;
            }
            
            if (!group) {
                document.getElementById('group-error').textContent = "Veuillez sélectionner un profil";
                document.getElementById('group-error').style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) e.preventDefault();
        });

  // Gestion des événements pour éviter le retour navigateur et recharger la page
    document.addEventListener('DOMContentLoaded', function() {
    // 1. Blocage du retour navigateur
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function() {
        history.pushState(null, null, location.href);
        window.location.reload(); // Rechargement forcé
    });

    // 2. Contrôle du cache et détection des pages en cache
    window.onpageshow = function(event) {
        if (event.persisted || performance.navigation.type === 2) {
            // Type 2 = navigation par historique (back/forward)
            window.location.reload();
        }
    };

    // 3. Gestion du préchargement (si vous utilisez loading.php)
    window.addEventListener('load', function() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.remove();
                }, 500);
            }, 1500);
        }
    });
});
</script>
</body>
</html>
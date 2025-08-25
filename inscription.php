<?php
// EN-TÊTES ANTI-CACHE ET SESSION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'includes/session_init.php';
require 'includes/connexion.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    
    try {
        // Récupération des données
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $pays = trim($_POST['pays'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation des champs
        $requiredFields = [
            'Prénom' => $prenom,
            'Adresse' => $adresse,
            'Ville' => $ville,
            'Pays' => $pays,
            'Email' => $email,
            'Téléphone' => $telephone,
            'Mot de passe' => $password,
            'Confirmation mot de passe' => $confirm_password
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $value) {
            if (empty($value)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new Exception("Champs obligatoires manquants : " . implode(', ', $missingFields));
        }

        if ($password !== $confirm_password) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        if (strlen($password) < 6) {
            throw new Exception("Le mot de passe doit contenir au moins 6 caractères");
        }

        // Vérification de l'email unique dans la table clients
        $stmt = $pdo->prepare("SELECT id_client FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Cet email est déjà utilisé");
        }

        // Création du login
        $login = strtolower($prenom . '.' . ($nom ?: 'user'));
        $originalLogin = $login;
        $suffix = 1;
        
        // Vérification du login unique dans la table users
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE login_user = ?");
        while (true) {
            $stmt->execute([$login]);
            if (!$stmt->fetch()) break;
            $login = $originalLogin . $suffix++;
        }

        // 1. Insérer dans la table clients
        $sqlClient = "INSERT INTO clients (nom_cli, prenom_cli, adresse_cli, ville, pays, email, telephone, statut) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'utilisateur')";
        $stmtClient = $pdo->prepare($sqlClient);
        if (!$stmtClient->execute([$nom, $prenom, $adresse, $ville, $pays, $email, $telephone])) {
            throw new Exception("Erreur lors de l'ajout du client");
        }
        
        $clientId = $pdo->lastInsertId();

        // 2. Insérer dans la table users
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sqlUser = "INSERT INTO users (nom_user, prenom_user, adresse_user, login_user, password_user, profil_user, telephone_user, id_client) 
                   VALUES (?, ?, ?, ?, ?, 'User', ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        if (!$stmtUser->execute([$nom ?: $prenom, $prenom, $adresse, $login, $hashedPassword, $telephone, $clientId])) {
            throw new Exception("Erreur lors de la création de l'utilisateur");
        }

        $pdo->commit();
        $success = "Inscription réussie! Votre login est : " . htmlspecialchars($login);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - StockNova</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a00ff;
            --primary-dark: #5a00d4;
            --primary-light: #8a5cff;
            --dark: #121212;
            --darker: #0a0a0a;
            --light: #f5f5f5;
            --danger: #ff3860;
            --success: #00d1b2;
            --text-light: rgba(255, 255, 255, 0.9);
            --text-muted: rgba(255, 255, 255, 0.6);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Conteneur principal élégant */
        .signup-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            background: rgba(30, 30, 46, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        /* En-tête stylisé */
        .signup-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .signup-logo {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
            background: linear-gradient(to right, white 50%, var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .signup-header p {
            color: var(--text-muted);
            font-weight: 300;
            font-size: 0.95rem;
        }

        /* Formulaire élégant */
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(20, 20, 36, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.15);
            background: rgba(30, 30, 46, 0.9);
        }

        /* Bouton modernisé */
        .signup-button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(106, 0, 255, 0.2);
        }

        .signup-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(106, 0, 255, 0.25);
        }

        /* Section liens */
        .signup-links {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.9rem;
        }

        .signup-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 400;
        }

        .signup-links a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        /* Messages stylisés */
        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 1.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
            display: flex;
            align-items: center;
        }

        .error-message {
            background: rgba(255, 56, 96, 0.1);
            color: var(--danger);
            border: 1px solid rgba(255, 56, 96, 0.2);
        }

        .success-message {
            background: rgba(0, 209, 178, 0.1);
            color: var(--success);
            border: 1px solid rgba(0, 209, 178, 0.2);
        }

        .message i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Animation */
        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Disposition responsive */
        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        @media (max-width: 480px) {
            .signup-container {
                padding: 2rem 1.5rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1.25rem;
            }
        }

        /* Effet de focus sur les labels */
        .form-group input:focus + label {
            color: var(--primary-light);
        }

        /* Icône optionnelle pour les champs */
        .field-icon {
            position: absolute;
            right: 15px;
            top: 38px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/loading.php'; ?>

    <div class="signup-container">
        <div class="signup-header">
            <div class="signup-logo">
                <span style="color: white;">Stock</span>
                <span style="color: var(--primary);">Nova</span>
            </div>
            <p>Créez votre compte</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form id="signupForm" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    <i class="fas fa-user field-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    <i class="fas fa-user field-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="adresse">Adresse *</label>
                <input type="text" id="adresse" name="adresse" required value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                <i class="fas fa-map-marker-alt field-icon"></i>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ville">Ville *</label>
                    <input type="text" id="ville" name="ville" required value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>">
                    <i class="fas fa-city field-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="pays">Pays *</label>
                    <input type="text" id="pays" name="pays" required value="<?= htmlspecialchars($_POST['pays'] ?? '') ?>">
                    <i class="fas fa-globe field-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <i class="fas fa-envelope field-icon"></i>
            </div>
            
            <div class="form-group">
                <label for="telephone">Téléphone *</label>
                <input type="tel" id="telephone" name="telephone" required value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                <i class="fas fa-phone field-icon"></i>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-lock field-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmation *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-lock field-icon"></i>
                </div>
            </div>
            
            <button type="submit" class="signup-button">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
            
            <div class="signup-links">
                <p>Déjà membre ? <a href="index.php">Connectez-vous ici</a></p>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des erreurs côté client
            const form = document.getElementById('signupForm');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                // Réinitialiser les styles
                document.querySelectorAll('.form-group').forEach(group => {
                    group.style.border = 'none';
                });
                
                // Vérifier chaque champ requis
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.parentElement.style.border = '1px solid var(--danger)';
                        isValid = false;
                    }
                });
                
                // Vérifier la correspondance des mots de passe
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    alert("Les mots de passe ne correspondent pas");
                    isValid = false;
                }
                
                if (password.length < 6) {
                    alert("Le mot de passe doit contenir au moins 6 caractères");
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    // Faire défiler jusqu'au premier champ invalide
                    const firstInvalid = form.querySelector('[required]:invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            
            // Amélioration UX : focus sur le premier champ
            const firstInput = form.querySelector('input');
            if (firstInput) {
                setTimeout(() => {
                    firstInput.focus();
                }, 400);
            }
        });
    </script>
</body>
</html>


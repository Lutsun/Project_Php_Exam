<?php
$pageTitle = "Ajouter un Client - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Vérifier si l'utilisateur est admin
$isAdmin = (isset($_SESSION['user']['profil_user']) && $_SESSION['user']['profil_user'] === 'Admin');

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom_cli = $_POST['nom_cli'] ?? null;
        $prenom_cli = $_POST['prenom_cli'];
        $adresse_cli = $_POST['adresse_cli'];
        $ville = $_POST['ville'];
        $pays = $_POST['pays'];
        $email = $_POST['email'] ?? null;
        $telephone = $_POST['telephone'];
        $statut = $_POST['statut'] ?? 'utilisateur';

        if (empty($prenom_cli) || empty($adresse_cli) || empty($ville) || empty($pays) || empty($telephone)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }

        // Vérification de l'email unique si fourni
        if ($email) {
            $checkEmail = $pdo->prepare("SELECT id_client FROM clients WHERE email = ?");
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                throw new Exception("Cet email est déjà utilisé par un autre client");
            }
        }

        // Commencer une transaction
        $pdo->beginTransaction();

        // 1. Insérer dans la table clients
        $sql = "INSERT INTO clients (nom_cli, prenom_cli, adresse_cli, ville, pays, email, telephone, statut) 
                VALUES (:nom_cli, :prenom_cli, :adresse_cli, :ville, :pays, :email, :telephone, :statut)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':nom_cli', $nom_cli, PDO::PARAM_STR);
        $stmt->bindValue(':prenom_cli', $prenom_cli, PDO::PARAM_STR);
        $stmt->bindValue(':adresse_cli', $adresse_cli, PDO::PARAM_STR);
        $stmt->bindValue(':ville', $ville, PDO::PARAM_STR);
        $stmt->bindValue(':pays', $pays, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':telephone', $telephone, PDO::PARAM_STR);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'ajout du client");
        }

        // Récupérer l'ID du client inséré
        $clientId = $pdo->lastInsertId();
        
        // Créer un login automatique (ex: prenom.nom)
        $login = strtolower($prenom_cli . '.' . ($nom_cli ? str_replace(' ', '', $nom_cli) : 'user' . $clientId));
        
        // Créer un mot de passe temporaire
        $tempPassword = bin2hex(random_bytes(4)); // Mot de passe aléatoire simple
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Déterminer le profil en fonction du statut
        $profil = ($statut === 'admin') ? 'Admin' : 'User';
        
        // Insérer dans la table users
        $sqlUser = "INSERT INTO users (nom_user, prenom_user, adresse_user, login_user, password_user, profil_user, telephone_user, id_client) 
                    VALUES (:nom, :prenom, :adresse, :login, :password, :profil, :telephone, :id_client)";
        $stmtUser = $pdo->prepare($sqlUser);
        $nom_user = $nom_cli ?: 'Client';
        $stmtUser->bindValue(':nom', $nom_user, PDO::PARAM_STR);
        $stmtUser->bindValue(':nom', $nom_user, PDO::PARAM_STR);
        $stmtUser->bindValue(':prenom', $prenom_cli, PDO::PARAM_STR);
        $stmtUser->bindValue(':adresse', $adresse_cli, PDO::PARAM_STR);
        $stmtUser->bindValue(':login', $login, PDO::PARAM_STR);
        $stmtUser->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmtUser->bindValue(':profil', $profil, PDO::PARAM_STR);
        $stmtUser->bindValue(':telephone', $telephone, PDO::PARAM_STR);
        $stmtUser->bindValue(':id_client', $clientId, PDO::PARAM_INT);

        if (!$stmtUser->execute()) {
            throw new Exception("Erreur lors de la création de l'utilisateur associé");
        }

        // Valider la transaction si tout s'est bien passé
        $pdo->commit();
        
        $successMessage = "Client et utilisateur associé ajoutés avec succès!<br><br>
                          <strong>Identifiants de connexion:</strong><br>
                          Login: <code>$login</code><br>
                          Mot de passe temporaire: <code>$tempPassword</code><br><br>
                          <small>Il est recommandé de changer ce mot de passe après la première connexion.</small>";

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        $errorMessage = $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-user-plus"></i> Ajouter un client</h3>
        </div>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="add-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom_cli">Nom</label>
                    <input type="text" id="nom_cli" name="nom_cli" class="form-input">
                    <span class="form-hint">(facultatif)</span>
                </div>
                
                <div class="form-group">
                    <label for="prenom_cli">Prénom *</label>
                    <input type="text" id="prenom_cli" name="prenom_cli" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="adresse_cli">Adresse *</label>
                <input type="text" id="adresse_cli" name="adresse_cli" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ville">Ville *</label>
                    <input type="text" id="ville" name="ville" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="pays">Pays *</label>
                    <input type="text" id="pays" name="pays" class="form-input" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input">
                    <span class="form-hint">(facultatif)</span>
                </div>
                
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="telephone" class="form-input" required>
                </div>
            </div>

            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut" class="form-input">
                    <option value="utilisateur">Utilisateur</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="reset" class="reset-btn">
                    <i class="fas fa-eraser"></i> Effacer
                </button>
            </div>
        </form>
    </div>
</div>

<style>
:root {
    --primary: #6a00ff;
    --primary-light: #9d4dff;
    --secondary: #00ffc6;
    --dark: #0d0d1a;
    --darker: #0a0a12;
    --light: #e0e0f0;
    --text: #ffffff;
    --text-secondary: #b8b8d1;
    --danger: #ff6b6b;
    --warning: #ffc107;
    --success: #4caf50;
}

.dashboard-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.data-section {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid rgba(106, 0, 255, 0.3);
}

.section-header {
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h3 {
    color: var(--text);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert i {
    font-size: 1.2rem;
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    color: var(--success);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.add-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1.5rem;
}

.form-group {
    flex: 1;
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
}

.form-hint {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-style: italic;
    display: block;
    margin-top: 0.25rem;
}

select.form-input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%239d4dff' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.submit-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.submit-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.reset-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.reset-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-group {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .submit-btn, .reset-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des champs
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('label').style.color = 'var(--primary-light)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.querySelector('label').style.color = 'var(--text-secondary)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
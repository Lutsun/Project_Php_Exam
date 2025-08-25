<?php
$pageTitle = "Modifier Client - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Vérification du statut admin
if ($_SESSION['role'] !== 'Admin') {
    header('Location: listclient.php');
    exit();
}

// Récupération de l'ID client
$clientId = $_GET['id'] ?? null;
if (!$clientId || !is_numeric($clientId)) {
    header('Location: listclient.php');
    exit();
}

try {
    // Récupération des données client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header('Location: listclient.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Traitement du formulaire
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    // Statut forcé à 'utilisateur' au lieu de récupérer depuis le formulaire
    $statut = 'utilisateur';

    // Validation basique
    if (empty($nom) || empty($prenom) || empty($telephone)) {
        $error = "Les champs nom, prénom et téléphone sont obligatoires";
    } else {
        try {
            $pdo->beginTransaction();
            
            $updateStmt = $pdo->prepare("UPDATE clients SET 
                nom_cli = ?, prenom_cli = ?, adresse_cli = ?, 
                ville = ?, email = ?, telephone = ?, statut = ?
                WHERE id_client = ?");
            
            $updateStmt->execute([
                $nom, $prenom, $adresse, $ville, 
                $email, $telephone, $statut, $clientId
            ]);

            $pdo->commit();
            $_SESSION['success_message'] = "Client modifié avec succès";
            header('Location: listclient.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-user-edit"></i> Modifier Client</h3>
            <div class="section-actions">
                <a href="listclient.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="glass-card form-container">
            <form method="POST" class="client-form">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Nom <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($client['nom_cli']) ?>" 
                               required aria-required="true">
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom <span class="required">*</span></label>
                        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($client['prenom_cli']) ?>" 
                               required aria-required="true">
                    </div>

                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($client['adresse_cli']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($client['ville']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($client['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone <span class="required">*</span></label>
                        <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" 
                               required aria-required="true">
                    </div>

                    <!-- Remplacement du select par un champ texte en lecture seule -->
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <input type="text" id="statut" name="statut" value="Utilisateur" readonly class="readonly-field">
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="listclient.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Styles harmonisés avec listclient.php */
:root {
    --primary: #6a00ff;
    --primary-light: #9d4dff;
    --primary-dark: #4a00b0;
    --secondary: #00ffc6;
    --dark: #0d0d1a;
    --darker: #0a0a12;
    --light: #e0e0f0;
    --text: #ffffff;
    --text-secondary: #b8b8d1;
    --danger: #ff6b6b;
    --warning: #ffc107;
    --success: #4caf50;
    --info: #2196f3;
    --glass: rgba(15, 15, 35, 0.65);
    --glass-border: rgba(106, 0, 255, 0.3);
}

.glass-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
}

.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.client-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text);
}

.form-group input,
.form-group select {
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

/* Style pour le champ en lecture seule */
.readonly-field {
    background: rgba(0, 0, 0, 0.2) !important;
    color: var(--text-secondary) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    cursor: not-allowed;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.2);
}

.required {
    color: var(--danger);
}

.form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: rgba(255, 107, 107, 0.15);
    border: 1px solid var(--danger);
    color: var(--danger);
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-footer {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
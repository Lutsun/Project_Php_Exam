<?php
$pageTitle = "Modifier Fournisseur - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';

// Vérification de l'ID fournisseur
if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'ID fournisseur invalide'
    ];
    header("Location: listfournisseur.php");
    exit();
}

$fournisseur_id = (int)$_GET['id'];

// Récupération du fournisseur
try {
    $stmt = $pdo->prepare("SELECT * FROM fournisseurs WHERE id_fournisseur = ?");
    $stmt->execute([$fournisseur_id]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fournisseur) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Fournisseur introuvable'
        ];
        header("Location: listfournisseur.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Erreur technique'
    ];
    header("Location: listfournisseur.php");
    exit();
}

// Traitement du formulaire
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et validation des données
    $data = [
        'nom_fournisseur' => trim($_POST['nom_fournisseur'] ?? ''),
        'prenom_fournisseur' => trim($_POST['prenom_fournisseur'] ?? ''),
        'raison_sociale' => trim($_POST['raison_sociale'] ?? ''),
        'adresse_fournisseur' => trim($_POST['adresse_fournisseur'] ?? ''),
        'ville' => trim($_POST['ville'] ?? ''),
        'pays' => trim($_POST['pays'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? '')
    ];

    // Validation
    if (empty($data['nom_fournisseur'])) $errors[] = "Le nom du fournisseur est requis";
    if (empty($data['adresse_fournisseur'])) $errors[] = "L'adresse est requise";
    if (empty($data['ville'])) $errors[] = "La ville est requise";
    if (empty($data['telephone'])) $errors[] = "Le téléphone est requis";

    // Mise à jour si aucune erreur
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE fournisseurs SET 
                nom_fournisseur = ?,
                prenom_fournisseur = ?,
                raison_sociale = ?,
                adresse_fournisseur = ?,
                ville = ?,
                pays = ?,
                email = ?,
                telephone = ?
                WHERE id_fournisseur = ?");

            if ($stmt->execute([
                $data['nom_fournisseur'],
                $data['prenom_fournisseur'],
                $data['raison_sociale'],
                $data['adresse_fournisseur'],
                $data['ville'],
                $data['pays'],
                $data['email'],
                $data['telephone'],
                $fournisseur_id
            ])) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Fournisseur mis à jour avec succès!'
                ];
                header("Location: listfournisseur.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erreur mise à jour: " . $e->getMessage());
            $errors[] = "Erreur lors de la mise à jour. Veuillez réessayer.";
        }
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h1><i class="fas fa-truck"></i> Modifier le fournisseur</h1>
            <p>Mettez à jour les informations du fournisseur</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <form method="POST" class="form-container">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_fournisseur">Nom <span class="required">*</span></label>
                        <input type="text" id="nom_fournisseur" name="nom_fournisseur" 
                               value="<?= htmlspecialchars($fournisseur['nom_fournisseur']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom_fournisseur">Prénom</label>
                        <input type="text" id="prenom_fournisseur" name="prenom_fournisseur" 
                               value="<?= htmlspecialchars($fournisseur['prenom_fournisseur']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="raison_sociale">Raison Sociale</label>
                        <input type="text" id="raison_sociale" name="raison_sociale" 
                               value="<?= htmlspecialchars($fournisseur['raison_sociale']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse_fournisseur">Adresse <span class="required">*</span></label>
                        <input type="text" id="adresse_fournisseur" name="adresse_fournisseur" 
                               value="<?= htmlspecialchars($fournisseur['adresse_fournisseur']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville <span class="required">*</span></label>
                        <input type="text" id="ville" name="ville" 
                               value="<?= htmlspecialchars($fournisseur['ville']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pays">Pays <span class="required">*</span></label>
                        <input type="text" id="pays" name="pays" 
                               value="<?= htmlspecialchars($fournisseur['pays']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($fournisseur['email']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone <span class="required">*</span></label>
                        <input type="text" id="telephone" name="telephone" 
                               value="<?= htmlspecialchars($fournisseur['telephone']) ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="listfournisseur.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #6a00ff;
    --primary-light: #9d4dff;
    --primary-dark: #4a00b0;
    --secondary: #00ffc6;
    --dark: #0d0d1a;
    --darker: #0a0a12;
    --light: #e0e0f0;
    --lighter: #f5f5ff;
    --text: #ffffff;
    --text-secondary: #b8b8d1;
    --danger: #ff6b6b;
    --warning: #ffc107;
    --success: #4caf50;
    --info: #2196f3;
    --glass: rgba(15, 15, 35, 0.65);
    --glass-border: rgba(106, 0, 255, 0.3);
}

.dashboard-container {
    padding: 2rem;
}

.data-section {
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    background: var(--darker);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--primary-dark);
}

.section-header h1 {
    color: var(--primary-light);
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.95rem;
}

.glass-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text);
    font-weight: 500;
}

.form-group .required {
    color: var(--danger);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.2);
    outline: none;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
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
    box-shadow: 0 4px 8px rgba(106, 0, 255, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.alert-danger ul {
    margin: 0;
    padding-left: 1.5rem;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
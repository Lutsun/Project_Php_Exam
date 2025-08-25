<?php
$pageTitle = "Supprimer Fournisseur - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';

// Vérification du statut admin
if ($_SESSION['role'] !== 'Admin') {
    header('Location: listfournisseur.php');
    exit();
}

// Récupération de l'ID fournisseur
$fournisseurId = $_GET['id'] ?? null;
if (!$fournisseurId || !is_numeric($fournisseurId)) {
    header('Location: listfournisseur.php');
    exit();
}

// Récupération des données du fournisseur
try {
    $stmt = $pdo->prepare("SELECT * FROM fournisseurs WHERE id_fournisseur = ?");
    $stmt->execute([$fournisseurId]);
    $fournisseur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fournisseur) {
        header('Location: listfournisseur.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Vérification si le fournisseur est utilisé dans des produits
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE fournisseur = ?");
    $stmt->execute([$fournisseur['nom_fournisseur']]);
    $productCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    $productCount = 0;
}

// Traitement de la suppression
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        

        $deleteStmt = $pdo->prepare("DELETE FROM fournisseurs WHERE id_fournisseur = ?");
        $deleteStmt->execute([$fournisseurId]);

        $pdo->commit();
        $_SESSION['success_message'] = "Fournisseur supprimé avec succès";
        header('Location: listfournisseur.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h1><i class="fas fa-truck"></i> Supprimer Fournisseur</h1>
            <div class="section-actions">
                <a href="listfournisseur.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="glass-card form-container">
            <form method="POST" class="delete-form">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="confirmation-message">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Confirmation de suppression</h4>
                    <p>Cette action est irréversible. Toutes les données associées à ce fournisseur seront définitivement supprimées.</p>
                    
                    <?php if ($productCount > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> 
                        Ce fournisseur est utilisé dans <?= $productCount ?> produit(s). Vous ne pouvez pas le supprimer tant que ces produits existent.
                    </div>
                    <?php endif; ?>
                    
                    <div class="fournisseur-details">
                        <div class="detail-item">
                            <span class="detail-label">Nom:</span>
                            <span class="detail-value"><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Prénom:</span>
                            <span class="detail-value"><?= htmlspecialchars($fournisseur['prenom_fournisseur']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Raison Sociale:</span>
                            <span class="detail-value"><?= htmlspecialchars($fournisseur['raison_sociale']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Téléphone:</span>
                            <span class="detail-value"><?= htmlspecialchars($fournisseur['telephone']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
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
    max-width: 900px;
    margin: 0 auto;
}

.section-header {
    background: var(--darker);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--primary-dark);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h1 {
    color: var(--primary-light);
    font-size: 1.8rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
    max-width: 800px;
    margin: 0 auto;
}

.delete-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.confirmation-message {
    text-align: center;
    padding: 1.5rem 0;
}

.confirmation-message h4 {
    color: var(--danger);
    margin: 1rem 0 0.5rem;
    font-size: 1.25rem;
}

.confirmation-message p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

.warning-icon {
    font-size: 3rem;
    color: var(--warning);
    margin-bottom: 1rem;
}

.fournisseur-details {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 6px;
    padding: 1.5rem;
    margin: 2rem 0;
    text-align: left;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.detail-value {
    color: var(--text);
}

.form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
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
}

.btn-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn-danger:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
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
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: rgba(255, 107, 107, 0.15);
    border: 1px solid var(--danger);
    color: var(--danger);
}

.alert-warning {
    background: rgba(255, 193, 7, 0.15);
    border: 1px solid var(--warning);
    color: var(--warning);
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .form-footer {
        flex-direction: column;
    }
    
    .detail-item {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
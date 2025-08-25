<?php
$pageTitle = "Modifier Commande - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

$commande_id = $_GET['id'] ?? null;
if (!$commande_id) {
    header("Location: commande_en_cours.php");
    exit();
}

$stmt = $pdo->prepare("SELECT c.*, p.nom_prod, p.prix_unitaire, p.quant_prod AS stock_disponible, 
                       CONCAT(cl.nom_cli, ' ', cl.prenom_cli) AS client_nom
                       FROM commandes c
                       JOIN produits p ON c.produit = p.id_prod
                       JOIN clients cl ON c.client = cl.id_client
                       WHERE c.id_com = ?");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch();

if (!$commande) {
    header("Location: commande_en_cours.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_commande'])) {
    $quantite = (int)$_POST['quantite'];
    $nouveau_montant = $quantite * $commande['prix_unitaire'];
    
    try {
        $pdo->beginTransaction();
        $difference = $quantite - $commande['quant_com'];
        
        $stmt = $pdo->prepare("UPDATE commandes SET quant_com = ?, montant = ? WHERE id_com = ?");
        $stmt->execute([$quantite, $nouveau_montant, $commande_id]);
        
        if ($difference != 0) {
            $stmt = $pdo->prepare("UPDATE produits SET quant_prod = quant_prod - ? WHERE id_prod = ?");
            $stmt->execute([$difference, $commande['produit']]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Commande modifiée avec succès !";
        header("Location: commande_en_cours.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-edit"></i> Modifier Commande #<?= htmlspecialchars($commande['id_com']) ?></h3>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Client</label>
                    <input type="text" value="<?= htmlspecialchars($commande['client_nom']) ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Produit</label>
                    <input type="text" value="<?= htmlspecialchars($commande['nom_prod']) ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Prix Unitaire</label>
                    <input type="text" value="<?= number_format($commande['prix_unitaire'], 0, ',', ' ') ?> FCFA" readonly>
                </div>
                
                <div class="form-group">
                    <label>Stock Disponible</label>
                    <input type="text" value="<?= htmlspecialchars($commande['stock_disponible']) ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="quantite">Quantité</label>
                    <input type="number" id="quantite" name="quantite" value="<?= htmlspecialchars($commande['quant_com']) ?>" 
                           min="1" max="<?= htmlspecialchars($commande['stock_disponible'] + $commande['quant_com']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Nouveau Montant</label>
                    <input type="text" id="nouveau_montant" 
                           value="<?= number_format($commande['quant_com'] * $commande['prix_unitaire'], 0, ',', ' ') ?> FCFA" readonly>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="modifier_commande" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="commande_en_cours.php" class="btn btn-secondary">
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
    --text: #ffffff;
    --text-secondary: #b8b8d1;
    --card-bg: #1a1a2e;
    --border-color: rgba(106, 0, 255, 0.3);
    --border-radius: 8px;
}

.dashboard-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.data-section {
    background: var(--dark);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.section-header {
    margin-bottom: 2rem;
}

.section-header h3 {
    color: var(--text);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.alert-danger {
    background: #ff6b6b;
    color: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
}

.form-container {
    background: var(--card-bg);
    padding: 2rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--darker);
    border: 1px solid var(--primary);
    border-radius: var(--border-radius);
    color: var(--text);
    font-size: 1rem;
}

.form-group input[readonly] {
    background: rgba(106, 0, 255, 0.1);
    border-color: var(--primary-dark);
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    text-decoration: none;
    font-size: 1rem;
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
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 0 0.75rem;
    }
    
    .form-container {
        padding: 1.5rem;
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

<script>
document.getElementById('quantite').addEventListener('input', function() {
    const quantite = this.value;
    const prixUnitaire = <?= $commande['prix_unitaire'] ?>;
    const nouveauMontant = quantite * prixUnitaire;
    document.getElementById('nouveau_montant').value = nouveauMontant.toLocaleString('fr-FR') + ' FCFA';
});
</script>

<?php include 'includes/footer.php'; ?>
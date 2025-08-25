<?php
$pageTitle = "Mise à jour du stock - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Traitement du formulaire d'ajout de fourniture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_fourniture'])) {
    try {
        $produit = $_POST['produit'] ?? null;
        $quantite = $_POST['quantite'] ?? null;
        
        if (empty($produit) || empty($quantite)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }

        // DÉBUT TRANSACTION
        $pdo->beginTransaction();

        // 1. Récupération des infos du produit
        $stmt = $pdo->prepare("SELECT nom_prod, quant_prod, prix_unitaire FROM produits WHERE id_prod = ?");
        $stmt->execute([$produit]);
        $produitInfos = $stmt->fetch();

        if (!$produitInfos) {
            throw new Exception("Produit introuvable");
        }

        $nomProduit = $produitInfos['nom_prod'];
        $ancienneQuantite = $produitInfos['quant_prod'];
        $prixUnitaire = $produitInfos['prix_unitaire'];
        $nouvelleQuantite = $ancienneQuantite + $quantite;
        $montantTotal = $nouvelleQuantite * $prixUnitaire;

        // 2. Mise à jour du stock
        $stmt = $pdo->prepare("UPDATE produits SET quant_prod = ?, prix_total = ? WHERE id_prod = ?");
        $stmt->execute([$nouvelleQuantite, $montantTotal, $produit]);

        // 3. Insertion dans l'historique (version corrigée)
        $stmt = $pdo->prepare("INSERT INTO historique_fournitures 
                             (produit_id, designation, quantite_ajoutee, prix_unitaire, date_operation) 
                             VALUES (?, ?, ?, ?, NOW())");
        
        $result = $stmt->execute([
            $produit,
            $nomProduit,
            $quantite,
            $prixUnitaire
        ]);

        if (!$result) {
            throw new Exception("Erreur lors de l'enregistrement de l'historique");
        }

        // VALIDATION TRANSACTION
        $pdo->commit();

        $successMessage = "Fourniture enregistrée le ".date('d/m/Y')." | +$quantite unités";

    } catch (Exception $e) {
        // ANNULATION en cas d'erreur
        $pdo->rollBack();
        $errorMessage = $e->getMessage();
        
        // Log l'erreur pour debug
        error_log("Erreur fourniture: " . $e->getMessage());
        error_log("Données: " . print_r($_POST, true));
    }
}

// Récupération des produits pour le select
try {
    $produits = $pdo->query("SELECT id_prod, nom_prod FROM produits ORDER BY nom_prod")->fetchAll();
} catch (PDOException $e) {
    $produits = [];
}

// Récupération des produits avec toutes les infos nécessaires
try {
    $produitsListe = $pdo->query("SELECT 
                                 id_prod, 
                                 nom_prod, 
                                 quant_prod, 
                                 prix_unitaire,
                                 prix_total
                                 FROM produits 
                                 ORDER BY nom_prod")->fetchAll();
} catch (PDOException $e) {
    $produitsListe = [];
    error_log("Erreur SQL produits: " . $e->getMessage());
}

?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-boxes"></i> Mise à jour du stock</h3>
            <div class="section-notice">
                <i class="fas fa-info-circle"></i> Les modifications sont appliquées immédiatement
            </div>
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

        <div class="supply-grid">
            <div class="supply-form">
                <form method="POST">
                    <input type="hidden" name="ajouter_fourniture" value="1">
                    
                    <div class="form-group">
                        <label for="produit">Produit *</label>
                        <select name="produit" id="produit" class="form-select" required>
                            <option value="">Sélectionner un produit</option>
                            <?php foreach($produits as $produit): ?>
                                <option value="<?= $produit['id_prod'] ?>"><?= htmlspecialchars($produit['nom_prod']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantite">Quantité *</label>
                        <input type="number" name="quantite" id="quantite" class="form-input" min="1" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="reset-btn">
                            <i class="fas fa-eraser"></i> Effacer
                        </button>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="supply-history">
                <h4><i class="fas fa-list-ol"></i> État du stock</h4>
                
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Montant total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produitsListe)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucun produit enregistré</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($produitsListe as $produit): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($produit['nom_prod']) ?></td>
                                        <td><?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                                        <td><?= htmlspecialchars($produit['quant_prod']) ?></td>
                                        <td><?= number_format($produit['prix_total'], 0, ',', ' ') ?> FCFA</td>
                                        <td class="actions">
                                            <a href="details_produit.php?id=<?= $produit['id_prod'] ?>" 
                                               class="action-btn view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
    max-width: 1200px;
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
}

.section-header h3 {
    color: var(--text);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.section-notice {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
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

.supply-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
}

.supply-form {
    background: rgba(15, 15, 35, 0.7);
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid rgba(106, 0, 255, 0.2);
}

.supply-history {
    background: rgba(15, 15, 35, 0.7);
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid rgba(106, 0, 255, 0.2);
}

.supply-history h4 {
    color: var(--text);
    margin-top: 0;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

.form-input, .form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
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

.data-table-container {
    overflow-x: auto;
    margin-top: 1.5rem;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 8px;
    overflow: hidden;
}

.data-table th {
    padding: 1rem;
    text-align: left;
    background: rgba(106, 0, 255, 0.5);
    color: white;
    font-weight: 500;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(15, 15, 35, 0.7);
    color: var(--text);
}

.data-table tr:hover td {
    background: rgba(106, 0, 255, 0.2);
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    transition: all 0.3s;
}

.action-btn.view {
    background: rgba(0, 255, 198, 0.2);
    color: var(--secondary);
}

.action-btn.view:hover {
    background: var(--secondary);
    color: white;
}

.text-center {
    text-align: center;
}

@media (max-width: 900px) {
    .supply-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des champs
    document.querySelectorAll('.form-input, .form-select').forEach(input => {
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
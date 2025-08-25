<?php
$pageTitle = "Nouvelle Commande - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Définir le fuseau horaire pour Dakar (GMT/UTC +0)
date_default_timezone_set('Africa/Dakar');

$userId = $_SESSION['user_id'];

try {
    // Récupérer l'ID client associé à l'utilisateur
    $stmt = $pdo->prepare("SELECT id_client FROM users WHERE id_user = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("Utilisateur non trouvé");
    }
    $clientId = $user['id_client'];

    // Récupérer les produits disponibles
    $produits = $pdo->query("SELECT id_prod, nom_prod, prix_unitaire, quant_prod 
                            FROM produits 
                            WHERE quant_prod > 0 
                            ORDER BY nom_prod")->fetchAll();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produitId = $_POST['produit'] ?? '';
    $quantite = $_POST['quantite'] ?? '';
    
    if ($produitId && $quantite) {
        try {
            // Récupérer le prix du produit
            $stmt = $pdo->prepare("SELECT nom_prod, prix_unitaire FROM produits WHERE id_prod = ?");
            $stmt->execute([$produitId]);
            $produit = $stmt->fetch();

            
            if (!$produit) {
                throw new Exception("Produit introuvable");
            }
            
            
            $montant = $produit['prix_unitaire'] * $quantite;
            $date = date('Y-m-d');
            $heure = date('H:i:s'); // Heure actuelle à Dakar

            // Commencer une transaction
            $pdo->beginTransaction();

            // Insérer la commande (en utilisant l'ID produit comme entier)
            $stmt = $pdo->prepare("INSERT INTO commandes 
                                  (client, produit, quant_com, date, heure, montant) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $clientId, 
                $produitId,// ✅ on enregistre l'id du produit, pas le nom
                $quantite, 
                $date, 
                $heure, 
                $montant
            ]);
            // Et ajouter juste après :
            $lastInsertId = $pdo->lastInsertId(); // Récupérer l'ID de la commande créée

            // Après avoir inséré la commande dans la table `commandes`, ajoutez :
            $stmt = $pdo->prepare("INSERT INTO livraisons 
                (commande_com, client_com, produit_id, produit_com, quantite_liv, date_liv)
                SELECT c.id_com, c.client, p.id_prod, p.nom_prod, 0, c.date
                FROM commandes c
                JOIN produits p ON c.produit = p.id_prod
                WHERE c.id_com = ?");
            $stmt->execute([$lastInsertId]); // $lastInsertId = ID de la nouvelle commande

            // Mettre à jour le stock
            $stmt = $pdo->prepare("UPDATE produits 
                                  SET quant_prod = quant_prod - ? 
                                  WHERE id_prod = ?");
            $stmt->execute([$quantite, $produitId]);

            // Valider la transaction
            $pdo->commit();

            // Redirection avec message de succès
            $_SESSION['success'] = "Commande enregistrée avec succès";
            header("Location: mes_commandes.php");
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Tous les champs sont obligatoires";
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-cart-plus"></i> Nouvelle Commande</h3>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="order-form">
            <input type="hidden" name="client" value="<?= $clientId ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="produit">Produit *</label>
                    <select name="produit" id="produit" required class="form-select">
                        <option value="">Sélectionnez un produit</option>
                        <?php foreach ($produits as $produit): ?>
                            <option value="<?= $produit['id_prod'] ?>" 
                                    data-prix="<?= $produit['prix_unitaire'] ?>"
                                    data-stock="<?= $produit['quant_prod'] ?>">
                                <?= htmlspecialchars($produit['nom_prod']) ?> 
                                (<?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA)
                                - Stock: <?= $produit['quant_prod'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantite">Quantité *</label>
                    <input type="number" name="quantite" id="quantite" min="1" required class="form-input">
                    <small id="stock-info" class="text-muted"></small>
                </div>

                <div class="form-group">
                    <label for="date">Date *</label>
                    <input type="date" name="date" id="date" required class="form-input" 
                           value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="heure">Heure *</label>
                    <input type="time" name="heure" id="heure" required class="form-input"
                           value="<?= date('H:i') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="montant">Montant Total (FCFA) *</label>
                <input type="number" name="montant" id="montant" readonly class="form-input">
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Enregistrer la commande
                </button>
                <button type="reset" class="reset-btn">
                    <i class="fas fa-undo"></i> Réinitialiser
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
    --select-bg: #1e1e3a;
    --select-hover: #2a2a4a;
}

.order-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    border: 1px solid rgba(106, 0, 255, 0.3);
}

.form-row {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    flex: 1;
    min-width: 200px;
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

.text-muted {
    color: var(--text-secondary);
    font-size: 0.85rem;
    display: block;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
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
}

.submit-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(106, 0, 255, 0.3);
}

.reset-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
    border: 1px solid var(--text-secondary);
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.reset-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--text);
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(76, 175, 80, 0.2);
    color: var(--success);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.2);
    color: var(--danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-group {
        min-width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const produitSelect = document.getElementById('produit');
    const quantiteInput = document.getElementById('quantite');
    const montantInput = document.getElementById('montant');
    const stockInfo = document.getElementById('stock-info');

    // Calcul automatique du montant
    function calculateMontant() {
        const selectedOption = produitSelect.options[produitSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const prixUnitaire = parseFloat(selectedOption.getAttribute('data-prix'));
            const quantite = parseInt(quantiteInput.value) || 0;
            montantInput.value = (prixUnitaire * quantite).toFixed(0);
        } else {
            montantInput.value = '';
        }
    }

    // Affichage du stock disponible
    function updateStockInfo() {
        const selectedOption = produitSelect.options[produitSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const stock = parseInt(selectedOption.getAttribute('data-stock'));
            stockInfo.textContent = `Stock disponible: ${stock}`;
            
            // Définir la quantité max
            quantiteInput.max = stock;
        } else {
            stockInfo.textContent = '';
        }
    }

    // Événements
    produitSelect.addEventListener('change', function() {
        updateStockInfo();
        calculateMontant();
    });

    quantiteInput.addEventListener('input', function() {
        const max = parseInt(quantiteInput.max);
        if (parseInt(quantiteInput.value) > max) {
            quantiteInput.value = max;
        }
        calculateMontant();
    });

    // Initialisation
    updateStockInfo();
});
</script>

<?php include 'includes/footer.php'; ?>
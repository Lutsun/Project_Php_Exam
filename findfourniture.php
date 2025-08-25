<?php
$pageTitle = "Historique des fournitures - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Traitement des paramètres de recherche
$date = $_GET['date'] ?? '';
$showAll = isset($_GET['show_all']);

// Requête pour récupérer l'historique des fournitures
try {
    // Requête principale pour récupérer l'historique
    // Remplacer la requête principale par :
if ($showAll) {
    $query = "SELECT 
             h.produit_id,
             h.designation, 
             GREATEST(h.quantite_ajoutee, 0) AS quantite_ajoutee, 
             h.prix_unitaire,
             h.date_operation,
             p.nom_prod
             FROM historique_fournitures h
             JOIN produits p ON h.produit_id = p.id_prod
             ORDER BY h.date_operation DESC";
} else {
    $query = "SELECT 
             h.produit_id,
             h.designation, 
             GREATEST(h.quantite_ajoutee, 0) AS quantite_ajoutee, 
             h.prix_unitaire,
             h.date_operation,
             p.nom_prod
             FROM historique_fournitures h
             JOIN produits p ON h.produit_id = p.id_prod
             WHERE (:date = '' OR DATE(h.date_operation) = :date)
             ORDER BY h.date_operation DESC";
}

    $stmt = $pdo->prepare($query);
    if (!$showAll) {
        $stmt->bindParam(':date', $date);
    }
    $stmt->execute();

    $fournitures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des dates disponibles pour le select
    $dates = $pdo->query("SELECT DISTINCT DATE(date_operation) as date FROM historique_fournitures ORDER BY date DESC")->fetchAll();

} catch (PDOException $e) {
    $errorMessage = "Erreur de base de données : " . $e->getMessage();
    $fournitures = [];
    $dates = [];
}

// Calcul du montant total
$total = 0;
foreach ($fournitures as $fourniture) {
    if ($fourniture['quantite_ajoutee'] > 0) { // Ne compter que les entrées positives
        $total += $fourniture['quantite_ajoutee'] * $fourniture['prix_unitaire'];
    }
}
?>


<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-truck-moving"></i> Historique des fournitures</h3>
            <button id="back-to-top" class="back-to-top-btn">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>

        <div class="search-section">
            <form method="GET" class="search-form glassmorphism">
                <h4><i class="fas fa-calendar-alt"></i> Filtre par date</h4>
                
                <div class="form-group">
                    <label for="date">Date de fourniture</label>
                    <select name="date" id="date" class="form-select">
                        <option value="">Toutes les dates</option>
                        <?php foreach ($dates as $d): ?>
                            <option value="<?= $d['date'] ?>" <?= $date == $d['date'] ? 'selected' : '' ?>>
                                <?= date('d/m/Y', strtotime($d['date'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <a href="?show_all=1" class="action-btn show-all-btn">
                        <i class="fas fa-list-ol"></i> Afficher tout
                    </a>
                    <button type="submit" class="action-btn search-btn">
                        <i class="fas fa-filter"></i> Appliquer le filtre
                    </button>
                </div>
            </form>
        </div>

        <div class="table-section">
            <div class="table-actions">
                <a href="fourniture.php" class="action-btn add-btn">
                    <i class="fas fa-plus"></i> Nouvelle fourniture
                </a>
                <a href="acceuil_admin.php" class="action-btn home-btn">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
            
            <div class="data-table-container glassmorphism">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="far fa-calendar"></i> Date</th>
                            <th><i class="fas fa-tags"></i> Produit</th>
                            <th><i class="fas fa-shipping-fast"></i> Qté fournie</th>
                            <th><i class="fas fa-tag"></i> P.U. (FCFA)</th>
                            <th><i class="fas fa-money-bill-wave"></i> Montant (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fournitures)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucune fourniture enregistrée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fournitures as $fourniture): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($fourniture['date_operation'])) ?></td>
                                    <td><?= htmlspecialchars($fourniture['designation']) ?></td>
                                    <td>
                                        <?php if ($fourniture['quantite_ajoutee'] <= 0): ?>
                                            <span class="badge critical">Sortie</span>
                                        <?php else: ?>
                                            <span class="badge success">+<?= $fourniture['quantite_ajoutee'] ?></span>
                                        <?php endif; ?>
                                        </td>
                                    <td><?= number_format($fourniture['prix_unitaire'], 0, ',', ' ') ?></td>
                                    <td class="amount"><?= number_format($fourniture['quantite_ajoutee'] * $fourniture['prix_unitaire'], 0, ',', ' ') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4" class="total-label">
                            <i class="fas fa-calculator"></i> Montant Total (Entrées seulement)
                        </td>
                        <td class="total-value"><?= number_format($total, 0, ',', ' ') ?></td>
                    </tr>
                    </tfoot>
                </table>
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
    max-width: 1400px;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.glassmorphism {
    background: rgba(26, 26, 46, 0.6);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 8px;
    border: 1px solid rgba(106, 0, 255, 0.2);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}

.search-section {
    margin-bottom: 1.5rem;
}

.search-form {
    padding: 1.5rem;
}

.search-form h4 {
    color: var(--text);
    margin-top: 0;
    margin-bottom: 1.5rem;
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

.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23b8b8d1'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.action-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    text-decoration: none;
}

.action-btn i {
    font-size: 0.9rem;
}

.show-all-btn {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
}

.show-all-btn:hover {
    background: var(--primary);
    color: white;
}

.search-btn {
    background: var(--primary);
    color: white;
}

.search-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.table-section {
    margin-top: 2rem;
}

.table-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.add-btn {
    background: var(--success);
    color: white;
}

.add-btn:hover {
    background: rgba(76, 175, 80, 0.8);
    transform: translateY(-2px);
}

.home-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.home-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.data-table-container {
    overflow-x: auto;
    padding: 5px;
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

.data-table th i {
    margin-right: 0.5rem;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(15, 15, 35, 0.7);
    color: var(--text);
}

.data-table tr:hover td {
    background: rgba(106, 0, 255, 0.1);
}

.amount {
    font-family: 'Courier New', monospace;
    font-weight: bold;
}

.total-label {
    font-weight: bold;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
}

.total-value {
    font-weight: bold;
    color: var(--secondary);
    font-size: 1.1rem;
}

.text-center {
    text-align: center;
}

.back-to-top-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
    z-index: 99;
    box-shadow: 0 5px 20px rgba(106, 0, 255, 0.3);
}

.back-to-top-btn.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top-btn:hover {
    transform: translateY(-5px);
    background: var(--primary-light);
    box-shadow: 0 8px 25px rgba(106, 0, 255, 0.4);
}

@media (max-width: 768px) {
    .form-actions, .table-actions {
        flex-direction: column;
    }
    
    .data-table th, .data-table td {
        padding: 0.75rem;
    }
}
/* Ajouter ces styles dans la section <style> */
.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.85rem;
}

.badge.success {
    background: rgba(0, 200, 83, 0.1);
    color: #00c853;
    border: 1px solid #00c853;
}

.badge.critical {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}
</style>

<script>
// Bouton Retour en haut
document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });

    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});


// Bouton Retour en haut
document.addEventListener('DOMContentLoaded', function() {
    const backToTopBtn = document.getElementById('back-to-top');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });

    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

</script>

<?php include 'includes/footer.php'; ?>
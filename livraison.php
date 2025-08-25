<?php
$pageTitle = "Suivi des Livraisons - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Activation du mode erreur PDO

// Traitement automatique des livraisons
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_livraison'])) {
    $commande_id = $_POST['commande_id'];
    $quantite_livree = $_POST['quantite_livree'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Récupérer les infos de la commande
        $stmt = $pdo->prepare("SELECT c.client, c.produit, p.id_prod, p.prix_unitaire 
                              FROM commandes c
                              JOIN produits p ON (c.produit = p.id_prod OR c.produit = p.nom_prod)
                              WHERE c.id_com = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            throw new Exception("Commande introuvable");
        }
        
        // 2. Mettre à jour la livraison avec produit_id inclus
        $stmt = $pdo->prepare("INSERT INTO livraisons 
            (commande_com, client_com, produit_id, produit_com, quantite_liv, montant_com, date_liv, heure_liv)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())
            ON DUPLICATE KEY UPDATE 
            quantite_liv = quantite_liv + VALUES(quantite_liv)");
        $stmt->execute([
            $commande_id,
            $commande['client'],
            $commande['id_prod'],
            $commande['produit'],
            $quantite_livree,
            $quantite_livree * $commande['prix_unitaire']
        ]);
        
        // 3. Mettre à jour le stock
        $stmt = $pdo->prepare("UPDATE produits SET quant_prod = quant_prod - ? WHERE id_prod = ?");
        $stmt->execute([$quantite_livree, $commande['id_prod']]);
        
        $pdo->commit();
        $_SESSION['success'] = "Livraison enregistrée et stock mis à jour !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }
    
    header("Location: livraison.php");
    exit();
}

// Récupération des données avec jointure corrigée
$query = "SELECT 
        c.id_com,
        cl.id_client,
        CONCAT(cl.nom_cli, ' ', cl.prenom_cli) AS client_nom,
        p.id_prod,
        p.nom_prod,
        c.quant_com,
        IFNULL(l.quantite_liv, 0) AS quantite_livree,
        c.montant,
        c.date AS date_commande,
        l.date_liv,
        l.heure_liv,
        p.prix_unitaire,
        CASE
            WHEN l.id_liv IS NULL THEN 'Non livrée'
            WHEN l.quantite_liv = 0 THEN 'En préparation'
            WHEN l.quantite_liv < c.quant_com THEN 'Partiellement livrée'
            ELSE 'Livrée'
        END AS statut
      FROM commandes c
      JOIN clients cl ON c.client = cl.id_client
      JOIN produits p ON (c.produit = p.id_prod OR c.produit = p.nom_prod)
      LEFT JOIN livraisons l ON c.id_com = l.commande_com
      ORDER BY c.date DESC, c.heure DESC";

$livraisons = $pdo->query($query)->fetchAll();
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h1><i class="fas fa-truck"></i> Commandes livrées</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="livraison-grid">
            <?php foreach ($livraisons as $liv): ?>
                <div class="livraison-card <?= strtolower(str_replace(' ', '-', $liv['statut'])) ?>">
                    <div class="livraison-header">
                        <h3>Commande #<?= $liv['id_com'] ?></h3>
                        <span class="statut-badge <?= strtolower($liv['statut']) ?>">
                            <?= $liv['statut'] ?>
                        </span>
                    </div>
                    
                    <div class="livraison-body">
                        <div class="livraison-info">
                            <span class="label">Client:</span>
                            <span class="value"><?= htmlspecialchars($liv['client_nom']) ?></span>
                        </div>
                        
                        <div class="livraison-info">
                            <span class="label">Produit:</span>
                            <span class="value"><?= htmlspecialchars($liv['nom_prod']) ?></span>
                        </div>
                        
                        <div class="livraison-progress">
                            <div class="progress-bar" 
                                 style="width: <?= min(100, ($liv['quantite_livree'] / $liv['quant_com']) * 100) ?>%">
                                <?= $liv['quantite_livree'] ?> / <?= $liv['quant_com'] ?>
                            </div>
                        </div>
                        
                        <div class="livraison-details">
                            <div class="detail">
                                <span class="label">Prix unitaire:</span>
                                <span class="value"><?= number_format($liv['prix_unitaire'], 2, ',', ' ') ?> FCFA</span>
                            </div>
                            
                            <div class="detail">
                                <span class="label">Total:</span>
                                <span class="value"><?= number_format($liv['montant'], 0, ',', ' ') ?> FCFA</span>
                            </div>
                            
                            <?php if ($liv['date_liv']): ?>
                                <div class="detail">
                                    <span class="label">Livré le:</span>
                                    <span class="value"><?= date('d/m/Y', strtotime($liv['date_liv'])) ?> à <?= $liv['heure_liv'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($liv['statut'] != 'Livrée'): ?>
                        <form method="POST" class="livraison-form">
                            <input type="hidden" name="commande_id" value="<?= $liv['id_com'] ?>">
                            
                            <div class="form-group">
                                <label>Quantité à livrer:</label>
                                <input type="number" name="quantite_livree" 
                                       value="<?= $liv['quant_com'] - $liv['quantite_livree'] ?>" 
                                       min="1" max="<?= $liv['quant_com'] - $liv['quantite_livree'] ?>" required>
                            </div>
                            
                            <button type="submit" name="valider_livraison" class="btn btn-primary">
                                <i class="fas fa-check"></i> Enregistrer livraison
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="livraison-complete">
                            <i class="fas fa-check-circle"></i> Commande complètement livrée
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

  
<style>
/* Styles existants de commande_en_cours.php */
/* Ajout des styles spécifiques aux livraisons */

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

/* Section Header */
.section-header {
    background: var(--darker);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--primary-dark);
}

.section-header h3 {
    color: var(--primary-light);
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Filter Form */
.filter-form {
    background: var(--darker);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    align-items: end;
}

.filter-group {
    position: relative;
}

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.filter-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-input:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.2);
    outline: none;
}

.select-wrapper {
    position: relative;
}

.select-wrapper i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: var(--text-secondary);
}

.filter-select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    appearance: none;
    transition: all 0.3s ease;
}

.filter-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.2);
    outline: none;
}

.filter-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    height: fit-content;
}

.filter-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(106, 0, 255, 0.3);
}

.filter-btn.pulse:hover {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(106, 0, 255, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(106, 0, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(106, 0, 255, 0); }
}

/* Data Table Container */
.data-table-container {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 2rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.glass-effect {
    background: var(--glass);
    backdrop-filter: blur(10px);
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table th {
    padding: 1.25rem 1rem;
    text-align: left;
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: white;
    font-weight: 500;
    position: sticky;
    top: 0;
    z-index: 10;
}

.data-table th.sortable {
    cursor: pointer;
    transition: all 0.3s;
}

.data-table th.sortable:hover {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
}

.data-table th i {
    margin-left: 0.5rem;
    opacity: 0.6;
    transition: all 0.3s;
}

.data-table th.sortable:hover i {
    opacity: 1;
}

.data-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background: var(--glass);
    color: var(--text);
    transition: all 0.3s ease;
}

.data-table tr.hover-effect:hover td {
    background: rgba(106, 0, 255, 0.1);
    transform: scale(1.01);
}

/* Table Elements */
.badge.commande-id {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-family: monospace;
}

.date-wrapper {
    display: flex;
    flex-direction: column;
}

.date {
    font-weight: 500;
}

.time {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.client-info, .product-info {
    display: flex;
    flex-direction: column;
}

.client-name, .product-name {
    font-weight: 500;
}

.client-id, .product-id {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.quantity-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: rgba(0, 200, 83, 0.1);
    color: var(--secondary);
    border-radius: 50px;
    font-weight: 500;
    text-align: center;
    min-width: 40px;
}

.price {
    font-weight: 600;
    color: var(--secondary);
}

.price small {
    font-size: 0.85rem;
    color: var(--text-secondary);
    font-weight: normal;
}

/* Actions */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

.action-btn i {
    font-size: 0.95rem;
}

.action-btn.view {
    background: rgba(0, 150, 136, 0.1);
    color: #009688;
}

.action-btn.view:hover {
    background: #009688;
    color: white;
    transform: translateY(-2px);
}

.action-btn.edit {
    background: rgba(106, 0, 255, 0.1);
    color: var(--primary-light);
}

.action-btn.edit:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.action-btn.delivery {
    background: rgba(0, 200, 83, 0.1);
    color: #00c853;
}

.action-btn.delivery:hover {
    background: #00c853;
    color: white;
    transform: translateY(-2px);
}

.tooltip {
    position: relative;
}

.tooltip::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--darker);
    color: var(--text);
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
    z-index: 100;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.tooltip:hover::after {
    opacity: 1;
    visibility: visible;
    bottom: calc(100% + 5px);
}

/* No Data */
.no-data {
    padding: 3rem;
    text-align: center;
    color: var(--text-secondary);
}

.no-data i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-data p {
    margin: 0;
    font-size: 1.1rem;
}

/* Pagination */
.pagination {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    align-items: center;
    margin-top: 2rem;
}

.pagination-info {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link {
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link.prev, .page-link.next {
    background: rgba(106, 0, 255, 0.1);
    color: var(--primary-light);
}

.page-link.prev:hover, .page-link.next:hover {
    background: var(--primary);
    color: white;
}

.page-numbers {
    display: flex;
    gap: 0.25rem;
}

.page-number {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    min-width: 40px;
    text-align: center;
}

.page-number:hover {
    background: rgba(106, 0, 255, 0.1);
    color: var(--primary-light);
}

.page-number.active {
    background: var(--primary);
    color: white;
}

.page-dots {
    padding: 0.75rem 0.5rem;
    color: var(--text-secondary);
}

.per-page-select {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.per-page-select select {
    padding: 0.5rem 0.75rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.9rem;
}

/* Animations */
.table-row-animation {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.5s forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .section-header {
        padding: 1rem;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .data-table th, .data-table td {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
    }
}

@media (max-width: 480px) {
    .page-numbers {
        display: none;
    }
}

.status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.85rem;
    text-align: center;
    min-width: 100px;
}

.status-delivered {
    background: rgba(0, 200, 83, 0.1);
    color: #00c853;
    border: 1px solid #00c853;
}

.status-pending {
    background: rgba(255, 171, 0, 0.1);
    color: #ffab00;
    border: 1px solid #ffab00;
}

.status-not-delivered {
    background: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
    border: 1px solid #ff6b6b;
}

.badge.livraison-id {
    background: rgba(0, 150, 136, 0.2);
    color: #009688;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-weight: 500;
    font-family: monospace;
}

.client-address {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
    display: block;
}

.action-btn.validate {
    background: rgba(0, 200, 83, 0.1);
    color: #00c853;
}

.action-btn.validate:hover {
    background: #00c853;
    color: white;
    transform: translateY(-2px);
}


/* Styles spécifiques pour la page de livraison */
.livraison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.livraison-card {
    background: var(--glass);
    border-radius: 10px;
    padding: 20px;
    border: 1px solid var(--glass-border);
    transition: transform 0.3s;
}

.livraison-card:hover {
    transform: translateY(-5px);
}

.livraison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.livraison-header h3 {
    margin: 0;
    color: var(--primary-light);
}

.statut-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
}

.statut-badge.non-livrée {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
}

.statut-badge.en-préparation {
    background: rgba(255, 171, 0, 0.2);
    color: #ffab00;
}

.statut-badge.partiellement-livrée {
    background: rgba(0, 145, 255, 0.2);
    color: #0091ff;
}

.statut-badge.livrée {
    background: rgba(0, 200, 83, 0.2);
    color: #00c853;
}

.livraison-info {
    margin-bottom: 10px;
}

.livraison-info .label {
    font-weight: bold;
    display: inline-block;
    width: 80px;
    color: var(--text-secondary);
}

.livraison-progress {
    height: 20px;
    background: rgba(0,0,0,0.1);
    border-radius: 10px;
    margin: 15px 0;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
}

.livraison-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 15px;
}

.livraison-details .detail {
    font-size: 0.9rem;
}

.livraison-details .label {
    color: var(--text-secondary);
    display: block;
    font-size: 0.8rem;
}

.livraison-form {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.livraison-form .form-group {
    margin-bottom: 10px;
}

.livraison-form label {
    display: block;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.livraison-form input {
    width: 100%;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid var(--primary);
    background: rgba(0,0,0,0.2);
    color: white;
}

.livraison-complete {
    margin-top: 15px;
    padding: 10px;
    background: rgba(0, 200, 83, 0.1);
    border-radius: 5px;
    text-align: center;
    color: #00c853;
}

.livraison-complete i {
    margin-right: 5px;
}

</style>

<script>
// Mise à jour du nombre d'éléments par page
function updatePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

// Animation des lignes du tableau
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.table-row-animation');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Tri des colonnes
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            // Implémentez ici la logique de tri
            console.log('Trier par', sortField);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
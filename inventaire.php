<?php
$pageTitle = "Inventaire - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Récupération des paramètres
$search = $_GET['search'] ?? '';
$perPage = $_GET['per_page'] ?? 15;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Requête pour récupérer les produits en stock
try {
    $query = "SELECT p.id_prod, p.code_prod, p.nom_prod, p.description,
                 p.prix_unitaire, 
                 GREATEST(p.quant_prod, 0) AS quant_prod,
                 (p.prix_unitaire * GREATEST(p.quant_prod, 0)) AS prix_total
          FROM produits p
          WHERE p.nom_prod LIKE :search
          OR p.code_prod LIKE :search   
          OR p.description LIKE :search
          ORDER BY p.nom_prod ASC
          LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%'.$search.'%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul du montant total du stock
    $totalQuery =  "SELECT SUM(prix_unitaire * GREATEST(quant_prod, 0)) AS montant_total FROM produits";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute();
    $montantTotalStock = $totalStmt->fetchColumn();

    // Calcul du total des produits vendus (requête à adapter selon votre structure)
    $salesQuery = "SELECT SUM(montant) AS total_vendu FROM commandes";
    $salesStmt = $pdo->prepare($salesQuery);
    $salesStmt->execute();
    $totalVendu = $salesStmt->fetchColumn() ?? 0;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-boxes"></i> Inventaire du stock</h3>
            <div class="section-actions">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Rechercher un produit..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                </form>
                <div class="action-buttons">
                    <div class="per-page-select">
                        <select class="per-page" onchange="updatePerPage(this.value)">
                            <option value="15" <?= $perPage == 15 ? 'selected' : '' ?>>15 lignes</option>
                            <option value="30" <?= $perPage == 30 ? 'selected' : '' ?>>30 lignes</option>
                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 lignes</option>
                        </select>
                    </div>
                    <button onclick="window.print()" class="print-btn">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Produits en stock</span>
                    <span class="stat-value"><?= count($products) ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Valeur totale</span>
                    <span class="stat-value"><?= number_format($montantTotalStock, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Ventes totales</span>
                    <span class="stat-value"><?= number_format($totalVendu, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
        </div>

        <!-- Tableau des produits -->
        <div class="data-table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th class="sortable">Désignation <i class="fas fa-sort"></i></th>
                        <th>Description</th>
                        <th class="text-right">Prix Unitaire</th>
                        <th class="text-right">Quantité</th>
                        <th class="text-right">Prix Totale(Fcfa)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="text-center no-data">
                                <i class="fas fa-box-open"></i>
                                <p>Aucun produit trouvé</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="table-row-animation hover-effect">
                                <td>
                                    <span class="product-code"><?= htmlspecialchars($product['code_prod']) ?></span>
                                </td>
                                <td>
                                    <span class="product-name"><?= htmlspecialchars($product['nom_prod']) ?></span>
                                </td>
                                <td>
                                    <span class="product-category"><?= htmlspecialchars($product['description']) ?></span>
                                </td>
                                <td class="text-right">
                                    <span class="price"><?= number_format($product['prix_unitaire'], 0, ',', ' ') ?> FCFA</span>
                                </td>
                               
                                <td class="text-right">
                                <?php if ($product['quant_prod'] <= 0): ?>
                                    <span class="quantity critical">Épuisé</span>
                                <?php else: ?>
                                    <span class="quantity <?= $product['quant_prod'] < 10 ? 'low-stock' : '' ?>">
                                <?= $product['quant_prod'] ?>
                                <?php if($product['quant_prod'] < 10): ?>
                                    <i class="fas fa-exclamation-triangle warning-icon"></i>
                                <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <span class="total-value"><?= number_format($product['prix_total'], 0, ',', ' ') ?> FCFA</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info">
                Affichage de <?= min(($page-1)*$perPage+1, count($products)) ?> à <?= min($page*$perPage, count($products)) ?> sur <?= count($products) ?> produits
            </div>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link prev">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
                
                <div class="page-numbers">
                    <?php 
                    $totalPages = ceil(count($products) / $perPage);
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) echo '<span class="page-dots">...</span>';
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" 
                           class="page-number <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor;
                    
                    if ($endPage < $totalPages) echo '<span class="page-dots">...</span>';
                    ?>
                </div>
                
                <?php if ($page * $perPage < count($products)): ?>
                    <a href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link next">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
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

/* Section Header */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h3 {
    color: var(--primary-light);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

/* Search Form */
.search-form {
    flex: 1;
    min-width: 300px;
}

.search-box {
    position: relative;
    max-width: 400px;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: var(--darker);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-input:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.2);
    outline: none;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.per-page-select {
    position: relative;
    min-width: 120px;
}

.per-page-select select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--darker);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    appearance: none;
    cursor: pointer;
    transition: all 0.3s;
}

.per-page-select select:hover {
    background: var(--dark);
    border-color: var(--primary-light);
}

.print-btn {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
    border: 1px solid var(--primary);
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.print-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(106, 0, 255, 0.3);
}

/* Stats Cards */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(106, 0, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--primary-light);
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-title {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.stat-value {
    color: var(--text);
    font-size: 1.5rem;
    font-weight: 600;
}

/* Data Table */
.glass-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 2rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

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
    transition: all 0.3s;
}

.data-table tr.hover-effect:hover td {
    background: rgba(106, 0, 255, 0.1);
    transform: scale(1.01);
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* Table Elements */
.product-code {
    font-family: monospace;
    background: rgba(106, 0, 255, 0.1);
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    color: var(--primary-light);
}

.product-name {
    font-weight: 500;
}

.product-category, .product-type {
    padding: 0.35rem 0.75rem;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.05);
}

.price {
    font-weight: 500;
}

.quantity {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
    min-width: 50px;
    text-align: center;
}

.quantity.low-stock {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
}

.quantity-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: 500;
    text-align: center;
    min-width: 50px;
}

.quantity-badge.warning {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
    border: 1px solid var(--warning);
}

.quantity-badge.critical {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.quantity.critical {
    color: var(--danger);
    font-weight: bold;
}

.warning-icon {
    color: var(--warning);
    margin-left: 0.25rem;
}

.total-value {
    font-weight: 600;
    color: var(--secondary);
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
    .stats-cards {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .search-form {
        width: 100%;
    }
    
    .search-box {
        max-width: 100%;
    }
    
    .data-table th, .data-table td {
        padding: 1rem 0.75rem;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .page-numbers {
        display: none;
    }
    
    .action-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .per-page-select, .print-btn {
        width: 100%;
    }
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
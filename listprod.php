<?php
$pageTitle = "Boutique - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';


// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Traitement de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_panier'])) {
    $id_prod = $_POST['id_prod'];
    $quantite = $_POST['quantite'] ?? 1;
    
    // Vérifier si le produit existe déjà dans le panier
    if (isset($_SESSION['panier'][$id_prod])) {
        $_SESSION['panier'][$id_prod] += $quantite;
    } else {
        $_SESSION['panier'][$id_prod] = $quantite;
    }
    
    // Redirection pour éviter le rechargement du formulaire
    header("Location: listprod.php");
    exit();
}

// Récupération des paramètres
$search = $_GET['search'] ?? '';
$perPage = $_GET['per_page'] ?? 12;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Requête pour récupérer les produits
try {
    $query = "SELECT p.id_prod, p.code_prod, p.nom_prod, 
                 GREATEST(p.quant_prod, 0) AS quant_prod,
                 p.prix_unitaire, p.description, p.fournisseur AS fournisseur_nom,
                 p.photo
          FROM produits p 
          WHERE p.nom_prod LIKE :search 
          OR p.description LIKE :search
          OR p.fournisseur LIKE :search
          ORDER BY p.nom_prod ASC
          LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%'.$search.'%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compte total pour la pagination
    $countQuery = "SELECT COUNT(*) FROM produits 
                   WHERE nom_prod LIKE :search 
                   OR description LIKE :search
                   OR fournisseur LIKE :search";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindValue(':search', '%'.$search.'%', PDO::PARAM_STR);
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
    $products = [];
    $totalProducts = 0;
}
?>

<div class="shop-container">
    <div class="shop-header">
        <h2><i class="fas fa-store"></i> Notre Boutique</h2>
        <div class="shop-controls">
            <form method="GET" class="shop-search">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Rechercher un produit..." 
                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                    <button type="submit" class="search-icon-btn">
                        <i class="fas fa-search search-icon"></i>
                    </button>
                </div>
            </form>
            <div class="view-options">
                <a href="mes_commandes.php" class="cart-icon-btn" title="Voir mon panier">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if (!empty($_SESSION['panier'])): ?>
                        <span class="cart-count"><?= array_sum($_SESSION['panier']) ?></span>
                    <?php endif; ?>
                </a>
                <div class="per-page-select">
                    <select class="per-page" onchange="updatePerPage(this.value)">
                        <option value="12" <?= $perPage == 12 ? 'selected' : '' ?>>12 produits</option>
                        <option value="24" <?= $perPage == 24 ? 'selected' : '' ?>>24 produits</option>
                        <option value="36" <?= $perPage == 36 ? 'selected' : '' ?>>36 produits</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="product-grid">
        <?php if (empty($products)): ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <p>Aucun produit trouvé</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card animate__animated animate__fadeIn">
                    <div class="product-badge">
                        <?php if ($product['quant_prod'] <= 0): ?>
                            <span class="badge critical">Épuisé</span>
                        <?php elseif ($product['quant_prod'] < 5): ?>
                            <span class="badge warning">Bientôt épuisé</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-image">
                        <?php if (!empty($product['photo']) && file_exists($product['photo']) && $product['photo'] !== 'assets/images/default-product.png'): ?>
                            <img src="<?= htmlspecialchars($product['photo']) ?>" alt="<?= htmlspecialchars($product['nom_prod']) ?>">
                        <?php else: ?>
                            <div class="no-image-icon">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['nom_prod']) ?></h3>
                        <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</p>
                        
                        <div class="product-meta">
                            <span class="product-price"><?= number_format($product['prix_unitaire'], 0, ',', ' ') ?> FCFA</span>
                            <span class="product-stock">
                                <i class="fas fa-box"></i> 
                                <?= $product['quant_prod'] > 0 ? $product['quant_prod'] . ' en stock' : 'Indisponible' ?>
                            </span>
                        </div>
                    </div>
                                       
                    <div class="product-actions">
                        <form method="POST" class="add-to-cart-form">
                            <input type="hidden" name="id_prod" value="<?= $product['id_prod'] ?>">
                            <input type="hidden" name="quantite" value="1">
                            <button type="submit" name="ajouter_panier" class="add-to-cart" <?= $product['quant_prod'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i> Ajouter
                            </button>
                        </form>
                        <?php if ($isAdmin): ?>
                            <a href="updateprod.php?id=<?= $product['id_prod'] ?>" class="details-btn">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                        <?php else: ?>
                            <a href="detailsprod.php?id=<?= $product['id_prod'] ?>" class="details-btn">
                                <i class="fas fa-eye"></i> Détails
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link">
                <i class="fas fa-chevron-left"></i> Précédent
            </a>
        <?php endif; ?>
        
        <span class="page-info">
            Page <?= $page ?> sur <?= ceil($totalProducts / $perPage) ?>
        </span>
        
        <?php if ($page * $perPage < $totalProducts): ?>
            <a href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link">
                Suivant <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['panier'])): ?>
<div class="validate-cart-container">
    <form action="valider_panier.php" method="POST">
        <button type="submit" class="validate-cart-btn">
            <i class="fas fa-check-circle"></i> Valider le panier
        </button>
    </form>
</div>
<?php endif; ?>
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
    --card-bg: #1a1a2e;
    --card-hover: #2a2a4a;
}

.shop-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.shop-header {
    display: flex;
    flex-direction: column;
    margin-bottom: 2rem;
}

.shop-header h2 {
    color: var(--text);
    font-size: 2rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.shop-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.shop-search {
    flex-grow: 1;
    max-width: 400px;
}

.search-box {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    background: var(--darker);
    border: 1px solid var(--primary);
    border-radius: 30px;
    color: var(--text);
    font-size: 1rem;
    transition: all 0.3s;
}

.search-input:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.3);
    outline: none;
}

.search-icon-btn {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 1rem;
}

.view-options {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.per-page-select {
    position: relative;
    min-width: 120px;
}

.per-page {
    appearance: none;
    background: var(--darker);
    border: 1px solid var(--primary);
    color: var(--text);
    padding: 0.5rem 2rem 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.per-page:hover {
    background: var(--card-hover);
    border-color: var(--primary-light);
}

.select-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: var(--text-secondary);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.product-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(106, 0, 255, 0.2);
    background: var(--card-hover);
}

.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge.warning {
    background: rgba(255, 193, 7, 0.2);
    color: var(--warning);
    border: 1px solid var(--warning);
}

.badge.critical {
    background: rgba(255, 107, 107, 0.2);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.product-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--darker);
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.no-image {
    color: var(--text-secondary);
    font-size: 3rem;
}

.product-info {
    padding: 1.25rem;
    flex-grow: 1;
}

.product-name {
    color: var(--text);
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.product-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.product-price {
    color: var(--primary-light);
    font-weight: 700;
    font-size: 1.1rem;
}

.product-stock {
    color: var(--text-secondary);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.product-actions {
    display: flex;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.add-to-cart, .details-btn {
    flex: 1;
    padding: 0.75rem;
    border: none;
    background: transparent;
    color: var(--text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.add-to-cart {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
}

.add-to-cart:hover {
    background: var(--primary);
    color: white;
}

.add-to-cart:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
}

.details-btn {
    background: rgba(0, 255, 198, 0.1);
    color: var(--secondary);
}

.details-btn:hover {
    background: var(--secondary);
    color: var(--dark);
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

.no-products i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--primary-light);
}

.no-products p {
    font-size: 1.2rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1.5rem;
    margin-top: 3rem;
    padding: 1rem;
}

.page-link {
    color: var(--primary-light);
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.3s;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-link:hover {
    background: rgba(106, 0, 255, 0.2);
    color: var(--secondary);
}

.page-info {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Animations */
.animate__animated {
    animation-duration: 0.5s;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate__fadeIn {
    animation-name: fadeIn;
}

.cart-icon-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text);
    font-size: 1.2rem;
    margin-right: 1rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s;
}

.cart-icon-btn:hover {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
}

.cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
}

.add-to-cart-form {
    flex: 1;
    display: flex;
}

.validate-cart-container {
    margin-top: 2rem;
    text-align: right;
}

.validate-cart-btn {
    background: var(--success);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.validate-cart-btn:hover {
    background: #3d8b40;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .shop-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .shop-search {
        max-width: 100%;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
}

@media (max-width: 480px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        flex-direction: column;
        gap: 0.5rem;
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

// Animation des cartes
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.product-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
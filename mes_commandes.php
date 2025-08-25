<?php
$pageTitle = "Mes Commandes - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Définir le fuseau horaire pour Dakar (UTC+0)
date_default_timezone_set('Africa/Dakar');

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['profil'] ?? '') === 'Admin';

// Récupérer le client_id à partir de l'user_id
$stmt = $pdo->prepare("SELECT id_client FROM users WHERE id_user = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$clientId = $user['id_client'] ?? null;

$search = $_GET['search'] ?? '';
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$perPage = max(1, min(100, $perPage));
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

try {
    $query = "SELECT c.id_com, c.date, c.heure, c.montant,
                     CASE 
                        WHEN p.nom_prod IS NOT NULL THEN p.nom_prod
                        ELSE (SELECT nom_prod FROM produits WHERE id_prod = CAST(c.produit AS UNSIGNED) LIMIT 1)
                     END AS nom_produit,
                     c.quant_com AS quantite,
                     cl.nom_cli AS client_nom,
                     cl.prenom_cli AS client_prenom
              FROM commandes c
              LEFT JOIN produits p ON c.produit = p.nom_prod
              LEFT JOIN clients cl ON c.client = cl.id_client";

    if (!$isAdmin) {
        $query .= " WHERE c.client = :client_id";
    }

    $query .= " ORDER BY c.date DESC, c.heure DESC
                LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);

    if (!$isAdmin) {
        $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    }

    $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countQuery = "SELECT COUNT(*) FROM commandes c";
    if (!$isAdmin) {
        $countQuery .= " WHERE c.client = :client_id";
    }

    $countStmt = $pdo->prepare($countQuery);
    if (!$isAdmin) {
        $countStmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    }
    $countStmt->execute();
    $totalCommandes = (int)$countStmt->fetchColumn();
    $totalPages = $totalCommandes > 0 ? ceil($totalCommandes / $perPage) : 1;

    $statsQuery = "SELECT COUNT(*) as total_commandes,
                          COALESCE(SUM(montant), 0) as montant_total,
                          MIN(date) as premiere_commande,
                          MAX(date) as derniere_commande
                   FROM commandes";

    if (!$isAdmin) {
        $statsQuery .= " WHERE client = :client_id";
    }

    $statsStmt = $pdo->prepare($statsQuery);
    if (!$isAdmin) {
        $statsStmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    }
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-shopping-bag"></i> <?= $isAdmin ? 'Toutes les Commandes' : 'Mes Commandes' ?></h3>
            <div class="section-actions">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Rechercher une commande..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                </form>
                <div class="action-buttons">
                    <div class="per-page-select">
                        <select class="per-page" onchange="updatePerPage(this.value)">
                            <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10 lignes</option>
                            <option value="20" <?= $perPage == 20 ? 'selected' : '' ?>>20 lignes</option>
                            <option value="30" <?= $perPage == 30 ? 'selected' : '' ?>>30 lignes</option>
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
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Commandes totales</span>
                    <span class="stat-value"><?= $stats['total_commandes'] ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Dépenses totales</span>
                    <span class="stat-value"><?= number_format($stats['montant_total'], 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Dernière commande</span>
                    <span class="stat-value"><?= $stats['derniere_commande'] ? date('d/m/Y', strtotime($stats['derniere_commande'])) : 'Aucune' ?></span>
                </div>
            </div>
        </div>

        
        <!-- Tableau des commandes -->
        <div class="data-table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php if ($isAdmin): ?>
                            <th>Client</th>
                        <?php endif; ?>
                        <th>N° Commande</th>
                        <th>Date</th>
                        <th>Produits</th>
                        <th>Quantités</th>
                        <th class="text-right">Montant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="text-center no-data">
                                <i class="fas fa-box-open"></i>
                                <p>Aucune commande trouvée</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $commande): ?>
                            <tr class="table-row-animation hover-effect">
                                <?php if ($isAdmin): ?>
                                    <td><?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?></td>
                                <?php endif; ?>
                                <td>#<?= htmlspecialchars($commande['id_com']) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($commande['date'])) ?><br>
                                    <small><?= date('H:i', strtotime($commande['heure'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($commande['nom_produit']) ?></td>
                                <td><?= htmlspecialchars($commande['quantite']) ?></td>
                                <td class="text-right">
                                    <span class="total-value"><?= number_format($commande['montant'], 0, ',', ' ') ?> FCFA</span>
                                </td>
                                <td>
                                    <a href="mes_factures.php?commande_id=<?= $commande['id_com'] ?>" class="details-btn">
                                        <i class="fas fa-file-invoice"></i> Facture
                                    </a>
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
                Affichage de <?= $totalCommandes > 0 ? min(($page-1)*$perPage+1, $totalCommandes) : 0 ?> 
                à <?= $totalCommandes > 0 ? min($page*$perPage, $totalCommandes) : 0 ?> 
                sur <?= $totalCommandes ?> commandes
            </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination-controls">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link prev">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>
            
            <div class="page-numbers">
                <?php 
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
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="page-link next">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Styles harmonisés avec les autres pages */
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
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.data-section {
    background: var(--dark);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

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

.total-value {
    font-weight: 600;
    color: var(--secondary);
}

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

.details-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    background: rgba(0, 255, 198, 0.1);
    color: var(--secondary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.details-btn:hover {
    background: var(--secondary);
    color: var(--dark);
}

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
    
    // Recherche automatique après 500ms de pause
    const searchInput = document.querySelector('.search-input');
    let searchTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            this.closest('form').submit();
        }, 500);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
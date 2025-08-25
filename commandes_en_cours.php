<?php
$pageTitle = "Commandes en cours - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Définir le fuseau horaire pour Dakar (GMT/UTC +0)
date_default_timezone_set('Africa/Dakar');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = ($_SESSION['profil'] ?? '') === 'Admin';

// Initialisation des variables
$search = $_GET['search'] ?? '';
$client_id = $_GET['client_id'] ?? '';
$perPage = max(10, min(100, (int)($_GET['per_page'] ?? 10)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalCommandes = 0;
$commandes = [];

// Traitement de l'annulation de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_commande'])) {
    $commande_id = $_POST['commande_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT produit, quant_com FROM commandes WHERE id_com = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();

        if ($commande) {
            $stmt = $pdo->prepare("UPDATE produits SET quant_prod = quant_prod + ? WHERE id_prod = ?");
            $stmt->execute([$commande['quant_com'], $commande['produit']]);

            $stmt = $pdo->prepare("DELETE FROM commandes WHERE id_com = ?");
            $stmt->execute([$commande_id]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Commande annulée et stock mis à jour avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de l'annulation : " . $e->getMessage();
    }

    header("Location: commande_en_cours.php?" . http_build_query($_GET));
    exit();
}

try {
    $query = "SELECT c.id_com, 
            cl.id_client, cl.nom_cli AS client_nom, cl.prenom_cli AS client_prenom,
            p.nom_prod AS produit_nom,
            c.quant_com, c.montant, c.date, c.heure
            FROM commandes c
            LEFT JOIN clients cl ON c.client = cl.id_client
            LEFT JOIN produits p ON c.produit = p.id_prod
            WHERE (c.id_com LIKE :search 
                OR cl.nom_cli LIKE :search 
                OR cl.prenom_cli LIKE :search
                OR c.produit LIKE :search)
            AND (:client_id = '' OR cl.id_client = :client_id)
            ORDER BY c.date DESC, c.heure DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':client_id', $client_id, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countQuery = "SELECT COUNT(*) 
                   FROM commandes c
                   LEFT JOIN clients cl ON c.client = cl.id_client
                   LEFT JOIN produits p ON c.produit = p.nom_prod
                   WHERE (c.id_com LIKE :search 
                       OR cl.nom_cli LIKE :search 
                       OR cl.prenom_cli LIKE :search
                       OR c.produit LIKE :search)
                   AND (:client_id = '' OR cl.id_client = :client_id)";

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $countStmt->bindValue(':client_id', $client_id, PDO::PARAM_STR);
    $countStmt->execute();
    $totalCommandes = (int)$countStmt->fetchColumn();

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    $commandes = [];
    $totalCommandes = 0;
}

try {
    $clients = $pdo->query("SELECT id_client, nom_cli, prenom_cli FROM clients ORDER BY nom_cli")->fetchAll();
} catch (PDOException $e) {
    $clients = [];
    $_SESSION['error'] = "Erreur lors du chargement des clients : " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-clipboard-list"></i> Commandes en cours</h3>
            <div class="section-actions">
                <form method="GET" class="filter-form">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <div class="input-icon">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search" name="search" placeholder="N° commande, client ou produit" 
                                       value="<?= htmlspecialchars($search) ?>" class="filter-input">
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <div class="select-wrapper">
                                <select id="client_id" name="client_id" class="filter-select">
                                    <option value="">Tous les clients</option>
                                    <?php foreach($clients as $client): ?>
                                        <option value="<?= $client['id_client'] ?>" 
                                            <?= $client_id == $client['id_client'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['nom_cli'].' '.$client['prenom_cli']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        
                        <button type="submit" class="filter-btn pulse">
                            <i class="fas fa-filter"></i> <span>Filtrer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="data-table-container glass-effect">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id_com">N° Commande <i class="fas fa-sort"></i></th>
                        <th class="sortable" data-sort="date">Date <i class="fas fa-sort"></i></th>
                        <th>Client</th>
                        <th>Produit</th>
                        <th class="sortable" data-sort="quant_com">Quantité <i class="fas fa-sort"></i></th>
                        <th class="sortable" data-sort="montant">Total <i class="fas fa-sort"></i></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                        <tr>
                            <td colspan="7" class="text-center no-data">
                                <i class="fas fa-box-open"></i>
                                <p>Aucune commande en cours</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commandes as $commande): ?>
                            <tr class="table-row-animation hover-effect">
                                <td>
                                    <span class="badge commande-id">#<?= $commande['id_com'] ?></span>
                                </td>
                                <td>
                                    <div class="date-wrapper">
                                        <span class="date"><?= date('d/m/Y', strtotime($commande['date'])) ?></span>
                                        <span class="time"><?= $commande['heure'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <span class="client-name"><?= htmlspecialchars($commande['client_nom'].' '.$commande['client_prenom']) ?></span>
                                        <span class="client-id">ID: <?= $commande['id_client'] ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-info">
                                        <span class="product-name"><?= htmlspecialchars($commande['produit_nom']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="quantity-badge"><?= $commande['quant_com'] ?></span>
                                </td>
                                <td>
                                    <span class="price"><?= number_format($commande['montant'], 0, ',', ' ') ?> <small>FCFA</small></span>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="detailscom.php?id=<?= $commande['id_com'] ?>" class="action-btn view tooltip" data-tooltip="Détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="updatecom.php?id=<?= $commande['id_com'] ?>" class="action-btn edit tooltip" data-tooltip="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="commande_id" value="<?= $commande['id_com'] ?>">
                                            <button type="submit" name="annuler_commande" class="action-btn danger tooltip" data-tooltip="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalCommandes > 0): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Affichage de <?= min(($page-1)*$perPage+1, $totalCommandes) ?> à <?= min($page*$perPage, $totalCommandes) ?> sur <?= $totalCommandes ?> commandes
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php 
                        $totalPages = max(1, ceil($totalCommandes / $perPage));
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1) echo '<span class="page-dots">...</span>';
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="page-number <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor;
                        
                        if ($endPage < $totalPages) echo '<span class="page-dots">...</span>';
                        ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-link next">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="per-page-select">
                    <span>Lignes par page:</span>
                    <select class="per-page" onchange="updatePerPage(this.value)">
                        <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                    </select>
                </div>
            </div>
        <?php endif; ?>
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


/* Bouton d'annulation */
.action-btn.danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: none;
}

.action-btn.danger:hover {
    background: var(--danger);
    color: white;
}

/* Messages d'alerte */
.alert {
    padding: 1rem;
    margin: 0 1rem 1.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    text-align: center;
}

.alert-success {
    background: rgba(0, 200, 83, 0.1);
    color: var(--success);
    border: 1px solid var(--success);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

/* Petits ajustements pour les formulaires inline */
form[style="display: inline;"] {
    display: inline-flex !important;
    margin: 0;
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
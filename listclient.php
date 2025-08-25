<?php
$pageTitle = "Liste des Clients - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Récupération des paramètres
$search = $_GET['search'] ?? '';
$perPage = $_GET['per_page'] ?? 15;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Requête pour récupérer les clients
try {
    $query = "SELECT * FROM clients 
              WHERE nom_cli LIKE :search 
              OR prenom_cli LIKE :search
              OR email LIKE :search
              OR telephone LIKE :search
              ORDER BY nom_cli, prenom_cli
              LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%'.$search.'%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Requête pour le nombre total
    $countQuery = "SELECT COUNT(*) FROM clients 
                   WHERE nom_cli LIKE :search 
                   OR prenom_cli LIKE :search
                   OR email LIKE :search
                   OR telephone LIKE :search";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindValue(':search', '%'.$search.'%', PDO::PARAM_STR);
    $countStmt->execute();
    $totalClients = $countStmt->fetchColumn();

    // Statistiques
    $statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN ville = 'Dakar' THEN 1 ELSE 0 END) as dakar_clients
                  FROM clients";
    $statsStmt = $pdo->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Vérification du statut admin
$isAdmin = ($_SESSION['role'] === 'Admin');
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Gestion des clients</h3>
            <div class="section-actions">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Rechercher un client..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                </form>
                <div class="action-buttons">
                    <div class="per-page-select">
                        <select class="form-input per-page" onchange="updatePerPage(this.value)">
                            <option value="15" <?= $perPage == 15 ? 'selected' : '' ?>>15 lignes</option>
                            <option value="30" <?= $perPage == 30 ? 'selected' : '' ?>>30 lignes</option>
                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 lignes</option>
                        </select>
                    </div>
                    <?php if ($isAdmin): ?>
                    <a href="ajoutclient.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn print-btn">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Clients total</span>
                    <span class="stat-value"><?= $stats['total'] ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Administrateurs</span>
                    <span class="stat-value"><?= $stats['admins'] ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-city"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Clients à Dakar</span>
                    <span class="stat-value"><?= $stats['dakar_clients'] ?></span>
                </div>
            </div>
        </div>

        <!-- Tableau des clients -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Adresse</th>
                        <th>Ville</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <?php if ($isAdmin): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 9 : 8 ?>" class="text-center no-data">
                                <i class="fas fa-user-slash"></i>
                                <p>Aucun client trouvé</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= htmlspecialchars($client['id_client']) ?></td>
                                <td><?= htmlspecialchars($client['nom_cli'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($client['prenom_cli']) ?></td>
                                <td><?= htmlspecialchars($client['adresse_cli']) ?></td>
                                <td><?= htmlspecialchars($client['ville']) ?></td>
                                <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($client['telephone']) ?></td>
                                <td>
                                    <span class="badge <?= $client['statut'] === 'admin' ? 'badge-success' : 'badge-primary' ?>">
                                        <?= ucfirst($client['statut']) ?>
                                    </span>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td class="actions">
                                    <a href="updateclient.php?id=<?= $client['id_client'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="deleteclient.php?id=<?= $client['id_client'] ?>" class="btn btn-sm btn-danger" title="Supprimer" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info">
                Affichage de <?= min(($page-1)*$perPage+1, $totalClients) ?> à <?= min($page*$perPage, $totalClients) ?> sur <?= $totalClients ?> clients
            </div>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="btn btn-pagination">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
                
                <div class="page-numbers">
                    <?php 
                    $totalPages = ceil($totalClients / $perPage);
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1) echo '<span class="page-dots">...</span>';
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?= $i ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" 
                           class="btn btn-pagination <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor;
                    
                    if ($endPage < $totalPages) echo '<span class="page-dots">...</span>';
                    ?>
                </div>
                
                <?php if ($page * $perPage < $totalClients): ?>
                    <a href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?>&search=<?= urlencode($search) ?>" class="btn btn-pagination">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles harmonisés avec ajoutclient.php */
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
    --danger: #ff6b6b;
    --warning: #ffc107;
    --success: #4caf50;
    --info: #2196f3;
    --glass: rgba(15, 15, 35, 0.65);
    --glass-border: rgba(106, 0, 255, 0.3);
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.data-section {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid rgba(106, 0, 255, 0.3);
}

.section-header {
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h3 {
    color: var(--text);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.search-form {
    flex: 1;
    min-width: 250px;
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
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
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

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
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

.print-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.print-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-icon.bg-primary { background: rgba(106, 0, 255, 0.2); color: var(--primary-light); }
.stat-icon.bg-success { background: rgba(76, 175, 80, 0.2); color: var(--success); }
.stat-icon.bg-warning { background: rgba(255, 193, 7, 0.2); color: var(--warning); }

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
    font-size: 1.2rem;
    font-weight: 600;
}

.table-container {
    overflow-x: auto;
    margin-bottom: 2rem;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--glass);
    font-size: 0.9rem;
}

.data-table th {
    padding: 0.75rem 0.5rem;
    text-align: left;
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: white;
    font-weight: 500;
}

.data-table td {
    padding: 0.75rem 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text);
}

.data-table tr {
    height: 40px;
}

.data-table tr:hover td {
    background: rgba(106, 0, 255, 0.1);
}

.text-center {
    text-align: center;
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

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-primary {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
    border: 1px solid var(--primary);
}

.badge-success {
    background: rgba(76, 175, 80, 0.2);
    color: var(--success);
    border: 1px solid var(--success);
}

.actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.btn-sm {
    padding: 0.35rem 0.75rem;
    font-size: 0.85rem;
}

.btn-warning {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
    border: 1px solid var(--warning);
}

.btn-warning:hover {
    background: var(--warning);
    color: var(--dark);
}

.btn-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn-danger:hover {
    background: var(--danger);
    color: white;
}

.pagination {
    display: flex;
    flex-direction: column;
    gap: 1rem;
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
    flex-wrap: wrap;
    justify-content: center;
}

.btn-pagination {
    background: rgba(106, 0, 255, 0.1);
    color: var(--primary-light);
}

.btn-pagination:hover {
    background: var(--primary);
    color: white;
}

.btn-pagination.active {
    background: var(--primary);
    color: white;
}

.page-numbers {
    display: flex;
    gap: 0.25rem;
}

.page-dots {
    padding: 0.5rem;
    color: var(--text-secondary);
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
    
    .action-buttons {
        width: 100%;
        justify-content: space-between;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .data-table th, 
    .data-table td {
        padding: 0.75rem;
    }
}

@media (max-width: 576px) {
    .pagination-controls {
        flex-direction: column;
    }
    
    .page-numbers {
        order: -1;
        margin-bottom: 0.5rem;
    }
    
    .actions {
        flex-direction: column;
        gap: 0.25rem;
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

// Animation de la recherche
document.addEventListener('DOMContentLoaded', function() {
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
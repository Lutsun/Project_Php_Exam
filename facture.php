<?php
$pageTitle = "Gestion des Factures - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Récupération des paramètres
$search = $_GET['search'] ?? '';
$client_id = $_GET['client_id'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';

// Requête pour récupérer les commandes livrées (factures)
try {
    $query = "SELECT c.id_com as id_facture, 
                     c.date as date_facture,
                     cl.id_client,
                     cl.nom_cli, 
                     cl.prenom_cli,
                     p.id_prod,
                     p.nom_prod,
                     p.prix_unitaire,
                     c.quant_com as quantite,
                     (p.prix_unitaire * c.quant_com) as montant_total,
                     l.date_liv as date_livraison
              FROM commandes c
              JOIN clients cl ON c.client = cl.id_client
              JOIN produits p ON c.produit = p.id_prod
              JOIN livraisons l ON c.id_com = l.commande_com
              WHERE (cl.nom_cli LIKE :search OR cl.prenom_cli LIKE :search OR c.id_com LIKE :search)
              AND l.quantite_liv > 0";
    
    $params = [':search' => '%'.$search.'%'];
    
    if (!empty($client_id)) {
        $query .= " AND c.client = :client_id";
        $params[':client_id'] = $client_id;
    }
    
    if (!empty($date_filter)) {
        $query .= " AND DATE(c.date) = :date_filter";
        $params[':date_filter'] = $date_filter;
    }
    
    $query .= " ORDER BY c.date DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer la liste des clients pour le filtre
    $clientsStmt = $pdo->query("SELECT id_client, CONCAT(nom_cli, ' ', prenom_cli) as nom_complet FROM clients ORDER BY nom_cli");
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques
    $statsQuery = "SELECT 
                    COUNT(DISTINCT c.id_com) as total,
                    SUM(p.prix_unitaire * c.quant_com) as chiffre_affaire,
                    COUNT(DISTINCT c.client) as clients_distincts
                  FROM commandes c
                  JOIN produits p ON c.produit = p.id_prod
                  JOIN livraisons l ON c.id_com = l.commande_com
                  WHERE l.quantite_liv > 0";
    $statsStmt = $pdo->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-file-invoice-dollar"></i> Gestion des factures</h3>
            <div class="section-actions">
                <form method="GET" class="search-form">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Rechercher une facture..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                </form>
                <div class="action-buttons">
                    <button onclick="window.print()" class="btn print-btn">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtres avancés -->
        <div class="filters-container glass-card">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" class="form-input">
                        <option value="">Tous les clients</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id_client'] ?>" <?= $client_id == $client['id_client'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['nom_complet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_filter">Date</label>
                    <input type="date" name="date_filter" id="date_filter" 
                           value="<?= htmlspecialchars($date_filter) ?>" class="form-input">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="facture.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Cartes de statistiques -->
        <div class="stats-cards">
            <div class="stat-card glass-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Factures totales</span>
                    <span class="stat-value"><?= $stats['total'] ?></span>
                </div>
            </div>
            
            <div class="stat-card glass-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Chiffre d'affaires</span>
                    <span class="stat-value"><?= number_format($stats['chiffre_affaire'], 0, ',', ' ') ?> Fcfa</span>
                </div>
            </div>
            
            <div class="stat-card glass-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-title">Clients distincts</span>
                    <span class="stat-value"><?= $stats['clients_distincts'] ?></span>
                </div>
            </div>
        </div>

        <!-- Tableau des factures -->
        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Montant Total</th>
                        <th>Livraison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($factures)): ?>
                        <tr>
                            <td colspan="9" class="text-center no-data">
                                <i class="fas fa-file-excel"></i>
                                <p>Aucune facture trouvée</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($factures as $facture): ?>
                            <tr>
                                <td>#<?= str_pad($facture['id_facture'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('d/m/Y', strtotime($facture['date_facture'])) ?></td>
                                <td><?= htmlspecialchars($facture['nom_cli'] . ' ' . $facture['prenom_cli']) ?></td>
                                <td><?= htmlspecialchars($facture['nom_prod']) ?></td>
                                <td><?= $facture['quantite'] ?></td>
                                <td><?= number_format($facture['prix_unitaire'], 0, ',', ' ') ?> Fcfa</td>
                                <td><?= number_format($facture['montant_total'], 0, ',', ' ') ?> Fcfa</td>
                                <td><?= date('d/m/Y', strtotime($facture['date_livraison'])) ?></td>
                                <td class="actions">
                                    <button onclick="afficherFacture(<?= $facture['id_facture'] ?>)" 
                                            class="btn btn-sm btn-primary" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="#" onclick="imprimerFacture(<?= $facture['id_facture'] ?>)" 
                                       class="btn btn-sm btn-info" title="Imprimer">
                                        <i class="fas fa-print"></i>
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

<!-- Modal pour afficher une facture -->
<div id="factureModal" class="modal">
    <div class="modal-content glass-card">
        <div class="modal-header">
            <h4>Facture #<span id="modalFactureId"></span></h4>
            <span class="close-modal" onclick="fermerModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalFactureContent">
            <!-- Contenu chargé dynamiquement -->
        </div>
        <div class="modal-footer">
            <button onclick="imprimerModal()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <button onclick="fermerModal()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>

<style>
/* Styles harmonisés avec vos autres pages */
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

/* Structure principale */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.data-section {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid var(--glass-border);
}

/* Cartes en verre (glassmorphism) */
.glass-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px); /* Pour Safari */
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
    margin-bottom: 1.5rem;
}

/* En-tête de section */
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

/* Barre de recherche */
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

/* Boutons */
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

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
}

.print-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.print-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.btn-sm {
    padding: 0.35rem 0.75rem;
    font-size: 0.85rem;
}

.btn-info {
    background: rgba(33, 150, 243, 0.1);
    color: var(--info);
    border: 1px solid var(--info);
}

.btn-info:hover {
    background: var(--info);
    color: white;
}

/* Filtres */
.filters-container {
    margin-bottom: 1.5rem;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Cartes de statistiques */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
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
.stat-icon.bg-info { background: rgba(33, 150, 243, 0.2); color: var(--info); }

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

/* Tableau */
.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
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

.actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    overflow: auto;
}

.modal-content {
    margin: 5% auto;
    width: 80%;
    max-width: 800px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--glass-border);
}

.modal-header h4 {
    margin: 0;
    color: var(--text);
    font-size: 1.5rem;
}

.close-modal {
    color: var(--text-secondary);
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close-modal:hover {
    color: var(--danger);
}

.modal-body {
    margin-bottom: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--glass-border);
}

/* Responsive */
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
    
    .filter-form {
        grid-template-columns: 1fr;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 2% auto;
    }
}

@media (max-width: 576px) {
    .actions {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
// Fonction pour afficher une facture dans le modal
function afficherFacture(id) {
    fetch('get_facture.php?id=' + id + '&mode=modal')
        .then(response => response.text())
        .then(data => {
            document.getElementById('modalFactureId').textContent = id.toString().padStart(4, '0');
            document.getElementById('modalFactureContent').innerHTML = data;
            document.getElementById('factureModal').style.display = 'block';
        })
        .catch(error => console.error('Erreur:', error));
}

// Imprimer une facture directement
function imprimerFacture(id) {
    window.open('get_facture.php?id=' + id + '&mode=print', '_blank');
}

// Imprimer le contenu du modal
function imprimerModal() {
    const content = document.getElementById('modalFactureContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Facture #${document.getElementById('modalFactureId').textContent}</title>
            <link rel="stylesheet" href="css/print.css">
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Fermer le modal
function fermerModal() {
    document.getElementById('factureModal').style.display = 'none';
}

// Fermer si clique en dehors
window.onclick = function(event) {
    if (event.target === document.getElementById('factureModal')) {
        fermerModal();
    }
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
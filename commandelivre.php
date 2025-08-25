<?php
$pageTitle = "Commandes Livrées - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';

// Récupération des commandes livrées
$query = "SELECT 
            c.id_com AS numero_commande,
            c.date AS date_commande,
            cl.id_client AS code_client,
            cl.nom_cli AS nom,
            cl.prenom_cli AS prenom,
            l.date_liv AS date_livraison,
            c.montant AS montant_commande,
            p.nom_prod AS produit,
            l.quantite_liv AS quantite_livree,
            c.quant_com AS quantite_commandee,
            l.adresse_liv AS adresse_livraison
          FROM commandes c
          JOIN clients cl ON c.client = cl.id_client
          JOIN produits p ON c.produit = p.id_prod
          JOIN livraisons l ON c.id_com = l.commande_com
          ORDER BY l.date_liv DESC";

$commandes = $pdo->query($query)->fetchAll();

// Calcul du montant total des commandes livrées
$totalQuery = "SELECT SUM(c.montant) AS total_livre FROM commandes c JOIN livraisons l ON c.id_com = l.commande_com";
$total = $pdo->query($totalQuery)->fetchColumn();

?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h1><i class="fas fa-clipboard-check"></i> Commandes Livrées</h1>
            <p>Les commandes indiquées par <strong>on</strong> ont été annulées ou suspendues par le client au cours de la livraison.</p>
        </div>

        <div class="data-table-container">
            <div class="table-controls">
                <div class="show-entries">
                    <span>Afficher</span>
                    <select onchange="updatePerPage(this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>lignes</span>
                </div>
                <div class="filter-controls">
                    <input type="text" placeholder="Filtre..." class="filter-input">
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="numero_commande">N° Commande</th>
                        <th class="sortable" data-sort="date_commande">Date</th>
                        <th class="sortable" data-sort="code_client">Code Client</th>
                        <th class="sortable" data-sort="nom">Nom</th>
                        <th class="sortable" data-sort="prenom">Prénom</th>
                        <th class="sortable" data-sort="date_livraison">Date livraison</th>
                        <th class="sortable" data-sort="montant_commande">Montant de la commande</th>
                        <th>Voir Details</th>
                
    
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                    <tr class="hover-effect table-row-animation">
                        <td>
                            <span class="badge commande-id">N° <?= $commande['numero_commande'] ?></span>
                        </td>
                        <td>
                            <div class="date-wrapper">
                                <span class="date"><?= date('d M Y', strtotime($commande['date_commande'])) ?></span>
                            </div>
                        </td>
                        <td><?= $commande['code_client'] ?></td>
                        <td><?= strtoupper($commande['nom']) ?></td>
                        <td><?= ucfirst(strtolower($commande['prenom'])) ?></td>
                        <td>
                            <div class="date-wrapper">
                                <span class="date"><?= date('d M Y', strtotime($commande['date_livraison'])) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="price"><?= number_format($commande['montant_commande'], 0, ',', ' ') ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="detailscom.php?id=<?= $commande['numero_commande'] ?>" class="action-btn view tooltip" data-tooltip="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Nouvelle section pour le total des commandes livrées -->
            <div class="total-container">
                <div class="total-card">
                    <div class="total-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="total-content">
                        <h3>Total des Commandes Livrées</h3>
                        <p class="total-amount"><?= number_format($total, 0, ',', ' ') ?> FCFA</p>
                    </div>
                </div>
            </div>

            <div class="pagination">
                <div class="pagination-info">
                    Affichage de 1 à <?= count($commandes) ?> sur <?= count($commandes) ?> entrées
                </div>
                <div class="pagination-controls">
                    <a href="#" class="page-link prev">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                    <div class="page-numbers">
                        <a href="#" class="page-number active">1</a>
                    </div>
                    <a href="#" class="page-link next">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles de base de StockNova */
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

.section-header h1 {
    color: var(--primary-light);
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.95rem;
}

/* Table Controls */
.table-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0 0.5rem;
}

.show-entries {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.show-entries select {
    padding: 0.5rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-input {
    padding: 0.5rem 1rem;
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

/* Data Table Container */
.data-table-container {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 2rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    padding: 1rem;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 1rem;
}

.data-table th {
    padding: 1rem;
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

.data-table td {
    padding: 1rem;
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

.price {
    font-weight: 600;
    color: var(--secondary);
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

.action-btn.delivery {
    background: rgba(0, 200, 83, 0.1);
    color: #00c853;
}

.action-btn.delivery:hover {
    background: #00c853;
    color: white;
    transform: translateY(-2px);
}

.action-btn.info {
    background: rgba(33, 150, 243, 0.1);
    color: #2196f3;
}

.action-btn.info:hover {
    background: #2196f3;
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

/* Animations */
.table-row-animation {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.5s forwards;
}

/* Styles pour la nouvelle section de total */
.total-container {
    margin-top: 2rem;
    padding: 0 1rem;
}

.total-card {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    border-radius: 12px;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: 0 8px 25px rgba(106, 0, 255, 0.3);
    border: 1px solid var(--primary-light);
    transition: all 0.3s ease;
}

.total-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(106, 0, 255, 0.4);
}

.total-icon {
    font-size: 2.5rem;
    color: white;
    background: rgba(255, 255, 255, 0.15);
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.total-content h3 {
    color: white;
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
    font-weight: 500;
}

.total-amount {
    color: var(--secondary);
    font-size: 2rem;
    margin: 0;
    font-weight: 700;
    letter-spacing: 1px;
}

/* Responsive */
@media (max-width: 768px) {
    .total-card {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    
    .total-icon {
        margin-bottom: 1rem;
    }
    
    .total-amount {
        font-size: 1.8rem;
    }
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 1200px) {
    .data-table th, .data-table td {
        padding: 0.75rem;
    }
}

@media (max-width: 768px) {
    .section-header {
        padding: 1rem;
    }
    
    .table-controls {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .data-table {
        display: block;
        overflow-x: auto;
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
    
    // Filtre de recherche
    const filterInput = document.querySelector('.filter-input');
    filterInput.addEventListener('input', function() {
        const filterValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');
        
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(filterValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
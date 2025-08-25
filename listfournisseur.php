<?php
$pageTitle = "Liste des Fournisseurs - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';
// Récupération des fournisseurs
$query = "SELECT * FROM fournisseurs ORDER BY nom_fournisseur ASC";
$fournisseurs = $pdo->query($query)->fetchAll();
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h1><i class="fas fa-truck"></i> Liste des Fournisseurs</h1>
            <p>Gérez ici tous vos fournisseurs et leurs informations.</p>
        </div>

        <div class="table-controls">
            <a href="ajoutfournisseur.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un fournisseur
            </a>
            <div class="filter-controls">
                <input type="text" id="searchInput" placeholder="Rechercher..." class="filter-input">
            </div>
        </div>

        <div class="data-table-container">
            <table id="fournisseursTable" class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="nom">Nom</th>
                        <th class="sortable" data-sort="prenom">Prénom</th>
                        <th class="sortable" data-sort="raison">Raison Sociale</th>
                        <th class="sortable" data-sort="ville">Ville</th>
                        <th class="sortable" data-sort="telephone">Téléphone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fournisseurs as $fournisseur): ?>
                    <tr class="hover-effect table-row-animation">
                        <td><?= htmlspecialchars($fournisseur['nom_fournisseur']) ?></td>
                        <td><?= htmlspecialchars($fournisseur['prenom_fournisseur']) ?></td>
                        <td><?= htmlspecialchars($fournisseur['raison_sociale']) ?></td>
                        <td><?= htmlspecialchars($fournisseur['ville']) ?></td>
                        <td><?= htmlspecialchars($fournisseur['telephone']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="updatefournisseur.php?id=<?= $fournisseur['id_fournisseur'] ?>" 
                                   class="action-btn edit tooltip" data-tooltip="Modifier">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="deletefournisseur.php?id=<?= $fournisseur['id_fournisseur'] ?>" 
                                   class="action-btn delete tooltip" data-tooltip="Supprimer" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Styles globaux StockNova */
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
    margin-bottom: 1.5rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(106, 0, 255, 0.3);
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-input {
    padding: 0.75rem 1rem;
    background: var(--dark);
    border: 1px solid var(--primary);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s ease;
    min-width: 250px;
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

.data-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    background: var(--glass);
    color: var(--text);
    transition: all 0.3s ease;
}

.data-table tr.hover-effect:hover td {
    background: rgba(106, 0, 255, 0.1);
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
}

.action-btn i {
    font-size: 0.95rem;
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

.action-btn.delete {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
}

.action-btn.delete:hover {
    background: var(--danger);
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
@media (max-width: 768px) {
    .table-controls {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .filter-input {
        width: 100%;
    }
    
    .data-table {
        display: block;
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des lignes
    const rows = document.querySelectorAll('.table-row-animation');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Filtre de recherche
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#fournisseursTable tbody tr');
        
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Tri des colonnes
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            // Implémentez ici la logique de tri si nécessaire
            console.log('Trier par', sortField);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
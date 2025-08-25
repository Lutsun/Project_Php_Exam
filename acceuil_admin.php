<?php
$pageTitle = "Tableau de bord StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Récupération des données depuis la base
try {
    // Nombre de clients
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM clients");
    $clients = $stmt->fetch()['total'] ?? 0;
    
    // Revenus totaux (en utilisant prix_total de la table produits)
    $stmt = $pdo->query("SELECT SUM(prix_total) AS total FROM produits");
    $revenus = $stmt->fetch()['total'] ?? 0;
    
    // Quantité totale de produits
    $stmt = $pdo->query("SELECT SUM(quant_prod) AS total FROM produits");
    $produits = $stmt->fetch()['total'] ?? 0;
    
    // Nombre de fournisseurs dans la table fournisseurs
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM fournisseurs");
        $fournisseurs = $stmt->fetch()['total'] ?? 0;

    
} catch (PDOException $e) {
    // En cas d'erreur, initialiser à 0
    $clients = $revenus = $produits = $fournisseurs = 0;
    error_log("Erreur SQL: " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <!-- Section des KPI Premium -->
    <div class="kpi-grid">
        <!-- Clients -->
        <div class="kpi-card kpi-client">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" data-target="<?= $clients ?>">0</div>
                <div class="kpi-label">Clients</div>
            </div>
            <div class="kpi-trend up">+0%</div>
        </div>

        <!-- Revenus -->
        <div class="kpi-card kpi-revenu">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" data-target="<?= $revenus ?>">0</div>
                <div class="kpi-label">Revenus (FCFA)</div>
            </div>
            <div class="kpi-trend up">+0%</div>
        </div>

        <!-- Produits -->
        <div class="kpi-card kpi-produit">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.69V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.7c.57-.35 1-.97 1-1.69V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-4H9V8h9v2z"/>
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" data-target="<?= $produits ?>">0</div>
                <div class="kpi-label">Produits stockés</div>
            </div>
            <div class="kpi-trend down">-0%</div>
        </div>

        <!-- Fournisseurs -->
        <div class="kpi-card kpi-fournisseur">
            <div class="kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M19 7h-3V5.5c0-.83-.67-1.5-1.5-1.5h-5C8.67 4 8 4.67 8 5.5V7H5c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-5-2h3v2h-3V5zM5 9h14v2h-6v5h-2v-5H5V9z"/>
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" data-target="<?= $fournisseurs ?>">0</div>
                <div class="kpi-label">Fournisseurs</div>
            </div>
            <div class="kpi-trend up">+0%</div>
        </div>
    </div>

    <!-- Section Produits -->
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-box-open"></i> Inventaire des Produits</h3>
            <div class="section-actions">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher un produit..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="per-page-select">
                    <select class="per-page">
                        <option value="10">10 éléments</option>
                        <option value="25">25 éléments</option>
                        <option value="50">50 éléments</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>
        </div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom du Produit</th>
                        <th>Référence</th>
                        <th>Quantité</th>
                        <th>Prix (FCFA)</th>
                        <th>Statut</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT nom_prod, code_prod, GREATEST(quant_prod, 0) AS quant_prod, prix_unitaire FROM produits LIMIT 5");
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($products)) {
                            echo "<tr><td colspan='6' class='text-center'>Aucun produit enregistré</td></tr>";
                        } else {
                            foreach ($products as $product) {
                                $status = 'En stock';
                                $statusClass = 'en-stock';
                                
                                if ($product['quant_prod'] <= 10) {
                                    $status = 'Stock bas';
                                    $statusClass = 'stock-bas';
                                }
                                if ($product['quant_prod'] <= 5) {
                                    $status = 'Stock critique';
                                    $statusClass = 'stock-critique';
                                }
                                 if ($product['quant_prod'] <= 0) {
                                    $status = 'Epuisé';
                                    $statusClass = 'stock-critique';
                                }
                                
                                echo "
                                <tr>
                                    <td>{$product['nom_prod']}</td>
                                    <td>{$product['code_prod']}</td>
                                    <td>{$product['quant_prod']}</td>
                                    <td>".number_format($product['prix_unitaire'], 0, ',', ' ')."</td>
                                    <td><span class='status-badge {$statusClass}'>{$status}</span></td>
                               
                                </tr>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6' class='text-center'>Erreur de chargement des produits</td></tr>";
                        error_log("Erreur produits: " . $e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Clients -->
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Nos Clients</h3>
            <div class="section-actions">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher un client..." class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="per-page-select">
                    <select class="per-page">
                        <option value="10">10 éléments</option>
                        <option value="25">25 éléments</option>
                        <option value="50">50 éléments</option>
                    </select>
                    <i class="fas fa-chevron-down select-icon"></i>
                </div>
            </div>
        </div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Statut</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT nom_cli, prenom_cli, telephone, email, statut FROM clients LIMIT 3");
                        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($clients)) {
                            echo "<tr><td colspan='6' class='text-center'>Aucun client enregistré</td></tr>";
                        } else {
                            foreach ($clients as $client) {
                                $statusClass = strtolower($client['statut'] ?? 'normal');
                                echo "
                                <tr>
                                    <td>{$client['nom_cli']}</td>
                                    <td>{$client['prenom_cli']}</td>
                                    <td>{$client['telephone']}</td>
                                    <td>{$client['email']}</td>
                                    <td><span class='status-badge {$statusClass}'>{$client['statut']}</span></td>
                                  
                                </tr>";
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6' class='text-center'>Erreur de chargement des clients</td></tr>";
                        error_log("Erreur clients: " . $e->getMessage());
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Thème StockNova - Style inchangé */
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

body {
    background: var(--dark);
    color: var(--text);
    font-family: 'Roboto', sans-serif;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.kpi-card {
    background: rgba(26, 26, 46, 0.6);
    border-radius: 16px;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(106, 0, 255, 0.2);
    display: flex;
    flex-direction: column;
}

.kpi-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    border-color: var(--primary-light);
}

.kpi-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    transition: all 0.3s;
}

.kpi-icon svg {
    width: 32px;
    height: 32px;
    fill: white;
}

.kpi-client .kpi-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.kpi-revenu .kpi-icon {
    background: linear-gradient(135deg, #2af598 0%, #009efd 100%);
    box-shadow: 0 4px 15px rgba(42, 245, 152, 0.4);
}

.kpi-produit .kpi-icon {
    background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
    box-shadow: 0 4px 15px rgba(255, 154, 158, 0.4);
}

.kpi-fournisseur .kpi-icon {
    background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
    box-shadow: 0 4px 15px rgba(161, 140, 209, 0.4);
}

.kpi-content {
    margin-bottom: 1rem;
}

.kpi-value {
    font-size: 2.8rem;
    font-weight: 700;
    color: white;
    margin: 0.5rem 0;
    font-family: 'Orbitron', sans-serif;
    letter-spacing: 1px;
    line-height: 1;
}

.kpi-label {
    color: var(--text-secondary);
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
}

.kpi-trend {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.kpi-trend.up {
    background: rgba(0, 200, 83, 0.15);
    color: var(--success);
}

.kpi-trend.down {
    background: rgba(255, 107, 107, 0.15);
    color: var(--danger);
}

.data-section {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(106, 0, 255, 0.3);
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
    color: var(--text);
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    margin: 0;
}

.section-header h3 i {
    margin-right: 0.8rem;
    color: var(--secondary);
}

.section-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.search-box {
    position: relative;
    min-width: 250px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(106, 0, 255, 0.5);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.per-page-select {
    position: relative;
    min-width: 150px;
}

.per-page {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(106, 0, 255, 0.5);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    appearance: none;
}

.select-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    pointer-events: none;
}

.data-table-container {
    overflow-x: auto;
    margin-top: 1.5rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(15, 15, 35, 0.7);
}

.data-table th {
    padding: 1rem;
    text-align: left;
    background: rgba(106, 0, 255, 0.5);
    color: var(--text);
    font-weight: 500;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text);
}

.data-table tr:hover td {
    background: rgba(106, 0, 255, 0.1);
}

.status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.en-stock {
    background: rgba(0, 255, 198, 0.1);
    color: var(--secondary);
    border: 1px solid rgba(0, 255, 198, 0.3);
}

.status-badge.stock-bas {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-badge.stock-critique {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.status-badge.normal {
    background: rgba(0, 255, 198, 0.1);
    color: var(--secondary);
    border: 1px solid rgba(0, 255, 198, 0.3);
}

.status-badge.vip {
    background: rgba(255, 193, 7, 0.1);
    color: var(--warning);
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: rgba(106, 0, 255, 0.3);
    color: var(--text);
}

.action-btn.view:hover {
    color: var(--secondary);
}

.action-btn.edit:hover {
    color: var(--warning);
}

.text-center {
    text-align: center;
}

@media (max-width: 1200px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .section-actions {
        width: 100%;
    }
    
    .search-box {
        width: 100%;
    }
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
</style>

<script>
// Animation des KPI
document.addEventListener('DOMContentLoaded', function() {
    // Compteurs animés
    const kpiValues = document.querySelectorAll('.kpi-value');
    
    kpiValues.forEach(valueElement => {
        const target = parseInt(valueElement.getAttribute('data-target'));
        let current = 0;
        const increment = target / 30;
        const duration = 1500;
        
        const startTime = Date.now();
        const endTime = startTime + duration;
        
        const animateCounter = () => {
            const now = Date.now();
            const progress = Math.min(1, (now - startTime) / duration);
            
            current = Math.floor(progress * target);
            valueElement.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animateCounter);
            }
        };
        
        requestAnimationFrame(animateCounter);
    });
    
    // Animation des lignes du tableau
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        row.style.transition = `all 0.3s ease-out ${index * 0.05}s`;
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 100);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
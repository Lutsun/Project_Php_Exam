<?php
$pageTitle = "Mes Factures - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];
$commandeId = $_GET['commande_id'] ?? null;

if (!$commandeId) {
    header("Location: mes_commandes.php");
    exit();
}

try {
    // Récupérer l'id_client associé à l'utilisateur
    $stmtClient = $pdo->prepare("SELECT id_client FROM users WHERE id_user = ?");
    $stmtClient->execute([$userId]);
    $user = $stmtClient->fetch();
    $clientId = $user['id_client'] ?? null;

    // Récupération des infos de base de la commande
    $commandeQuery = "SELECT c.*, cl.nom_cli, cl.prenom_cli, cl.adresse_cli, cl.ville, cl.pays, cl.email, cl.telephone
                      FROM commandes c
                      JOIN clients cl ON c.client = cl.id_client
                      WHERE c.id_com = :commande_id AND cl.id_client = :client_id";

    $commandeStmt = $pdo->prepare($commandeQuery);
    $commandeStmt->bindValue(':commande_id', $commandeId, PDO::PARAM_INT);
    $commandeStmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
    $commandeStmt->execute();

    $commande = $commandeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        header("Location: mes_commandes.php");
        exit();
    }

    // Récupération du produit de la commande
    $produitQuery = "SELECT 
                    p.nom_prod AS nom_produit,
                    COALESCE(p.prix_unitaire, 0) AS prix_unitaire,
                    c.quant_com AS quantite,
                    (COALESCE(p.prix_unitaire, 0) * c.quant_com) AS total_ligne
                FROM commandes c
                JOIN produits p ON c.produit = p.id_prod
                WHERE c.id_com = :commande_id";

    $produitsStmt = $pdo->prepare($produitQuery);
    $produitsStmt->bindValue(':commande_id', $commandeId, PDO::PARAM_INT);
    $produitsStmt->execute();

    $produits = $produitsStmt->fetchAll(PDO::FETCH_ASSOC);

    $total = array_sum(array_column($produits, 'total_ligne'));

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>


<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-file-invoice"></i> Facture #<?= $commandeId ?></h3>
            <div class="section-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <a href="mes_commandes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="invoice-container">
            <div class="invoice-header">
                <div class="invoice-logo">
                    <h2><span class="text-white">Stock</span><span class="text-primary">Nova</span></h2>
                    <p class="text-muted">Gestion de Stock</p>
                </div>
                <div class="invoice-info">
                    <h3 class="text-white">Facture #<?= $commandeId ?></h3>
                    <p class="text-muted">Date: <?= date('d/m/Y', strtotime($commande['date'])) ?></p>
                    <p class="text-muted">Heure: <?= date('H:i', strtotime($commande['heure'])) ?></p>
                </div>
            </div>
            
            <div class="invoice-parties">
                <div class="invoice-from">
                    <h4 class="text-primary">De:</h4>
                    <p><strong class="text-white">StockNova</strong></p>
                    <p class="text-muted">123 Avenue de la République</p>
                    <p class="text-muted">Dakar, Sénégal</p>
                    <p class="text-muted">Email: contact@stocknova.com</p>
                    <p class="text-muted">Téléphone: +221 33 123 45 67</p>
                </div>
                <div class="invoice-to">
                    <h4 class="text-primary">À:</h4>
                    <p><strong class="text-white"><?= htmlspecialchars($commande['prenom_cli'] . ' ' . $commande['nom_cli']) ?></strong></p>
                    <p class="text-muted"><?= htmlspecialchars($commande['adresse_cli']) ?></p>
                    <p class="text-muted"><?= htmlspecialchars($commande['ville']) . ', ' . htmlspecialchars($commande['pays']) ?></p>
                    <p class="text-muted">Email: <?= htmlspecialchars($commande['email']) ?></p>
                    <p class="text-muted">Téléphone: <?= htmlspecialchars($commande['telephone']) ?></p>
                </div>
            </div>
            
            <div class="invoice-details">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th class="bg-primary">Produit</th>
                            <th class="bg-primary text-right">Prix Unitaire</th>
                            <th class="bg-primary text-center">Quantité</th>
                            <th class="bg-primary text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produits)): ?>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td class="text-white"><?= htmlspecialchars($produit['nom_produit']) ?></td>
                                    <td class="text-right text-muted"><?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                                    <td class="text-center text-muted"><?= $produit['quantite'] ?></td>
                                    <td class="text-right text-white"><?= number_format($produit['total_ligne'], 0, ',', ' ') ?> FCFA</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Aucun produit trouvé pour cette commande</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right text-white"><strong>Total:</strong></td>
                            <td class="text-right text-primary"><strong><?= number_format($total, 0, ',', ' ') ?> FCFA</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="invoice-footer">
                <div class="invoice-notes">
                    <h4 class="text-primary">Notes:</h4>
                    <p class="text-muted">Merci pour votre achat. Pour toute question concernant cette facture, veuillez contacter notre service client.</p>
                </div>
                <div class="invoice-terms">
                    <h4 class="text-primary">Conditions:</h4>
                    <p class="text-muted">Paiement dû dans les 15 jours suivant la réception de la facture.</p>
                </div>
            </div>
        </div>
    </div>
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
    --border-color: rgba(106, 0, 255, 0.3);
    --border-radius: 8px;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

/* Classes utilitaires */
.text-primary { color: var(--primary); }
.text-secondary { color: var(--secondary); }
.text-white { color: var(--text); }
.text-muted { color: var(--text-secondary); }
.bg-primary { background: var(--primary); }
.bg-dark { background: var(--dark); }

.dashboard-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.data-section {
    background: var(--dark);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--box-shadow);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h3 {
    color: var(--text);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.section-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
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
    background: rgba(106, 0, 255, 0.1);
    color: var(--primary-light);
    border: 1px solid var(--primary);
}

.btn-secondary:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.invoice-container {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-top: 1rem;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.invoice-logo h2 {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 0;
}

.invoice-logo p {
    margin: 0;
    font-size: 0.9rem;
}

.invoice-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
}

.invoice-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.invoice-parties {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.invoice-from, .invoice-to {
    flex: 1;
    padding: 1.5rem;
    background: var(--darker);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.invoice-from h4, .invoice-to h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-color);
}

.invoice-from p, .invoice-to p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2rem;
}

.invoice-table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: normal;
}

.invoice-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.invoice-table .text-right {
    text-align: right;
}

.invoice-table .text-center {
    text-align: center;
}

.invoice-table tfoot td {
    border-top: 2px solid var(--border-color);
    font-weight: bold;
}

.invoice-footer {
    display: flex;
    gap: 1.5rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.invoice-notes, .invoice-terms {
    flex: 1;
    padding: 1.5rem;
    background: var(--darker);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.invoice-notes h4, .invoice-terms h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.invoice-notes p, .invoice-terms p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

/* Styles d'impression */
@media print {
    body {
        background: white !important;
        color: black !important;
        font-size: 12pt;
    }
    
    .dashboard-container {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    
    .section-header {
        display: none !important;
    }
    
    .invoice-container {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        background: white !important;
    }
    
    .text-primary {
        color: #6a00ff !important;
    }
    
    .text-muted {
        color: #555 !important;
    }
    
    .bg-primary {
        background: #6a00ff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .invoice-from, .invoice-to,
    .invoice-notes, .invoice-terms {
        background: white !important;
        border: 1px solid #ddd !important;
    }
    
    @page {
        size: A4;
        margin: 10mm;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .invoice-parties, .invoice-footer {
        flex-direction: column;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .section-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Impression automatique si paramètre print=true
    if (window.location.search.includes('print=true')) {
        window.print();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
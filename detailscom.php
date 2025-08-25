<?php
$pageTitle = "Détails de Commande | StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

$commande_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($commande_id <= 0) {
    header("Location: commande_en_cours.php");
    exit();
}

try {
    $query = "SELECT 
                c.id_com AS commande_id,
                DATE_FORMAT(c.date, '%d/%m/%Y') AS date_commande,
                DATE_FORMAT(c.heure, '%H:%i') AS heure_commande,
                p.id_prod AS produit_id,
                p.nom_prod,
                p.code_prod,
                p.photo,
                c.quant_com AS quantite,
                p.prix_unitaire,
                (c.quant_com * p.prix_unitaire) AS montant_total,
                cl.nom_cli,
                cl.prenom_cli,
                cl.adresse_cli,
                cl.ville
              FROM commandes c
              JOIN produits p ON c.produit = p.id_prod
              JOIN clients cl ON c.client = cl.id_client
              WHERE c.id_com = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$commande_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($details)) {
        header("Location: commande_en_cours.php");
        exit();
    }

    $total_general = array_sum(array_column($details, 'montant_total'));

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-file-invoice"></i> Détails de la commande #<?= htmlspecialchars($details[0]['commande_id']) ?></h3>
            <div class="commande-meta">
                <span class="meta-date"><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($details[0]['date_commande']) ?></span>
                <span class="meta-time"><i class="far fa-clock"></i> <?= htmlspecialchars($details[0]['heure_commande']) ?></span>
            </div>
        </div>
    </div>

    <div class="data-section">
        <div class="client-info">
            <h4><i class="fas fa-user"></i> Client</h4>
            <p><strong>Nom :</strong> <?= htmlspecialchars($details[0]['prenom_cli'] . ' ' . $details[0]['nom_cli']) ?></p>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($details[0]['adresse_cli']) ?>, <?= htmlspecialchars($details[0]['ville']) ?></p>
        </div>
    </div>

    <?php foreach ($details as $produit) : ?>
    <div class="data-section">
        <div class="produit-card">
            <div class="produit-photo-container">
                <img src="<?= htmlspecialchars(!empty($produit['photo']) ? $produit['photo'] : 'assets/images/default-product.png') ?>" 
                     alt="<?= htmlspecialchars($produit['nom_prod']) ?>" 
                     class="produit-photo">
            </div>
            <div class="produit-infos">
                <h4><?= htmlspecialchars($produit['nom_prod']) ?></h4>
                <div class="produit-meta">
                    <span class="produit-ref">Réf: <?= htmlspecialchars($produit['code_prod']) ?></span>
                    <span class="produit-quantite">Quantité: <?= htmlspecialchars($produit['quantite']) ?></span>
                </div>
                <div class="produit-prix">
                    <span class="prix-unitaire"><?= number_format($produit['prix_unitaire'], 0, ',', ' ') ?> FCFA l'unité</span>
                    <span class="prix-total">Total: <?= number_format($produit['montant_total'], 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="data-section total-section">
        <div class="total-line">
            <span>Total à payer</span>
            <span class="total-amount"><?= number_format($total_general, 0, ',', ' ') ?> FCFA</span>
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
    --text: #ffffff;
    --text-secondary: #b8b8d1;
    --card-bg: #1a1a2e;
    --border-color: rgba(106, 0, 255, 0.3);
    --border-radius: 8px;
}

.dashboard-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.data-section {
    background: var(--dark);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.section-header {
    margin-bottom: 1.5rem;
}

.section-header h3 {
    color: var(--text);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
}

.commande-meta {
    display: flex;
    gap: 1.5rem;
    color: var(--text-secondary);
    font-size: 0.95rem;
}

.commande-meta i {
    margin-right: 0.3rem;
    color: var(--primary-light);
}

.client-info {
    padding: 1.5rem;
    background: var(--card-bg);
    border-radius: var(--border-radius);
}

.client-info h4 {
    color: var(--text);
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.client-info p {
    margin: 0.5rem 0;
    color: var(--text);
}

.client-info strong {
    color: var(--primary-light);
}

.produit-card {
    display: flex;
    gap: 2rem;
    padding: 1.5rem;
    background: var(--card-bg);
    border-radius: var(--border-radius);
}

.produit-photo-container {
    flex: 0 0 150px;
}

.produit-photo {
    width: 100%;
    max-height: 150px;
    object-fit: contain;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.produit-infos {
    flex: 1;
}

.produit-infos h4 {
    color: var(--text);
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
}

.produit-meta {
    display: flex;
    gap: 1.5rem;
    margin: 1rem 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.produit-prix {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
}

.prix-unitaire {
    color: var(--text-secondary);
}

.prix-total {
    font-weight: bold;
    color: var(--secondary);
}

.total-section {
    background: var(--primary-dark);
    text-align: right;
    padding: 1.5rem;
}

.total-line {
    display: inline-flex;
    justify-content: space-between;
    min-width: 300px;
    font-size: 1.2rem;
}

.total-amount {
    font-weight: bold;
    color: var(--secondary);
    margin-left: 1rem;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 0 0.75rem;
    }
    
    .produit-card {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .produit-photo-container {
        flex: 0 0 auto;
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
    }
    
    .produit-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .total-line {
        min-width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
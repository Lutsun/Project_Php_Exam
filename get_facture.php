<?php
require 'includes/connexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de commande invalide');
}

$id_commande = $_GET['id'];
$mode = $_GET['mode'] ?? 'modal'; // 'modal' ou 'print'

try {
    // Récupérer les informations de la commande livrée
    $query = "SELECT c.id_com as id_facture, 
                     c.date as date_facture,
                     cl.id_client,
                     cl.nom_cli, 
                     cl.prenom_cli,
                     cl.adresse_cli,
                     cl.ville,
                     cl.pays,
                     cl.telephone,
                     p.id_prod,
                     p.nom_prod,
                     p.description,
                     p.prix_unitaire,
                     c.quant_com as quantite,
                     (p.prix_unitaire * c.quant_com) as montant_total,
                     l.date_liv as date_livraison,
                     l.adresse_liv
              FROM commandes c
              JOIN clients cl ON c.client = cl.id_client
              JOIN produits p ON c.produit = p.id_prod
              JOIN livraisons l ON c.id_com = l.commande_com
              WHERE c.id_com = ? AND l.quantite_liv > 0";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_commande]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$facture) {
        die('Commande livrée non trouvée');
    }

    // Formatage des dates
    $date_facture = date('d/m/Y', strtotime($facture['date_facture']));
    $date_livraison = date('d/m/Y', strtotime($facture['date_livraison']));

    // Générer le HTML de la facture
    $html = generateFactureHTML($facture, $date_facture, $date_livraison);

    if ($mode === 'print') {
        // Retourner une page complète pour impression
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Facture #'.str_pad($facture['id_facture'], 4, '0', STR_PAD_LEFT).'</title>
            <link rel="stylesheet" href="css/print.css">
           
        </head>
        <body>'.$html.'<script>window.print();</script></body>
        </html>';
        exit;
    } else {
        // Retourner juste le contenu pour le modal
        echo $html;
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Fonction pour générer le HTML de la facture
function generateFactureHTML($facture, $date_facture, $date_livraison) {
    ob_start(); ?>
    <div class="facture-container">
        <div class="facture-header">
            <div class="logo-text">
                <span class="logo-stock">Stock </span><span class="logo-nova">Nova</span>
            </div>
            <div>
                <p>Facture N°: <?= str_pad($facture['id_facture'], 4, '0', STR_PAD_LEFT) ?></p>
                <p>Date: <?= $date_facture ?></p>
            </div>
        </div>
        
        <div class="facture-title">
            <h2>FACTURE</h2>
            <p>(Commande livrée)</p>
        </div>
        
        <div class="facture-info">
            <div class="facture-client">
                <h3>Client</h3>
                <p><strong><?= htmlspecialchars($facture['nom_cli'].' '.$facture['prenom_cli']) ?></strong></p>
                <p><?= htmlspecialchars($facture['adresse_cli']) ?></p>
                <p><?= htmlspecialchars($facture['ville'].', '.$facture['pays']) ?></p>
                <p>Tél: <?= htmlspecialchars($facture['telephone']) ?></p>
            </div>
            
            <div class="facture-details">
                <h3>Détails de livraison</h3>
                <p><strong>Date livraison:</strong> <?= $date_livraison ?></p>
                <p><strong>Adresse livraison:</strong> <?= htmlspecialchars($facture['adresse_liv'] ?? $facture['adresse_cli']) ?></p>
            </div>
        </div>
        
        <table class="facture-table">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Prix Unitaire (Fcfa)</th>
                    <th>Quantité</th>
                    <th>Total (Fcfa)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($facture['nom_prod']) ?><br>
                        <small><?= htmlspecialchars($facture['description']) ?></small>
                    </td>
                    <td><?= number_format($facture['prix_unitaire'], 0, ',', ' ') ?></td>
                    <td><?= $facture['quantite'] ?></td>
                    <td><?= number_format($facture['montant_total'], 0, ',', ' ') ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="facture-total">
            <p>Net à payer: <strong><?= number_format($facture['montant_total'], 0, ',', ' ') ?> Fcfa</strong></p>
        </div>
        
        <div class="facture-footer">
            <p>Merci pour votre confiance !</p>
            <p>Stock Nova - Gestion de stock et facturation</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
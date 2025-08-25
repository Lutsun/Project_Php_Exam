<?php
require 'includes/connexion.php';

// Récupère les commandes sans livraison
$query = "SELECT c.*, p.id_prod, cl.adresse_cli 
          FROM commandes c
          JOIN produits p ON c.produit = p.id_prod
          JOIN clients cl ON c.client = cl.id_client
          LEFT JOIN livraisons l ON c.id_com = l.commande_com
          WHERE l.id_liv IS NULL";

$commandes = $pdo->query($query)->fetchAll();

foreach ($commandes as $commande) {
    // Crée la livraison
    $stmt = $pdo->prepare("INSERT INTO livraisons 
                          (commande_com, produit_id, client_com, produit_com, 
                           quantite_liv, montant_com, date_liv, heure_liv, adresse_liv)
                          VALUES 
                          (?, ?, ?, ?, 0, ?, CURDATE(), CURTIME(), ?)");
    
    $stmt->execute([
        $commande['id_com'],
        $commande['id_prod'],
        $commande['client'],
        $commande['produit'],
        $commande['montant'],
        $commande['adresse_cli']
    ]);
}

echo count($commandes) . " livraisons créées";
?>
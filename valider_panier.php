<?php
require 'includes/connexion.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Vérifier si le panier existe et n'est pas vide
if (empty($_SESSION['panier'])) {
    header("Location: listprod.php");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Pour chaque produit dans le panier
    foreach ($_SESSION['panier'] as $productId => $quantity) {
        // Récupérer les infos complètes du produit
        $productQuery = "SELECT id_prod, nom_prod, prix_unitaire FROM produits WHERE id_prod = ?";
        $productStmt = $pdo->prepare($productQuery);
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();
        
        if ($product) {
            // Calculer le montant total
            $montant = $product['prix_unitaire'] * $quantity;
            $currentDate = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Insérer la commande dans la table commandes
            $insertQuery = "INSERT INTO commandes 
                          (client, produit, quant_com, date, heure, montant)
                          VALUES (?, ?, ?, ?, ?, ?)";
            
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                $userId,                // client (id_client)
                $product['nom_prod'],   // ✅ nom du produit
                $quantity,              // quant_com
                $currentDate,           // date
                $currentTime,           // heure
                $montant               // montant
            ]);
            
            // Récupérer l'ID de la commande nouvellement créée
            $commandeId = $pdo->lastInsertId();
            
            // Insérer également dans la table livraisons
            $insertLivraisonQuery = "INSERT INTO livraisons 
                                  (commande_com, client_com, produit_com, quantite_liv, montant_com, date_liv, heure_liv)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $insertLivraisonStmt = $pdo->prepare($insertLivraisonQuery);
            $insertLivraisonStmt->execute([
                $commandeId,           // commande_com
                $userId,               // client_com
                $product['nom_prod'], // au lieu de $product['id_prod']
                $quantity,             // quantite_liv
                $montant,               // montant_com
                $currentDate,          // date_liv
                $currentTime            // heure_liv
            ]);
            
            // Mettre à jour le stock
            $updateQuery = "UPDATE produits SET quant_prod = quant_prod - ? WHERE id_prod = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$quantity, $productId]);
        }
    }
    
    // Valider la transaction
    $pdo->commit();
    
    // Vider le panier
    unset($_SESSION['panier']);
    
    // Rediriger vers la page des commandes avec message de succès
    header("Location: mes_commandes.php?success=1");
    exit();

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Rediriger avec message d'erreur
    $_SESSION['error'] = "Erreur lors de la validation du panier : " . $e->getMessage();
    header("Location: listprod.php");
    exit();
}
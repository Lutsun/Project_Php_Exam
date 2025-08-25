<?php
require 'includes/connexion.php';
require 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prodId = $_GET['id'] ?? null;
if (!$prodId) {
    echo "<p>Produit introuvable</p>";
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_prod = ?");
    $stmt->execute([$prodId]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        echo "<p>Produit introuvable</p>";
        exit();
    }
} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Produit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a00ff;
            --secondary: #00ffc6;
            --background: #0d0d1a;
            --card: #1a1a2e;
            --text: #ffffff;
            --text-muted: #aaaaaa;
            --accent: #9d4dff;
            --border: #2c2c40;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background);
            color: var(--text);
        }

        .product-details-container {
            max-width: 700px;
            margin: 3rem auto;
            background-color: var(--card);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }

        .product-image-container {
            text-align: center;
        }

        .product-image-container img {
            width: 250px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .product-name {
            font-size: 1.8rem;
            margin-top: 1.2rem;
            text-align: center;
            color: var(--accent);
        }

        .product-stock {
            text-align: center;
            font-size: 1.05rem;
            margin: 0.5rem 0;
            color: var(--secondary);
        }

        .product-description {
            font-size: 1rem;
            line-height: 1.6;
            margin-top: 1rem;
            padding: 1rem;
            border-left: 4px solid var(--accent);
            background-color: rgba(255, 255, 255, 0.03);
        }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--accent);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="product-details-container">
        <div class="product-image-container">
            <img src="<?= htmlspecialchars($produit['photo']) ?>" alt="<?= htmlspecialchars($produit['nom_prod']) ?>">
        </div>
        <h2 class="product-name"><?= htmlspecialchars($produit['nom_prod']) ?></h2>
        <p class="product-stock">Quantité disponible : <?= htmlspecialchars($produit['quant_prod']) ?></p>
        <div class="product-description">
            <strong>Description :</strong><br>
            <?= nl2br(htmlspecialchars($produit['description'])) ?>
        </div>
        <a href="listprod.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour à la boutique</a>
    </div>
</body>
</html>

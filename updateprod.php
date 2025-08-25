<?php
// Activation du débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclusions nécessaires
require 'includes/connexion.php';
require 'includes/header.php';

// Vérification de l'ID produit
if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'ID produit invalide'
    ];
    header("Location: listprod.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Récupération du produit
try {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_prod = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => 'Produit introuvable'
        ];
        header("Location: listprod.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Erreur technique'
    ];
    header("Location: listprod.php");
    exit();
}

// Traitement du formulaire
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et validation des données
    $data = [
        'nom_prod' => trim($_POST['nom_prod'] ?? ''),
        'code_prod' => trim($_POST['code_prod'] ?? ''),
        'quant_prod' => (int)($_POST['quant_prod'] ?? 0),
        'prix_unitaire' => (float)($_POST['prix_unitaire'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'fournisseur' => trim($_POST['fournisseur'] ?? '')
    ];

    // Validation
    if (empty($data['nom_prod'])) $errors[] = "Le nom du produit est requis";
    if (empty($data['code_prod'])) $errors[] = "Le code produit est requis";
    if ($data['quant_prod'] < 0) $errors[] = "La quantité ne peut pas être négative";
    if ($data['prix_unitaire'] <= 0) $errors[] = "Le prix unitaire doit être positif";

    // Gestion de la photo
    $photo = $product['photo'];
    if (!empty($photo) && !file_exists($photo)) {
        $photo = 'assets/images/default-product.png';
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/products/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['photo']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('prod_') . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                if ($photo !== 'assets/images/default-product.png' && file_exists($photo)) {
                    unlink($photo);
                }
                $photo = $uploadPath;
            } else {
                $errors[] = "Erreur lors de l'upload de la photo";
            }
        } else {
            $errors[] = "Type de fichier non autorisé. Seuls les images sont acceptées.";
        }
    }

    // Mise à jour si aucune erreur
    if (empty($errors)) {
        try {
            $data['prix_total'] = $data['quant_prod'] * $data['prix_unitaire'];
            
            $stmt = $pdo->prepare("UPDATE produits SET 
                code_prod = ?,
                nom_prod = ?,    
                quant_prod = ?, 
                prix_unitaire = ?, 
                prix_total = ?, 
                fournisseur = ?,
                description = ?,
                photo = ?
                WHERE id_prod = ?");

            if ($stmt->execute([
                $data['code_prod'],
                $data['nom_prod'],
                $data['quant_prod'],
                $data['prix_unitaire'],
                $data['prix_total'],
                $data['fournisseur'],
                $data['description'],
                $photo,
                $product_id
            ])) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Produit mis à jour avec succès!'
                ];
                header("Location: listprod.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erreur mise à jour: " . $e->getMessage());
            $errors[] = "Erreur lors de la mise à jour. Veuillez réessayer.";
        }
    }
}

// Récupération des fournisseurs
try {
    $fournisseurs = $pdo->query("SELECT nom_fournisseur FROM fournisseurs ORDER BY nom_fournisseur")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $fournisseurs = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier produit - StockNova</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .update-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(26, 26, 46, 0.8);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(106, 0, 255, 0.3);
        }

        h1 {
            color: var(--primary-light);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--primary);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(0, 255, 198, 0.3);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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

        .error-message {
            color: var(--danger);
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .success-message {
            color: var(--success);
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--success);
        }

        .datalist-options {
            background: var(--darker);
            color: var(--text);
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: rgba(255, 107, 107, 0.1);
            color: var(--danger);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        ul {
            padding-left: 20px;
        }

        
        input[type="file"] {
            padding: 0.5rem;
            cursor: pointer;
        }

        input[type="file"]::-webkit-file-upload-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 1rem;
            transition: all 0.3s;
        }

        input[type="file"]::-webkit-file-upload-button:hover {
            background: var(--primary-light);
        }

        .form-hint {
            color: var(--text-secondary);
            font-size: 0.8rem;
            display: block;
            margin-top: 0.25rem;
        }

        img {
            border: 1px solid rgba(106, 0, 255, 0.3);
        }

        .current-photo-container {
    width: 150px;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(106, 0, 255, 0.1);
    border-radius: 8px;
    border: 1px dashed var(--primary-light);
    overflow: hidden;
}

.current-photo {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.no-photo-icon {
    color: var(--primary-light);
    font-size: 3rem;
    opacity: 0.5;
}

/* Style pour le champ fichier (identique à ajoutprod.php) */
input[type="file"].form-input {
    padding: 0.5rem;
    cursor: pointer;
}

input[type="file"].form-input::-webkit-file-upload-button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 1rem;
    transition: all 0.3s;
}

input[type="file"].form-input::-webkit-file-upload-button:hover {
    background: var(--primary-light);
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="update-container">
            <h1><i class="fas fa-edit"></i> Modifier le produit</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                <div class="form-group">
                    <label for="code_prod">Code produit</label>
                    <input type="text" id="code_prod" name="code_prod" 
                           value="<?= htmlspecialchars($product['code_prod']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="nom_prod">Nom du produit</label>
                    <input type="text" id="nom_prod" name="nom_prod" 
                           value="<?= htmlspecialchars($product['nom_prod']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="quant_prod">Quantité en stock</label>
                    <input type="number" id="quant_prod" name="quant_prod" 
                           value="<?= htmlspecialchars($product['quant_prod']) ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label for="prix_unitaire">Prix unitaire (FCFA)</label>
                    <input type="number" step="0.01" id="prix_unitaire" name="prix_unitaire" 
                           value="<?= htmlspecialchars($product['prix_unitaire']) ?>" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="fournisseur">Fournisseur</label>
                    <input list="fournisseurs-list" id="fournisseur" name="fournisseur" 
                           value="<?= htmlspecialchars($product['fournisseur']) ?>" required>
                    <datalist id="fournisseurs-list">
                        <?php foreach ($fournisseurs as $fournisseur): ?>
                            <option value="<?= htmlspecialchars($fournisseur) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

            <div class="form-group">
                    <label>Photo actuelle</label>
                <div class="current-photo-container" style="margin-bottom: 1rem;">
                    <?php if (!empty($product['photo']) && file_exists($product['photo']) && $product['photo'] !== 'assets/images/default-product.png'): ?>
                        <img src="<?= htmlspecialchars($product['photo']) ?>" alt="Photo actuelle" class="current-photo">
                    <?php else: ?>
                        <div class="no-photo-icon">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <label for="photo">Changer la photo</label>
                <input type="file" id="photo" name="photo" class="form-input" accept="image/*">
                <small class="form-hint">(facultatif - formats: jpg, png, etc.)</small>
            </div>



                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    <a href="listprod.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validation côté client
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>

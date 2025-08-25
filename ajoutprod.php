<?php
$pageTitle = "Ajouter un Produit - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code_prod = $_POST['code_prod'];
        $nom_prod = $_POST['nom_prod'];
        $quant_prod = $_POST['quant_prod'];
        $prix_unitaire = $_POST['prix_unitaire'];
        $fournisseur = $_POST['fournisseur'];
        $description = $_POST['description'] ?? null;
        $prix_total = $quant_prod * $prix_unitaire;

        // Gestion de la photo (valeur par défaut)
        $photo = 'assets/images/default-product.png';

        if (empty($code_prod) || empty($nom_prod) || empty($quant_prod) || empty($prix_unitaire) || empty($fournisseur)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }

        // Traitement de l'upload de fichier
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/uploads/products/';
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

             // Vérifier le type de fichier
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['photo']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé. Seuls les images sont acceptées.");
            }
            
            // Générer un nom de fichier unique
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('prod_') . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $photo = $uploadPath;
            } else {
                throw new Exception("Erreur lors de l'upload de la photo");
            }
        }



        $sql = "INSERT INTO produits (code_prod, nom_prod, quant_prod, prix_unitaire, prix_total, fournisseur, description,photo) 
                VALUES (:code_prod, :nom_prod, :quant_prod, :prix_unitaire, :prix_total, :fournisseur, :description,:photo)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':code_prod', $code_prod);
        $stmt->bindParam(':nom_prod', $nom_prod);
        $stmt->bindParam(':quant_prod', $quant_prod);
        $stmt->bindParam(':prix_unitaire', $prix_unitaire);
        $stmt->bindParam(':prix_total', $prix_total);
        $stmt->bindParam(':fournisseur', $fournisseur);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':photo', $photo);

        if ($stmt->execute()) {
            $successMessage = "Produit ajouté avec succès!";
        } else {
            throw new Exception("Erreur lors de l'ajout du produit");
        }

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-box-open"></i> Ajouter un produit</h3>
        </div>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="add-form" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="code_prod">Code Produit *</label>
                    <input type="text" id="code_prod" name="code_prod" class="form-input" required>
                    <span class="form-hint">Code unique du produit</span>
                </div>
                
                <div class="form-group">
                    <label for="nom_prod">Nom du Produit *</label>
                    <input type="text" id="nom_prod" name="nom_prod" class="form-input" required>
                    <span class="form-hint">Désignation complète</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quant_prod">Quantité *</label>
                    <input type="number" id="quant_prod" name="quant_prod" class="form-input" step="0.1" min="0" required>
                    <span class="form-hint">ex: 11.5, 25</span>
                </div>
                
                <div class="form-group">
                    <label for="prix_unitaire">Prix Unitaire *</label>
                    <div class="input-with-symbol">
                        <input type="number" id="prix_unitaire" name="prix_unitaire" class="form-input" step="0.01" min="0" required>
                        <span>FCFA</span>
                    </div>
                    <span class="form-hint">ex: 5000</span>
                </div>
            </div>

            <div class="form-group">
                <label for="fournisseur">Fournisseur *</label>
                <input type="text" id="fournisseur" name="fournisseur" class="form-input" required>
                <span class="form-hint">Nom du fournisseur</span>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-input" rows="3"></textarea>
                <span class="form-hint">(facultatif)</span>
            </div>

            <div class="form-group">
                <label for="photo">Photo du produit</label>
                <input type="file" id="photo" name="photo" class="form-input" accept="image/*">
                <span class="form-hint">(facultatif - formats: jpg, png, etc.)</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <button type="reset" class="reset-btn">
                    <i class="fas fa-eraser"></i> Effacer
                </button>
            </div>
        </form>
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
}

.dashboard-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.data-section {
    background: rgba(26, 26, 46, 0.8);
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid rgba(106, 0, 255, 0.3);
}

.section-header {
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

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert i {
    font-size: 1.2rem;
}

.alert-success {
    background: rgba(76, 175, 80, 0.1);
    color: var(--success);
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.alert-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.add-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1.5rem;
}

.form-group {
    flex: 1;
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--primary);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 2px rgba(106, 0, 255, 0.3);
}

.form-hint {
    font-size: 0.8rem;
    color: var(--text-secondary);
    font-style: italic;
    display: block;
    margin-top: 0.25rem;
}

.input-with-symbol {
    position: relative;
}

.input-with-symbol span {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-light);
    font-weight: 500;
}

textarea.form-input {
    min-height: 100px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.submit-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.submit-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
}

.reset-btn {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.reset-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-group {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .submit-btn, .reset-btn {
        width: 100%;
    }
}
/* Ajoutez ceci dans la section <style> */
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

<script>
// Calcul automatique du prix total
document.addEventListener('DOMContentLoaded', function() {
    const quantiteInput = document.getElementById('quant_prod');
    const prixInput = document.getElementById('prix_unitaire');
    
    function calculateTotal() {
        const quantite = parseFloat(quantiteInput.value) || 0;
        const prix = parseFloat(prixInput.value) || 0;
        return quantite * prix;
    }
    
    quantiteInput.addEventListener('input', calculateTotal);
    prixInput.addEventListener('input', calculateTotal);
    
    // Animation des champs
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('label').style.color = 'var(--primary-light)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.querySelector('label').style.color = 'var(--text-secondary)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
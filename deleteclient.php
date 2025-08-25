<?php
$pageTitle = "Supprimer Client - StockNova";
require 'includes/connexion.php';
require 'includes/header.php';
require 'includes/loading.php';

// Vérification du statut admin
if ($_SESSION['role'] !== 'Admin') {
    header('Location: listclient.php');
    exit();
}

// Récupération de l'ID client
$clientId = $_GET['id'] ?? null;
if (!$clientId || !is_numeric($clientId)) {
    header('Location: listclient.php');
    exit();
}

// Empêche l'auto-suppression (vérification modifiée)
if (isset($_SESSION['user_id']) && $clientId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "Vous ne pouvez pas supprimer votre propre compte";
    header('Location: listclient.php');
    exit();
}

try {
    // Récupération des données client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id_client = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        header('Location: listclient.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Traitement de la suppression
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Vérification supplémentaire pour éviter l'auto-suppression
        if (isset($_SESSION['user_id']) && $clientId == $_SESSION['user_id']) {
            throw new Exception("Auto-suppression interdite");
        }

        // 1. D'abord supprimer l'utilisateur associé
        $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE id_client = ?");
        $deleteUserStmt->execute([$clientId]);

        // 2. Ensuite supprimer le client
        $deleteClientStmt = $pdo->prepare("DELETE FROM clients WHERE id_client = ?");
        $deleteClientStmt->execute([$clientId]);

        $pdo->commit();
        $_SESSION['success_message'] = "Client et utilisateur associé supprimés avec succès";
        header('Location: listclient.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>

<div class="dashboard-container">
    <div class="data-section">
        <div class="section-header">
            <h3><i class="fas fa-user-times"></i> Supprimer Client</h3>
            <div class="section-actions">
                <a href="listclient.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="glass-card form-container">
            <form method="POST" class="delete-form">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div class="confirmation-message">
                    <div class="warning-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Confirmation de suppression</h4>
                    <p>Cette action est irréversible. Toutes les données associées à ce client seront définitivement supprimées.</p>
                    
                    <div class="client-details">
                        <div class="detail-item">
                            <span class="detail-label">Nom complet:</span>
                            <span class="detail-value"><?= htmlspecialchars($client['nom_cli']) ?> <?= htmlspecialchars($client['prenom_cli']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?= htmlspecialchars($client['email'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Téléphone:</span>
                            <span class="detail-value"><?= htmlspecialchars($client['telephone']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Statut:</span>
                            <span class="detail-value badge <?= $client['statut'] === 'admin' ? 'badge-success' : 'badge-primary' ?>">
                                <?= ucfirst($client['statut']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
                    </button>
                    <a href="listclient.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Styles harmonisés avec listclient.php */
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
    --danger: #ff6b6b;
    --warning: #ffc107;
    --success: #4caf50;
    --info: #2196f3;
    --glass: rgba(15, 15, 35, 0.65);
    --glass-border: rgba(106, 0, 255, 0.3);
}

.glass-card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid var(--glass-border);
}

.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.delete-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.confirmation-message {
    text-align: center;
    padding: 1.5rem 0;
}

.confirmation-message h4 {
    color: var(--danger);
    margin: 1rem 0 0.5rem;
    font-size: 1.25rem;
}

.confirmation-message p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

.warning-icon {
    font-size: 3rem;
    color: var(--warning);
    margin-bottom: 1rem;
}

.client-details {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 6px;
    padding: 1.5rem;
    margin: 2rem 0;
    text-align: left;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.detail-value {
    color: var(--text);
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-primary {
    background: rgba(106, 0, 255, 0.2);
    color: var(--primary-light);
    border: 1px solid var(--primary);
}

.badge-success {
    background: rgba(76, 175, 80, 0.2);
    color: var(--success);
    border: 1px solid var(--success);
}

.form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
}

.btn-danger {
    background: rgba(255, 107, 107, 0.1);
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn-danger:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-danger {
    background: rgba(255, 107, 107, 0.15);
    border: 1px solid var(--danger);
    color: var(--danger);
}

@media (max-width: 768px) {
    .form-footer {
        flex-direction: column;
    }
    
    .detail-item {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
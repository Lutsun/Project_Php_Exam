<?php
require 'includes/session_init.php';

define('ROLE_ADMIN', 'Admin');
define('ROLE_USER', 'User');

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    // Si non connecté et pas déjà sur la page de login
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: index.php");
        exit();
    }
} else {
    // Si déjà connecté mais sur la page de login
    if (basename($_SERVER['PHP_SELF']) === 'index.php') {
        if ($_SESSION['role'] === ROLE_ADMIN) {
            header("Location: acceuil_admin.php");
        } else {
            header("Location: listprod.php");
        }
        exit();
    }
}

$pageTitle = "StockNova";
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdmin = ($_SESSION['role'] === ROLE_ADMIN);

ob_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Sidebar fixe avec contenu scrollable -->
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="logo">
                <span class="logo-stock">Stock</span>
                <span class="logo-nova">Nova</span>
            </div>
            
            <div class="sidebar-menu">
                <nav class="nav-menu">
                    <!-- Tableau de bord - Uniquement pour les admins -->
                    <?php if ($isAdmin): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Tableau de bord</div>
                        <div class="nav-item">
                            <a href="acceuil_admin.php" class="nav-link <?= $currentPage === 'acceuil_admin.php' ? 'active' : '' ?>">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Tableau de Bord</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gestion des Clients - Uniquement pour les admins -->
                    <?php if ($isAdmin): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion des Clients</div>
                        <div class="nav-item">
                            <a href="ajoutclient.php" class="nav-link <?= $currentPage === 'ajoutclient.php' ? 'active' : '' ?>">
                                <i class="fas fa-user-plus"></i>
                                <span>Ajouter un client</span>
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="listclient.php" class="nav-link <?= $currentPage === 'listclient.php' ? 'active' : '' ?>">
                                <i class="fas fa-users"></i>
                                <span>Liste des clients</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Gestion des Commandes -->
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion des Commandes</div>
                        <div class="nav-item">
                            <a href="commande.php" class="nav-link <?= $currentPage === 'commande.php' ? 'active' : '' ?>">
                                <i class="fas fa-cart-plus"></i>
                                <span>Nouvelle commande</span>
                            </a>
                        </div>
                        
                        <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="commandes_en_cours.php" class="nav-link <?= $currentPage === 'commandes_en_cours.php' ? 'active' : '' ?>">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Toutes les commandes</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="nav-item">
                                <a href="mes_commandes.php" class="nav-link <?= $currentPage === 'mes_commandes.php' ? 'active' : '' ?>">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Mes commandes</span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="livraison.php" class="nav-link <?= $currentPage === 'livraison.php' ? 'active' : '' ?>">
                                    <i class="fas fa-truck-moving"></i>
                                    <span>Gestion des livraisons</span>
                                </a>
                            </div>
                        <?php endif; ?>

                         <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="commandelivre.php" class="nav-link <?= $currentPage === 'commandelivre.php' ? 'active' : '' ?>">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Commandes livrées</span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <!-- Gestion du Stock -->
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion du Stock</div>
                        <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="ajoutprod.php" class="nav-link <?= $currentPage === 'ajoutprod.php' ? 'active' : '' ?>">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Ajouter un produit</span>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="nav-item">
                            <a href="listprod.php" class="nav-link <?= $currentPage === 'listprod.php' ? 'active' : '' ?>">
                                <i class="fas fa-boxes"></i>
                                <span>Liste des produits</span>
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="inventaire.php" class="nav-link <?= $currentPage === 'inventaire.php' ? 'active' : '' ?>">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Inventaire</span>
                            </a>
                        </div>
                        <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="fourniture.php" class="nav-link <?= $currentPage === 'fourniture.php' ? 'active' : '' ?>">
                                    <i class="fas fa-truck-loading"></i>
                                    <span>Nouvelle fourniture</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="findfourniture.php" class="nav-link <?= $currentPage === 'findfourniture.php' ? 'active' : '' ?>">
                                    <i class="fas fa-search-dollar"></i>
                                    <span>Recherche fournitures</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Gestion des Fournisseurs - Uniquement pour les admins -->
                    <?php if ($isAdmin): ?>
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion des Fournisseurs</div>
                        <div class="nav-item">
                            <a href="ajoutfournisseur.php" class="nav-link <?= $currentPage === 'ajoutfournisseur.php' ? 'active' : '' ?>">
                                <i class="fas fa-user-plus"></i>
                                <span>Ajouter un fournisseur</span>
                            </a>
                        </div>
                        <div class="nav-item">
                            <a href="listfournisseur.php" class="nav-link <?= $currentPage === 'listfournisseur.php' ? 'active' : '' ?>">
                                <i class="fas fa-truck"></i>
                                <span>Liste des fournisseurs</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
    

                    <!-- Gestion des Factures -->
                    <div class="nav-section">
                        <div class="nav-section-title">Gestion des Factures</div>
                        <?php if ($isAdmin): ?>
                            <div class="nav-item">
                                <a href="facture.php" class="nav-link <?= $currentPage === 'facture.php' ? 'active' : '' ?>">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>Facturation</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="nav-item">
                                <a href="mes_factures.php" class="nav-link <?= $currentPage === 'mes_factures.php' ? 'active' : '' ?>">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Mes factures</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
            
            <!-- Profil utilisateur fixe en bas -->
            <div class="user-profile">
                <div class="profile-info">
                    <?php if ($isAdmin): ?>
                        <i class="fas fa-crown fa-2x" style="color: gold;"></i>
                    <?php else: ?>
                        <i class="fas fa-user fa-2x" style="color: #555;"></i>
                    <?php endif; ?>
                    <div>
                        <div class="username"><?= htmlspecialchars($_SESSION['username'] ?? 'Utilisateur') ?></div>
                        <small class="role">
                            <?= $isAdmin ? 'Administrateur' : 'Utilisateur' ?>
                        </small>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php include 'includes/loading.php'; ?>
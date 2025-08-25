-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 26 août 2025 à 00:58
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_stock`
--

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `nom_cli` varchar(250) DEFAULT NULL,
  `prenom_cli` varchar(50) NOT NULL,
  `adresse_cli` varchar(255) NOT NULL,
  `ville` varchar(50) NOT NULL,
  `pays` varchar(50) NOT NULL,
  `email` varchar(250) DEFAULT NULL,
  `telephone` varchar(20) NOT NULL,
  `statut` enum('admin','utilisateur') DEFAULT 'utilisateur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_client`, `nom_cli`, `prenom_cli`, `adresse_cli`, `ville`, `pays`, `email`, `telephone`, `statut`) VALUES
(1, 'Lo', 'Cheikh', 'Pikine', 'Dakar', 'Senegal', 'cheikhlo123@gmail.com', '776534568', 'utilisateur'),
(2, 'Diouf', 'Mouhamed', 'HLM', 'Dakar', 'Senegal', 'mohamedndiaye02@gmail.com', '782345678', 'utilisateur'),
(5, 'Diakhate', 'Ousmane', 'Medina', 'Dakar', 'Senegal', 'ousmane12@gmail.com', '789465776', 'utilisateur'),
(6, 'Diouf', 'Mouhamed', 'Dafifort', 'Dakar', 'Sénégal', 'diouffymoh34@gmail.com', '708554356', 'utilisateur');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id_com` int(11) NOT NULL,
  `client` int(11) DEFAULT NULL,
  `produit` int(11) DEFAULT NULL,
  `quant_com` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `heure` time DEFAULT NULL,
  `montant` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id_com`, `client`, `produit`, `quant_com`, `date`, `heure`, `montant`) VALUES
(33, 1, 5, 2, '2025-05-25', '18:59:43', 2400.00),
(35, 1, 8, 10, '2025-05-25', '17:32:11', 13000.00),
(36, 1, 3, 10, '2025-05-25', '17:43:00', 8000.00),
(37, 1, 3, 2, '2025-05-25', '17:45:24', 3050.00),
(38, 1, 3, 10, '2025-05-25', '17:54:53', 13000.00),
(39, 1, 3, 4, '2025-05-25', '17:58:52', 4800.00),
(40, 2, 3, 1, '2025-05-25', '20:12:48', 800.00),
(41, 2, 3, 1, '2025-05-25', '20:12:48', 1525.00),
(42, 2, 3, 1, '2025-05-25', '20:12:48', 1300.00),
(43, 1, 3, 2, '2025-05-25', '18:14:06', 1400.00),
(44, 1, 3, 8, '2025-05-25', '18:20:01', 10400.00),
(45, 1, 3, 5, '2025-05-25', '18:26:39', 4000.00),
(46, 1, 3, 3, '2025-05-25', '18:36:22', 3900.00),
(47, 1, 3, 11, '2025-05-25', '18:47:34', 8800.00),
(49, 1, 3, 3, '2025-05-26', '21:45:57', 2400.00),
(50, 1, 3, 4, '2025-05-26', '22:31:21', 6100.00),
(51, 1, 3, 7, '2025-05-26', '23:25:14', 5600.00),
(53, 6, 4, 3, '2025-05-27', '21:41:09', 2400.00),
(54, 6, 6, 1, '2025-05-27', '21:41:25', 800.00);

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id_fournisseur` int(11) NOT NULL,
  `nom_fournisseur` varchar(100) DEFAULT NULL,
  `prenom_fournisseur` varchar(100) DEFAULT NULL,
  `raison_sociale` varchar(255) DEFAULT NULL,
  `adresse_fournisseur` varchar(255) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `prenom_fournisseur`, `raison_sociale`, `adresse_fournisseur`, `ville`, `pays`, `email`, `telephone`) VALUES
(1, 'Diaw', 'EL hadji', 'marie', 'centre ville', 'Dakar', 'Senegal', 'elhadji123@gmail.com', '786579876'),
(4, 'Sylla', 'Ibrahima', 'celibataire', 'Guediawaye', 'Dakar', 'Senegal', 'ibo34@gmail.com', '786543214'),
(5, 'Diop', 'Omar', 'celibataire', 'Guediawaye', 'Dakar', 'Senegal', 'Oums14@gmail.com', '789874553'),
(6, 'Sene', 'Mouhamed', 'celibataire', 'Residence Hacienda', 'Dakar', 'Senegal', 'Mohasene023@gmail.com', '788654321'),
(7, 'Diagne', 'Veronique', 'marie', 'Rufisque', 'Dakar', 'Senegal', 'verodiagne55@gmail.com', '776564389');

-- --------------------------------------------------------

--
-- Structure de la table `historique_fournitures`
--

CREATE TABLE `historique_fournitures` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `quantite_ajoutee` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `date_operation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_fournitures`
--

INSERT INTO `historique_fournitures` (`id`, `produit_id`, `designation`, `quantite_ajoutee`, `prix_unitaire`, `date_operation`) VALUES
(3, 5, 'Bissap', 20, 1200.00, '2025-05-18 21:58:43'),
(16, 6, 'Fanta', 5, 800.00, '2025-05-20 00:31:26'),
(17, 6, 'Fanta', 100, 800.00, '2025-05-25 18:58:34'),
(18, 7, 'Casamancaise Jus Ditakh', 100, 1525.00, '2025-05-25 18:58:57'),
(19, 3, 'Coca Cola', 30, 800.00, '2025-05-25 18:59:15'),
(20, 4, 'Gazelle Ananas', 80, 700.00, '2025-05-25 18:59:32'),
(21, 3, 'Coca Cola', 30, 800.00, '2025-05-25 18:59:47'),
(22, 5, 'Casamancaise Jus Bissap', 30, 1200.00, '2025-05-27 22:24:06'),
(23, 4, 'Gazelle Ananas', 10, 800.00, '2025-05-27 22:24:20'),
(24, 5, 'Casamancaise Jus Bissap', 10, 1200.00, '2025-05-27 22:24:33'),
(25, 7, 'Casamancaise Jus Ditakh', 4, 1525.00, '2025-05-27 22:24:45');

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

CREATE TABLE `livraisons` (
  `id_liv` int(11) NOT NULL,
  `client_com` int(11) NOT NULL,
  `commande_com` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `produit_com` varchar(100) DEFAULT NULL,
  `montant_com` decimal(10,2) DEFAULT NULL,
  `date_liv` date DEFAULT NULL,
  `heure_liv` time DEFAULT NULL,
  `quantite_liv` int(11) DEFAULT NULL,
  `adresse_liv` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livraisons`
--

INSERT INTO `livraisons` (`id_liv`, `client_com`, `commande_com`, `produit_id`, `produit_com`, `montant_com`, `date_liv`, `heure_liv`, `quantite_liv`, `adresse_liv`) VALUES
(32, 1, 50, 7, 'Casamancaise Jus Ditakh', 6100.00, '2025-05-26', '23:21:10', 4, NULL),
(33, 1, 49, 6, 'Fanta', 2400.00, '2025-05-26', '23:21:15', 3, NULL),
(34, 2, 40, 3, 'Coca Cola', 800.00, '2025-05-26', '23:21:46', 1, NULL),
(35, 1, 51, 4, 'Gazelle Ananas', 5600.00, '2025-05-26', '23:25:46', 7, NULL),
(36, 6, 53, 4, 'Gazelle Ananas', NULL, '2025-05-27', NULL, 0, NULL),
(37, 6, 54, 6, 'Fanta', NULL, '2025-05-27', NULL, 0, NULL),
(38, 6, 54, 6, '6', 800.00, '2025-05-27', '22:25:25', 1, NULL),
(39, 6, 54, 6, '6', 800.00, '2025-05-27', '22:25:28', 1, NULL),
(40, 6, 54, 6, '6', 800.00, '2025-05-27', '23:01:35', 1, NULL),
(41, 6, 54, 6, '6', 800.00, '2025-05-27', '23:01:40', 1, NULL),
(42, 6, 54, 6, '6', 800.00, '2025-05-27', '23:16:48', 1, NULL),
(43, 6, 54, 6, '6', 800.00, '2025-05-27', '23:16:51', 1, NULL),
(44, 6, 54, 6, '6', 800.00, '2025-05-27', '23:16:56', 1, NULL),
(45, 6, 53, 4, '4', 2400.00, '2025-05-27', '23:17:14', 3, NULL),
(46, 6, 54, 6, '6', 800.00, '2025-05-28', '22:24:53', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id_prod` int(11) NOT NULL,
  `code_prod` varchar(255) DEFAULT NULL,
  `nom_prod` varchar(100) DEFAULT NULL,
  `quant_prod` int(11) DEFAULT 0,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `prix_total` decimal(10,2) GENERATED ALWAYS AS (`quant_prod` * `prix_unitaire`) VIRTUAL,
  `fournisseur` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) NOT NULL DEFAULT 'assets/images/default-product.png' COMMENT 'Chemin vers la photo du produit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_prod`, `code_prod`, `nom_prod`, `quant_prod`, `prix_unitaire`, `fournisseur`, `description`, `photo`) VALUES
(3, '041104', 'Coca Cola', 140, 800.00, 'Ibrahima Sylla', 'Boisson Gazeuse', 'assets/uploads/products/prod_682e62da11fcd.webp'),
(4, '789019', 'Gazelle Ananas', 117, 800.00, 'EL Hadji Diaw', 'Boisson Gazeuse', 'assets/uploads/products/prod_682e6379ad133.webp'),
(5, '20252026', 'Casamancaise Jus Bissap', 120, 1200.00, 'Ibrahima Sylla', 'Jus Naturel', 'assets/uploads/products/prod_682e621ab9dba.webp'),
(6, '0456978', 'Fanta', 112, 800.00, 'Mouhammadou Lamine Sene', 'Boisson Gazeuse', 'assets/uploads/products/prod_682e632fb0f41.jpg'),
(7, '22345667', 'Casamancaise Jus Ditakh', 120, 1525.00, 'Omar Diop', 'Jus de Fruit', 'assets/uploads/products/prod_682e53270ea86.webp'),
(8, '247304', 'Bouye', 158, 1300.00, 'Omar Diop', 'Jus Naturel', 'assets/uploads/products/prod_682e64367976e.png');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nom_user` varchar(100) NOT NULL,
  `prenom_user` varchar(100) NOT NULL,
  `adresse_user` varchar(255) DEFAULT NULL,
  `login_user` varchar(50) NOT NULL,
  `password_user` varchar(255) NOT NULL,
  `profil_user` enum('Admin','User') NOT NULL,
  `telephone_user` varchar(20) DEFAULT NULL,
  `photo_user` varchar(255) DEFAULT NULL,
  `id_client` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `nom_user`, `prenom_user`, `adresse_user`, `login_user`, `password_user`, `profil_user`, `telephone_user`, `photo_user`, `id_client`) VALUES
(1, 'Da Sylva', 'Serge', 'Hann Mariste 1', 'Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '777203162', 'https://i.pravatar.cc/300?img=1', NULL),
(2, 'Lo', 'Cheikh', '123 rue Pikine, Dakar', 'user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '761234567', 'https://i.pravatar.cc/300?img=2', 1),
(3, 'Diouf', 'Mouhamed', 'Dafifort', 'mouhamed.diouf', '$2y$10$PcUDgDY/QKHHUI/zV593s.Z9EzSrzm7V5ZejieI.2JEU.sMfSuKu6', 'User', '708554356', NULL, 6);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_com`),
  ADD KEY `fk_commandes_produits` (`produit`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id_fournisseur`);

--
-- Index pour la table `historique_fournitures`
--
ALTER TABLE `historique_fournitures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produit_id` (`produit_id`);

--
-- Index pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id_liv`),
  ADD KEY `client_com` (`client_com`),
  ADD KEY `commande_com` (`commande_com`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_prod`),
  ADD UNIQUE KEY `code_prod` (`code_prod`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `login_user` (`login_user`),
  ADD KEY `id_client` (`id_client`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_com` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id_fournisseur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `historique_fournitures`
--
ALTER TABLE `historique_fournitures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id_liv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_prod` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_produits` FOREIGN KEY (`produit`) REFERENCES `produits` (`id_prod`);

--
-- Contraintes pour la table `historique_fournitures`
--
ALTER TABLE `historique_fournitures`
  ADD CONSTRAINT `fk_produit_id` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id_prod`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD CONSTRAINT `fk_liv_commande` FOREIGN KEY (`commande_com`) REFERENCES `commandes` (`id_com`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `livraisons_ibfk_1` FOREIGN KEY (`client_com`) REFERENCES `clients` (`id_client`),
  ADD CONSTRAINT `livraisons_ibfk_2` FOREIGN KEY (`commande_com`) REFERENCES `commandes` (`id_com`),
  ADD CONSTRAINT `livraisons_ibfk_3` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id_prod`);

--
-- Contraintes pour la table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

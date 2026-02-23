-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 21 fév. 2026 à 14:38
-- Version du serveur : 10.4.24-MariaDB
-- Version de PHP : 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `nextmux_erp`
--

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `email`, `telephone`, `adresse`, `ville`, `created_at`) VALUES
(1, 'TechVision SARL', 'contact@techvision.fr', '01 23 45 67 89', '12 rue de la Paix', 'Paris', '2026-02-21 11:43:50'),
(2, 'Agence Lumière', 'hello@agence-lumiere.fr', '04 56 78 90 12', '8 allée des Roses', 'Lyon', '2026-02-21 11:43:50'),
(3, 'BioGreen Solutions', 'info@biogreen.io', '05 67 89 01 23', '45 avenue du Port', 'Bordeaux', '2026-02-21 11:43:50'),
(4, 'StartupNova', 'team@startupnova.co', '06 11 22 33 44', '3 square Innovation', 'Nantes', '2026-02-21 11:43:50'),
(5, 'Médias Plus', 'direction@mediasplus.com', '02 98 76 54 32', '22 boulevard du Théâtre', 'Rennes', '2026-02-21 11:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `projet_id` int(10) UNSIGNED DEFAULT NULL,
  `libelle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `categorie` enum('materiel','logiciel','prestataire','transport','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'autre',
  `date` date NOT NULL,
  `justificatif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `depenses`
--

INSERT INTO `depenses` (`id`, `projet_id`, `libelle`, `montant`, `categorie`, `date`, `justificatif`, `created_at`) VALUES
(1, 1, 'Licence Figma Pro (annuel)', '19000.00', 'logiciel', '2025-01-10', NULL, '2026-02-21 11:43:51'),
(2, 1, 'Stock photos Shutterstock', '8500.00', 'logiciel', '2025-01-20', NULL, '2026-02-21 11:43:51'),
(3, 2, 'Serveur AWS EC2 (3 mois)', '36000.00', 'logiciel', '2025-02-01', NULL, '2026-02-21 11:43:51'),
(4, 2, 'Prestataire testeur QA freelance', '120000.00', 'prestataire', '2025-03-01', NULL, '2026-02-21 11:43:51'),
(5, 3, 'Outils SEO SEMrush (trimestre)', '33000.00', 'logiciel', '2025-01-01', NULL, '2026-02-21 11:43:51'),
(6, 5, 'Capteurs IoT prototype x10', '85000.00', 'materiel', '2025-03-05', NULL, '2026-02-21 11:43:51'),
(7, 5, 'Formation MQTT équipe', '50000.00', 'prestataire', '2025-03-10', NULL, '2026-02-21 11:43:51'),
(8, 7, 'Microphone Shure SM7B', '39900.00', 'materiel', '2024-12-10', NULL, '2026-02-21 11:43:51'),
(9, 7, 'Interface audio Focusrite', '24900.00', 'materiel', '2024-12-10', NULL, '2026-02-21 11:43:51'),
(10, NULL, 'Abonnement suite Adobe Creative', '60000.00', 'logiciel', '2025-01-01', NULL, '2026-02-21 11:43:51'),
(11, NULL, 'Transport déplacements client Q1', '28000.00', 'transport', '2025-03-31', NULL, '2026-02-21 11:43:51'),
(12, NULL, 'Hébergement OVH annuel', '12000.00', 'logiciel', '2025-01-15', NULL, '2026-02-21 11:43:51');

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id` int(10) UNSIGNED NOT NULL,
  `projet_id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant_ht` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tva` decimal(5,2) NOT NULL DEFAULT 20.00,
  `montant_ttc` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('brouillon','envoyee','partiellement_payee','payee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `date_emission` date NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `factures`
--

INSERT INTO `factures` (`id`, `projet_id`, `numero`, `montant_ht`, `tva`, `montant_ttc`, `statut`, `date_emission`, `date_echeance`, `notes`, `created_at`) VALUES
(1, 1, 'FAC-2025-001', '340000.00', '20.00', '408000.00', 'payee', '2025-01-15', '2025-02-15', 'Acompte 40% — Refonte site TechVision', '2026-02-21 11:43:50'),
(2, 1, 'FAC-2025-002', '510000.00', '20.00', '612000.00', 'envoyee', '2025-03-01', '2025-04-01', 'Solde 60% — Refonte site TechVision', '2026-02-21 11:43:50'),
(3, 2, 'FAC-2025-003', '800000.00', '20.00', '960000.00', 'payee', '2025-02-05', '2025-03-05', 'Acompte 40% — App mobile iOS/Android', '2026-02-21 11:43:50'),
(4, 3, 'FAC-2025-004', '500000.00', '20.00', '600000.00', 'payee', '2025-01-02', '2025-01-31', 'Facturation mensuelle — Campagne digitale Q1', '2026-02-21 11:43:50'),
(5, 4, 'FAC-2024-015', '420000.00', '20.00', '504000.00', 'payee', '2024-11-05', '2024-12-05', 'Acompte 100% — Identité visuelle', '2026-02-21 11:43:50'),
(6, 5, 'FAC-2025-005', '600000.00', '20.00', '720000.00', 'partiellement_payee', '2025-03-10', '2025-04-10', 'Acompte 40% — Dashboard IoT', '2026-02-21 11:43:50'),
(7, 7, 'FAC-2024-014', '320000.00', '20.00', '384000.00', 'payee', '2024-12-05', '2025-01-05', 'Forfait complet — Podcast Studio', '2026-02-21 11:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(10) UNSIGNED NOT NULL,
  `facture_id` int(10) UNSIGNED NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_paiement` date NOT NULL,
  `mode_paiement` enum('virement','cheque','especes','carte','autre') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'virement',
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id`, `facture_id`, `montant`, `date_paiement`, `mode_paiement`, `reference`, `created_at`) VALUES
(1, 1, '408000.00', '2025-01-22', 'virement', 'VIR-TechVision-0122', '2026-02-21 11:43:50'),
(2, 3, '960000.00', '2025-02-12', 'virement', 'VIR-TechVision-0212', '2026-02-21 11:43:50'),
(3, 4, '600000.00', '2025-01-28', 'virement', 'VIR-Lumiere-0128', '2026-02-21 11:43:50'),
(4, 5, '504000.00', '2024-11-10', 'cheque', 'CHQ-2024-1234', '2026-02-21 11:43:50'),
(5, 6, '300000.00', '2025-03-18', 'virement', 'VIR-BioGreen-0318', '2026-02-21 11:43:50'),
(6, 7, '384000.00', '2024-12-28', 'virement', 'VIR-Medias-1228', '2026-02-21 11:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `projets`
--

CREATE TABLE `projets` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('prospect','en_cours','suspendu','termine') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `projets`
--

INSERT INTO `projets` (`id`, `client_id`, `titre`, `description`, `budget`, `statut`, `date_debut`, `date_fin`, `created_at`) VALUES
(1, 1, 'Refonte site corporate', 'Refonte complète du site web avec CMS headless', '850000.00', 'en_cours', '2025-01-10', '2025-04-30', '2026-02-21 11:43:50'),
(2, 1, 'App mobile iOS/Android', 'Application de gestion de tournées commerciales', '2200000.00', 'en_cours', '2025-02-01', '2025-07-31', '2026-02-21 11:43:50'),
(3, 2, 'Campagne digitale Q1 2025', 'SEA + SEO + Social Media sur 3 mois', '500000.00', 'termine', '2025-01-01', '2025-03-31', '2026-02-21 11:43:50'),
(4, 2, 'Identité visuelle rebrand', 'Nouveau logo, charte graphique et déclinaisons print', '420000.00', 'termine', '2024-11-01', '2025-01-15', '2026-02-21 11:43:50'),
(5, 3, 'Dashboard IoT capteurs', 'Interface temps réel pour capteurs environnementaux', '1500000.00', 'en_cours', '2025-03-01', '2025-09-30', '2026-02-21 11:43:50'),
(6, 4, 'MVP SaaS B2B', 'Développement MVP plateforme de gestion RH', '3000000.00', 'prospect', '2025-05-01', '2025-12-31', '2026-02-21 11:43:50'),
(7, 5, 'Podcast Studio Setup', 'Intégration technique studio podcast + site', '320000.00', 'termine', '2024-12-01', '2025-02-28', '2026-02-21 11:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `taches`
--

CREATE TABLE `taches` (
  `id` int(10) UNSIGNED NOT NULL,
  `projet_id` int(10) UNSIGNED NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('a_faire','en_cours','termine') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_faire',
  `priorite` enum('basse','normale','haute','critique') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `date_echeance` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `taches`
--

INSERT INTO `taches` (`id`, `projet_id`, `titre`, `description`, `statut`, `priorite`, `date_echeance`, `created_at`) VALUES
(1, 1, 'Audit UX existant', 'Analyse des parcours utilisateur actuels', 'termine', 'haute', '2025-01-20', '2026-02-21 11:43:50'),
(2, 1, 'Maquettes Figma', 'Conception des wireframes et prototypes', 'termine', 'haute', '2025-02-10', '2026-02-21 11:43:50'),
(3, 1, 'Intégration HTML/CSS', 'Développement frontend responsive', 'en_cours', 'haute', '2025-03-15', '2026-02-21 11:43:50'),
(4, 1, 'Connexion CMS Strapi', 'Configuration et migration de contenu', 'a_faire', 'normale', '2025-04-01', '2026-02-21 11:43:50'),
(5, 1, 'Tests et déploiement', 'Tests cross-browser + mise en production', 'a_faire', 'critique', '2025-04-25', '2026-02-21 11:43:50'),
(6, 2, 'Cahier des charges', 'Rédaction des spécifications techniques', 'termine', 'haute', '2025-02-10', '2026-02-21 11:43:50'),
(7, 2, 'Architecture API REST', 'Conception backend Node.js + PostgreSQL', 'en_cours', 'haute', '2025-03-30', '2026-02-21 11:43:50'),
(8, 2, 'Dev écran authentification', 'Login/Register + gestion sessions', 'en_cours', 'haute', '2025-04-15', '2026-02-21 11:43:50'),
(9, 2, 'Module tournées', 'CRUD tournées + géolocalisation', 'a_faire', 'normale', '2025-05-30', '2026-02-21 11:43:50'),
(10, 3, 'Setup Google Ads', 'Création et configuration des campagnes', 'termine', 'haute', '2025-01-05', '2026-02-21 11:43:50'),
(11, 3, 'Rapport mensuel janv.', 'Analyse performances et recommandations', 'termine', 'normale', '2025-02-05', '2026-02-21 11:43:50'),
(12, 5, 'Étude capteurs MQTT', 'Protocoles et intégration capteurs IoT', 'en_cours', 'haute', '2025-04-15', '2026-02-21 11:43:50'),
(13, 5, 'Dashboard Chart.js', 'Visualisation temps réel des données', 'a_faire', 'haute', '2025-06-01', '2026-02-21 11:43:50'),
(14, 7, 'Installation matériel', 'Micro, interfaces audio, traitement', 'termine', 'normale', '2025-01-15', '2026-02-21 11:43:50'),
(15, 7, 'Site vitrine podcast', 'WordPress + intégration Spotify/Apple Podcasts', 'termine', 'normale', '2025-02-20', '2026-02-21 11:43:50');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mdp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `mdp`, `created_at`) VALUES
(3, 'Hervanio', 'admin1@nextmux.com', '$2y$10$jtuUgk0Ro.yUOZsxhIqxOOSj7RezNwFsvaMiUs1rLRqbBGiWZq1oO', '2026-02-21 12:42:13'),
(4, 'Maeva', 'admin2@nextmux.com', '$2y$10$a50vLSAf2dqiabsP6CJQNuqV82lwKbKCWHocpXuBIMcDj3bFBGxQW', '2026-02-21 12:43:13'),
(5, 'Jaurès', 'admin3@nextmux.com', '$2y$10$10jhoIHLCJTp0khlF2fx3eiQCVimiPYhfOscSqkiOBANvzUB9ZOL6', '2026-02-21 12:45:55'),
(6, 'Elvis', 'admin4@nextmux.com', '$2y$10$1ZXhJgsimOwyk4/43R5o3.CUYjEly7zeETqAoKDSasjM0ZOdETEM.', '2026-02-21 12:46:27'),
(7, 'Elvire', 'admin5@nextmux.com', '$2y$10$tfDJtQz25Rqlj7nHGeapKODwebzSW68S43sf9zLxWeaNo3soJS0lu', '2026-02-21 12:47:59');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_dashboard`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_dashboard` (
`nb_clients` bigint(21)
,`projets_actifs` bigint(21)
,`taches_ouvertes` bigint(21)
,`total_facture` decimal(34,2)
,`total_encaisse` decimal(34,2)
,`total_depenses` decimal(34,2)
,`resultat_net` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_finance_projets`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_finance_projets` (
`projet_id` int(10) unsigned
,`projet_titre` varchar(200)
,`budget` decimal(12,2)
,`client_nom` varchar(150)
,`total_facture` decimal(34,2)
,`total_encaisse` decimal(34,2)
,`total_depenses` decimal(34,2)
,`marge_nette` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_dashboard`
--
DROP TABLE IF EXISTS `v_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard`  AS SELECT (select count(0) from `clients`) AS `nb_clients`, (select count(0) from `projets` where `projets`.`statut` = 'en_cours') AS `projets_actifs`, (select count(0) from `taches` where `taches`.`statut` <> 'termine') AS `taches_ouvertes`, (select coalesce(sum(`factures`.`montant_ttc`),0) from `factures`) AS `total_facture`, (select coalesce(sum(`paiements`.`montant`),0) from `paiements`) AS `total_encaisse`, (select coalesce(sum(`depenses`.`montant`),0) from `depenses`) AS `total_depenses`, (select coalesce(sum(`paiements`.`montant`),0) from `paiements`) - (select coalesce(sum(`depenses`.`montant`),0) from `depenses`) AS `resultat_net``resultat_net`  ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_finance_projets`
--
DROP TABLE IF EXISTS `v_finance_projets`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_finance_projets`  AS SELECT `p`.`id` AS `projet_id`, `p`.`titre` AS `projet_titre`, `p`.`budget` AS `budget`, `c`.`nom` AS `client_nom`, coalesce(sum(distinct `f`.`montant_ttc`),0) AS `total_facture`, coalesce(sum(`pa`.`montant`),0) AS `total_encaisse`, coalesce(sum(distinct `d`.`montant`),0) AS `total_depenses`, coalesce(sum(`pa`.`montant`),0) - coalesce(sum(distinct `d`.`montant`),0) AS `marge_nette` FROM ((((`projets` `p` left join `clients` `c` on(`c`.`id` = `p`.`client_id`)) left join `factures` `f` on(`f`.`projet_id` = `p`.`id`)) left join `paiements` `pa` on(`pa`.`facture_id` = `f`.`id`)) left join `depenses` `d` on(`d`.`projet_id` = `p`.`id`)) GROUP BY `p`.`id`, `p`.`titre`, `p`.`budget`, `c`.`nom``nom`  ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_email` (`email`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_depense_projet` (`projet_id`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_facture_numero` (`numero`),
  ADD KEY `idx_facture_projet` (`projet_id`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paiement_facture` (`facture_id`);

--
-- Index pour la table `projets`
--
ALTER TABLE `projets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projet_client` (`client_id`);

--
-- Index pour la table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tache_projet` (`projet_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_utilisateur_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `projets`
--
ALTER TABLE `projets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `fk_depense_projet` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `fk_facture_projet` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiement_facture` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `projets`
--
ALTER TABLE `projets`
  ADD CONSTRAINT `fk_projet_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `fk_tache_projet` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

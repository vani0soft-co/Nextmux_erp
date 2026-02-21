-- ============================================================
--  NEXTMUX MINI ERP — Base de données MySQL
--  Version 1.0 | Workshop Full-Stack PHP/MySQL
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Création de la base
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS nextmux_erp
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nextmux_erp;

-- ------------------------------------------------------------
-- TABLE : utilisateurs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS utilisateurs;
CREATE TABLE utilisateurs (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(150) NOT NULL,
  email       VARCHAR(200) NOT NULL,
  mdp         VARCHAR(255) NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : clients
-- ------------------------------------------------------------
DROP TABLE IF EXISTS clients;
CREATE TABLE clients (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(150) NOT NULL,
  email       VARCHAR(200) NOT NULL,
  telephone   VARCHAR(20)  DEFAULT NULL,
  adresse     TEXT         DEFAULT NULL,
  ville       VARCHAR(100) DEFAULT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_client_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : projets
-- ------------------------------------------------------------
DROP TABLE IF EXISTS projets;
CREATE TABLE projets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id   INT UNSIGNED NOT NULL,
  titre       VARCHAR(200) NOT NULL,
  description TEXT         DEFAULT NULL,
  budget      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  statut      ENUM('prospect','en_cours','suspendu','termine') NOT NULL DEFAULT 'en_cours',
  date_debut  DATE         DEFAULT NULL,
  date_fin    DATE         DEFAULT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_projet_client (client_id),
  CONSTRAINT fk_projet_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : taches
-- ------------------------------------------------------------
DROP TABLE IF EXISTS taches;
CREATE TABLE taches (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  projet_id      INT UNSIGNED NOT NULL,
  titre          VARCHAR(200) NOT NULL,
  description    TEXT         DEFAULT NULL,
  statut         ENUM('a_faire','en_cours','termine')           NOT NULL DEFAULT 'a_faire',
  priorite       ENUM('basse','normale','haute','critique')      NOT NULL DEFAULT 'normale',
  date_echeance  DATE         DEFAULT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tache_projet (projet_id),
  CONSTRAINT fk_tache_projet
    FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : factures
-- ------------------------------------------------------------
DROP TABLE IF EXISTS factures;
CREATE TABLE factures (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  projet_id      INT UNSIGNED NOT NULL,
  numero         VARCHAR(30)  NOT NULL,
  montant_ht     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tva            DECIMAL(5,2)  NOT NULL DEFAULT 20.00,
  montant_ttc    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  statut         ENUM('brouillon','envoyee','partiellement_payee','payee') NOT NULL DEFAULT 'brouillon',
  date_emission  DATE         NOT NULL,
  date_echeance  DATE         DEFAULT NULL,
  notes          TEXT         DEFAULT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_facture_numero (numero),
  KEY idx_facture_projet (projet_id),
  CONSTRAINT fk_facture_projet
    FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : paiements
-- ------------------------------------------------------------
DROP TABLE IF EXISTS paiements;
CREATE TABLE paiements (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  facture_id      INT UNSIGNED NOT NULL,
  montant         DECIMAL(12,2) NOT NULL,
  date_paiement   DATE         NOT NULL,
  mode_paiement   ENUM('virement','cheque','especes','carte','autre') NOT NULL DEFAULT 'virement',
  reference       VARCHAR(100) DEFAULT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_paiement_facture (facture_id),
  CONSTRAINT fk_paiement_facture
    FOREIGN KEY (facture_id) REFERENCES factures(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLE : depenses
-- ------------------------------------------------------------
DROP TABLE IF EXISTS depenses;
CREATE TABLE depenses (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  projet_id    INT UNSIGNED DEFAULT NULL,
  libelle      VARCHAR(200) NOT NULL,
  montant      DECIMAL(12,2) NOT NULL,
  categorie    ENUM('materiel','logiciel','prestataire','transport','autre') NOT NULL DEFAULT 'autre',
  date         DATE         NOT NULL,
  justificatif VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_depense_projet (projet_id),
  CONSTRAINT fk_depense_projet
    FOREIGN KEY (projet_id) REFERENCES projets(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  VUES UTILITAIRES
-- ============================================================

-- Vue récapitulative financière par projet
CREATE OR REPLACE VIEW v_finance_projets AS
SELECT
  p.id                                            AS projet_id,
  p.titre                                         AS projet_titre,
  p.budget                                        AS budget,
  c.nom                                           AS client_nom,
  COALESCE(SUM(DISTINCT f.montant_ttc), 0)        AS total_facture,
  COALESCE(SUM(pa.montant), 0)                    AS total_encaisse,
  COALESCE(SUM(DISTINCT d.montant), 0)            AS total_depenses,
  COALESCE(SUM(pa.montant), 0)
    - COALESCE(SUM(DISTINCT d.montant), 0)        AS marge_nette
FROM projets p
LEFT JOIN clients c ON c.id = p.client_id
LEFT JOIN factures f ON f.projet_id = p.id
LEFT JOIN paiements pa ON pa.facture_id = f.id
LEFT JOIN depenses d ON d.projet_id = p.id
GROUP BY p.id, p.titre, p.budget, c.nom;

-- Vue tableau de bord global
CREATE OR REPLACE VIEW v_dashboard AS
SELECT
  (SELECT COUNT(*) FROM clients)                                          AS nb_clients,
  (SELECT COUNT(*) FROM projets WHERE statut = 'en_cours')               AS projets_actifs,
  (SELECT COUNT(*) FROM taches WHERE statut != 'termine')                AS taches_ouvertes,
  (SELECT COALESCE(SUM(montant_ttc), 0) FROM factures)                   AS total_facture,
  (SELECT COALESCE(SUM(montant), 0) FROM paiements)                      AS total_encaisse,
  (SELECT COALESCE(SUM(montant), 0) FROM depenses)                       AS total_depenses,
  (SELECT COALESCE(SUM(montant), 0) FROM paiements)
    - (SELECT COALESCE(SUM(montant), 0) FROM depenses)                   AS resultat_net;

-- ============================================================
--  JEUX DE DONNÉES DE TEST
-- ============================================================

-- Utilisateurs (mdp : Admin123 hashé avec password_hash en PHP)
INSERT INTO utilisateurs (nom, email, mdp) VALUES
('Administrateur',     'admin@nextmux.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/GOm'),
('Utilisateur Demo',   'user@nextmux.com',  '$2y$10$FGW2lmVcH3UcHGTINpjH..HG1mfFx/pM3fh7iXG8DHPQ1l0D3RVVS');

-- Clients
INSERT INTO clients (nom, email, telephone, adresse, ville) VALUES
('TechVision SARL',    'contact@techvision.fr',    '01 23 45 67 89', '12 rue de la Paix',    'Paris'),
('Agence Lumière',     'hello@agence-lumiere.fr',  '04 56 78 90 12', '8 allée des Roses',    'Lyon'),
('BioGreen Solutions', 'info@biogreen.io',          '05 67 89 01 23', '45 avenue du Port',    'Bordeaux'),
('StartupNova',        'team@startupnova.co',       '06 11 22 33 44', '3 square Innovation',  'Nantes'),
('Médias Plus',        'direction@mediasplus.com',  '02 98 76 54 32', '22 boulevard du Théâtre','Rennes');

-- Projets
INSERT INTO projets (client_id, titre, description, budget, statut, date_debut, date_fin) VALUES
(1, 'Refonte site corporate',     'Refonte complète du site web avec CMS headless',        850000.00,  'en_cours',  '2025-01-10', '2025-04-30'),
(1, 'App mobile iOS/Android',     'Application de gestion de tournées commerciales',        2200000.00, 'en_cours',  '2025-02-01', '2025-07-31'),
(2, 'Campagne digitale Q1 2025',  'SEA + SEO + Social Media sur 3 mois',                  500000.00,  'termine',   '2025-01-01', '2025-03-31'),
(2, 'Identité visuelle rebrand',  'Nouveau logo, charte graphique et déclinaisons print',  420000.00,  'termine',   '2024-11-01', '2025-01-15'),
(3, 'Dashboard IoT capteurs',     'Interface temps réel pour capteurs environnementaux',   1500000.00, 'en_cours',  '2025-03-01', '2025-09-30'),
(4, 'MVP SaaS B2B',              'Développement MVP plateforme de gestion RH',            3000000.00, 'prospect',  '2025-05-01', '2025-12-31'),
(5, 'Podcast Studio Setup',       'Intégration technique studio podcast + site',            320000.00,  'termine',   '2024-12-01', '2025-02-28');

-- Tâches
INSERT INTO taches (projet_id, titre, description, statut, priorite, date_echeance) VALUES
(1, 'Audit UX existant',         'Analyse des parcours utilisateur actuels',         'termine',  'haute',    '2025-01-20'),
(1, 'Maquettes Figma',           'Conception des wireframes et prototypes',           'termine',  'haute',    '2025-02-10'),
(1, 'Intégration HTML/CSS',      'Développement frontend responsive',                 'en_cours', 'haute',    '2025-03-15'),
(1, 'Connexion CMS Strapi',      'Configuration et migration de contenu',             'a_faire',  'normale',  '2025-04-01'),
(1, 'Tests et déploiement',      'Tests cross-browser + mise en production',          'a_faire',  'critique', '2025-04-25'),
(2, 'Cahier des charges',        'Rédaction des spécifications techniques',           'termine',  'haute',    '2025-02-10'),
(2, 'Architecture API REST',     'Conception backend Node.js + PostgreSQL',           'en_cours', 'haute',    '2025-03-30'),
(2, 'Dev écran authentification','Login/Register + gestion sessions',                 'en_cours', 'haute',    '2025-04-15'),
(2, 'Module tournées',           'CRUD tournées + géolocalisation',                   'a_faire',  'normale',  '2025-05-30'),
(3, 'Setup Google Ads',          'Création et configuration des campagnes',           'termine',  'haute',    '2025-01-05'),
(3, 'Rapport mensuel janv.',     'Analyse performances et recommandations',           'termine',  'normale',  '2025-02-05'),
(5, 'Étude capteurs MQTT',       'Protocoles et intégration capteurs IoT',            'en_cours', 'haute',    '2025-04-15'),
(5, 'Dashboard Chart.js',        'Visualisation temps réel des données',              'a_faire',  'haute',    '2025-06-01'),
(7, 'Installation matériel',     'Micro, interfaces audio, traitement',               'termine',  'normale',  '2025-01-15'),
(7, 'Site vitrine podcast',      'WordPress + intégration Spotify/Apple Podcasts',    'termine',  'normale',  '2025-02-20');

-- Factures
INSERT INTO factures (projet_id, numero, montant_ht, tva, montant_ttc, statut, date_emission, date_echeance, notes) VALUES
(1, 'FAC-2025-001', 340000.00, 20.00, 408000.00, 'payee',              '2025-01-15', '2025-02-15', 'Acompte 40% — Refonte site TechVision'),
(1, 'FAC-2025-002', 510000.00, 20.00, 612000.00, 'envoyee',            '2025-03-01', '2025-04-01', 'Solde 60% — Refonte site TechVision'),
(2, 'FAC-2025-003', 800000.00, 20.00, 960000.00, 'payee',              '2025-02-05', '2025-03-05', 'Acompte 40% — App mobile iOS/Android'),
(3, 'FAC-2025-004', 500000.00, 20.00, 600000.00, 'payee',              '2025-01-02', '2025-01-31', 'Facturation mensuelle — Campagne digitale Q1'),
(4, 'FAC-2024-015', 420000.00, 20.00, 504000.00, 'payee',              '2024-11-05', '2024-12-05', 'Acompte 100% — Identité visuelle'),
(5, 'FAC-2025-005', 600000.00, 20.00, 720000.00, 'partiellement_payee','2025-03-10', '2025-04-10', 'Acompte 40% — Dashboard IoT'),
(7, 'FAC-2024-014', 320000.00, 20.00, 384000.00, 'payee',              '2024-12-05', '2025-01-05', 'Forfait complet — Podcast Studio');

-- Paiements
INSERT INTO paiements (facture_id, montant, date_paiement, mode_paiement, reference) VALUES
(1, 408000.00, '2025-01-22', 'virement',  'VIR-TechVision-0122'),
(3, 960000.00, '2025-02-12', 'virement',  'VIR-TechVision-0212'),
(4, 600000.00, '2025-01-28', 'virement',  'VIR-Lumiere-0128'),
(5, 504000.00, '2024-11-10', 'cheque',    'CHQ-2024-1234'),
(6, 300000.00, '2025-03-18', 'virement',  'VIR-BioGreen-0318'),
(7, 384000.00, '2024-12-28', 'virement',  'VIR-Medias-1228');

-- Dépenses
INSERT INTO depenses (projet_id, libelle, montant, categorie, date) VALUES
(1,    'Licence Figma Pro (annuel)',          19000.00,  'logiciel',    '2025-01-10'),
(1,    'Stock photos Shutterstock',            8500.00,  'logiciel',    '2025-01-20'),
(2,    'Serveur AWS EC2 (3 mois)',            36000.00,  'logiciel',    '2025-02-01'),
(2,    'Prestataire testeur QA freelance',  120000.00,  'prestataire', '2025-03-01'),
(3,    'Outils SEO SEMrush (trimestre)',      33000.00,  'logiciel',    '2025-01-01'),
(5,    'Capteurs IoT prototype x10',         85000.00,  'materiel',    '2025-03-05'),
(5,    'Formation MQTT équipe',              50000.00,  'prestataire', '2025-03-10'),
(7,    'Microphone Shure SM7B',              39900.00,  'materiel',    '2024-12-10'),
(7,    'Interface audio Focusrite',          24900.00,  'materiel',    '2024-12-10'),
(NULL, 'Abonnement suite Adobe Creative',   60000.00,  'logiciel',    '2025-01-01'),
(NULL, 'Transport déplacements client Q1',  28000.00,  'transport',   '2025-03-31'),
(NULL, 'Hébergement OVH annuel',            12000.00,  'logiciel',    '2025-01-15');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  VÉRIFICATIONS (à exécuter pour tester)
-- ============================================================
-- SELECT * FROM v_dashboard;
-- SELECT * FROM v_finance_projets;
-- SELECT c.nom, COUNT(p.id) AS nb_projets FROM clients c LEFT JOIN projets p ON p.client_id = c.id GROUP BY c.id;

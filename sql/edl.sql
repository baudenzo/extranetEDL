-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 13 fév. 2026 à 13:24
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `edl`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `type` enum('theme','mot_cle','niveau') NOT NULL DEFAULT 'mot_cle',
  `description` text,
  `parent_id` int DEFAULT NULL,
  `niveau` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Catégories, mots-clés et thématiques du référentiel';

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiration` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_expiration` (`expiration`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Tokens pour réinitialisation de mot de passe';



-- --------------------------------------------------------

--
-- Structure de la table `referentiel`
--

DROP TABLE IF EXISTS `referentiel`;
CREATE TABLE IF NOT EXISTS `referentiel` (
  `module` enum('Bases','Conjugaison','Grammaire','Prononciation','Methodologie','Vocabulaire','Au Quotidien') COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveaux` set('A1','A2','B1','B2','C1','C2') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`),
  UNIQUE KEY `unique_code` (`code`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `referentiel`
--

INSERT INTO `referentiel` (`module`, `code`, `contenu`, `niveaux`, `created_at`, `updated_at`) VALUES
('Au Quotidien', 'A-C1', 'Animaux', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Au Quotidien', 'A-C2', 'Météo', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Au Quotidien', 'A-C3', 'L\'heure', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Au Quotidien', 'A-C4', 'Noël', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Au Quotidien', 'A-C5', 'Halloween', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Bases', 'B-C1', 'Culture pays anglosaxons', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Bases', 'B-C2', 'Salutations', 'A1', '2026-01-23 12:59:58', '2026-01-23 12:59:58'),
('Bases', 'B-C3', 'Se présenter', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Bases', 'B-C4', 'Chiffres/dates/heures', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Bases', 'B-C5', 'Construction de phrase', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Bases', 'B-C6', 'Like/dislike', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C1', 'BE et HAVE', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C2', 'Présent simple / -ING', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C3', 'Les temps futurs et passés', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C4', 'Les modaux + niveau A2/B1', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C5', 'Prétérit simple / -ING', 'A1,A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C6', 'Présent perfect simple/ -ING', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C7', 'Les verbes irréguliers', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C8', 'Le conditionnel', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Conjugaison', 'C-C9', 'Les temps complexes (futur antérieur, plus que parfait, subjonctif,...)', 'B1,B2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C1', 'Les pronoms', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C10', 'La fréquence', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C11', 'La comparaison', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C12', 'Les verbes à particules + niveau A2/B1', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C13', 'La voie passive', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C14', 'L\'hypothèse', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C15', 'Les pronoms relatifs (auquel, lesquels,...)', 'B1,B2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C16', 'La mise en relief', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C17', 'Phrases verbales', NULL, '2026-01-23 08:38:45', '2026-01-23 09:29:54'),
('Grammaire', 'G-C2', 'Les adverbes', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C3', 'Mots interrogatifs', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C4', 'Possession', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C5', 'Articles (the, a/an)', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C6', 'Le pluriel des noms', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C7', 'Les adjectifs', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C8', 'La quantité', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Grammaire', 'G-C9', 'Les prépositions', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C1', 'Rédaction d\'email/lettres/messages', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C2', 'Donner son avis', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C3', 'Commenter', 'B1,B2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C4', 'Faire un exposé, un compte rendu, commentaire', 'B1,B2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C5', 'Rédaction CV et lettres', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C6', 'Rédiger en s\'adaptant aux différents styles', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C7', 'Rédaction de toutes sortes de documents', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C8', 'Réaliser des présentations', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Methodologie', 'M-C9', 'Utiliser différents registres de langage', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Prononciation', 'P-C1', 'Phonétique, prononciation, accents + niveau C1/C2', 'A2,B1,C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Prononciation', 'P-C2', 'Intonation, débit + niveau C1/C2', 'B1,B2,C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Vocabulaire', 'V-C1', 'Famille, travail, quotidien, vêtements, nourriture, loisirs, sentiments', 'A1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Vocabulaire', 'V-C2', 'Localisation dans le temps et l\'espace, logement, météo, pays/villes, argent, moyens de transports, événements, médias', 'A2,B1', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Vocabulaire', 'V-C3', 'Sujets culturels (cinéma, spectacles, littérature, art, ...) sujets d\'actualité et faits de société, le système scolaire, les événements, psychologie, enrichissement lexical (synonyme, antonyme, polysémie)', 'B1,B2', '2026-01-23 08:38:45', '2026-01-23 08:38:45'),
('Vocabulaire', 'V-C4', 'Expressions idiomatiques, proverbes, faux amis', 'C1,C2', '2026-01-23 08:38:45', '2026-01-23 08:38:45');

-- --------------------------------------------------------

--
-- Structure de la table `ressources`
--

DROP TABLE IF EXISTS `ressources`;
CREATE TABLE IF NOT EXISTS `ressources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `code_referentiel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text,
  `type_fichier` enum('audio','video','pdf','image','autre') NOT NULL,
  `chemin_fichier` varchar(500) NOT NULL,
  `nom_fichier_original` varchar(255) NOT NULL,
  `taille_fichier` int UNSIGNED DEFAULT NULL COMMENT 'Taille en octets',
  `extension` varchar(10) DEFAULT NULL,
  `uploader_id` int NOT NULL,
  `date_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nb_telechargements` int DEFAULT '0',
  `visible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type_fichier`),
  KEY `idx_uploader` (`uploader_id`),
  KEY `idx_visible` (`visible`),
  KEY `idx_date_upload` (`date_upload`),
  KEY `fk_ressources_referentiel` (`code_referentiel`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Fichiers et ressources pédagogiques';



-- --------------------------------------------------------

--
-- Structure de la table `ressources_categories`
--

DROP TABLE IF EXISTS `ressources_categories`;
CREATE TABLE IF NOT EXISTS `ressources_categories` (
  `ressource_id` int NOT NULL,
  `categorie_id` int NOT NULL,
  PRIMARY KEY (`ressource_id`,`categorie_id`),
  KEY `categorie_id` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Liaison ressources et catégories';

-- --------------------------------------------------------

--
-- Structure de la table `seances`
--

DROP TABLE IF EXISTS `seances`;
CREATE TABLE IF NOT EXISTS `seances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text,
  `objectifs` text COMMENT 'Objectifs pédagogiques',
  `date_seance` date DEFAULT NULL,
  `duree_minutes` int UNSIGNED DEFAULT NULL,
  `formateur_id` int NOT NULL,
  `type_seance` enum('OP','FPC','mixte') NOT NULL DEFAULT 'FPC',
  `statut` enum('planifiee','en_cours','terminee','annulee') DEFAULT 'planifiee',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date_seance`),
  KEY `idx_formateur` (`formateur_id`),
  KEY `idx_type` (`type_seance`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Séances de formation';

-- --------------------------------------------------------

--
-- Structure de la table `seances_categories`
--

DROP TABLE IF EXISTS `seances_categories`;
CREATE TABLE IF NOT EXISTS `seances_categories` (
  `seance_id` int NOT NULL,
  `categorie_id` int NOT NULL,
  PRIMARY KEY (`seance_id`,`categorie_id`),
  KEY `categorie_id` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Liaison séances et catégories/thématiques';

-- --------------------------------------------------------

--
-- Structure de la table `seances_ressources`
--

DROP TABLE IF EXISTS `seances_ressources`;
CREATE TABLE IF NOT EXISTS `seances_ressources` (
  `seance_id` int NOT NULL,
  `ressource_id` int NOT NULL,
  `ordre` int DEFAULT '0' COMMENT 'Ordre d affichage de la ressource dans la séance',
  PRIMARY KEY (`seance_id`,`ressource_id`),
  KEY `ressource_id` (`ressource_id`),
  KEY `idx_ordre` (`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Liaison séances et ressources';

-- --------------------------------------------------------

--
-- Structure de la table `stagiaire_formateur`
--

DROP TABLE IF EXISTS `stagiaire_formateur`;
CREATE TABLE IF NOT EXISTS `stagiaire_formateur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stagiaire_id` int NOT NULL,
  `formateur_id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_stagiaire_formateur` (`stagiaire_id`,`formateur_id`),
  KEY `idx_formateur` (`formateur_id`),
  KEY `idx_stagiaire` (`stagiaire_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numlogin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','formateur','stagiaire OP','stagiaire FPC') COLLATE utf8mb4_unicode_ci NOT NULL,
  `distanciel` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pp/default.jpg',
  `sexe` enum('masculin','feminin','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `numlogin` (`numlogin`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--
-- Déclencheurs `utilisateurs`
--
DROP TRIGGER IF EXISTS `format_nom_prenom_before_insert`;
DELIMITER $$
CREATE TRIGGER `format_nom_prenom_before_insert` BEFORE INSERT ON `utilisateurs` FOR EACH ROW BEGIN
    SET NEW.nom = UPPER(NEW.nom);
    
    SET NEW.prenom = CONCAT(UPPER(LEFT(NEW.prenom, 1)), LOWER(SUBSTRING(NEW.prenom, 2)));
END
$$
DELIMITER ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ressources`
--
ALTER TABLE `ressources`
  ADD CONSTRAINT `fk_ressources_referentiel` FOREIGN KEY (`code_referentiel`) REFERENCES `referentiel` (`code`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `ressources_categories`
--
ALTER TABLE `ressources_categories`
  ADD CONSTRAINT `ressources_categories_ibfk_1` FOREIGN KEY (`ressource_id`) REFERENCES `ressources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ressources_categories_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `seances_categories`
--
ALTER TABLE `seances_categories`
  ADD CONSTRAINT `seances_categories_ibfk_1` FOREIGN KEY (`seance_id`) REFERENCES `seances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seances_categories_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `seances_ressources`
--
ALTER TABLE `seances_ressources`
  ADD CONSTRAINT `seances_ressources_ibfk_1` FOREIGN KEY (`seance_id`) REFERENCES `seances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seances_ressources_ibfk_2` FOREIGN KEY (`ressource_id`) REFERENCES `ressources` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stagiaire_formateur`
--
ALTER TABLE `stagiaire_formateur`
  ADD CONSTRAINT `fk_sf_formateur` FOREIGN KEY (`formateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sf_stagiaire` FOREIGN KEY (`stagiaire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

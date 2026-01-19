-- Script de création des tables pour le référentiel et les séances
-- EDL - École des Langues Grand Calais

USE EDL;

-- Table des catégories du référentiel (mots-clés, thématiques, niveaux)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('theme', 'mot_cle', 'niveau') NOT NULL DEFAULT 'mot_cle',
    description TEXT,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Catégories, mots-clés et thématiques du référentiel';

-- Table des ressources (fichiers uploadés)
CREATE TABLE IF NOT EXISTS ressources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    type_fichier ENUM('audio', 'video', 'pdf', 'image', 'autre') NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    nom_fichier_original VARCHAR(255) NOT NULL,
    taille_fichier INT UNSIGNED COMMENT 'Taille en octets',
    extension VARCHAR(10),
    uploader_id INT NOT NULL,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nb_telechargements INT DEFAULT 0,
    visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type_fichier),
    INDEX idx_uploader (uploader_id),
    INDEX idx_visible (visible),
    INDEX idx_date_upload (date_upload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fichiers et ressources pédagogiques';

-- Table de liaison entre ressources et catégories (plusieurs catégories par ressource)
CREATE TABLE IF NOT EXISTS ressources_categories (
    ressource_id INT NOT NULL,
    categorie_id INT NOT NULL,
    PRIMARY KEY (ressource_id, categorie_id),
    FOREIGN KEY (ressource_id) REFERENCES ressources(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Liaison ressources et catégories';

-- Table des séances de formation
CREATE TABLE IF NOT EXISTS seances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    objectifs TEXT COMMENT 'Objectifs pédagogiques',
    date_seance DATE,
    duree_minutes INT UNSIGNED,
    formateur_id INT NOT NULL,
    type_seance ENUM('OP', 'FPC', 'mixte') NOT NULL DEFAULT 'FPC',
    statut ENUM('planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'planifiee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date_seance),
    INDEX idx_formateur (formateur_id),
    INDEX idx_type (type_seance),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Séances de formation';

-- Table de liaison entre séances et ressources (plusieurs ressources par séance)
CREATE TABLE IF NOT EXISTS seances_ressources (
    seance_id INT NOT NULL,
    ressource_id INT NOT NULL,
    ordre INT DEFAULT 0 COMMENT 'Ordre d affichage de la ressource dans la séance',
    PRIMARY KEY (seance_id, ressource_id),
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    FOREIGN KEY (ressource_id) REFERENCES ressources(id) ON DELETE CASCADE,
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Liaison séances et ressources';

-- Table de liaison entre séances et catégories (thématiques de la séance)
CREATE TABLE IF NOT EXISTS seances_categories (
    seance_id INT NOT NULL,
    categorie_id INT NOT NULL,
    PRIMARY KEY (seance_id, categorie_id),
    FOREIGN KEY (seance_id) REFERENCES seances(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Liaison séances et catégories/thématiques';

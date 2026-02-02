    -- Migration: créer la table de liaison stagiaire <-> formateur
    -- Exécuter ce fichier SQL pour ajouter la table "stagiaire_formateur".

    CREATE TABLE IF NOT EXISTS `stagiaire_formateur` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stagiaire_id` INT NOT NULL,
    `formateur_id` INT NOT NULL,
    `session_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `ux_stagiaire_formateur` (`stagiaire_id`, `formateur_id`),
    KEY `idx_formateur` (`formateur_id`),
    KEY `idx_stagiaire` (`stagiaire_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

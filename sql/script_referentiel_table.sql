CREATE TABLE IF NOT EXISTS referentiel (
    module ENUM('Bases', 'Conjugaison', 'Grammaire', 'Prononciation', 'Methodologie', 'Vocabulaire', 'Au Quotidien') NOT NULL,
    code VARCHAR(10) PRIMARY KEY,
    contenu TEXT NOT NULL,
    niveaux SET('A1', 'A2', 'B1', 'B2', 'C1', 'C2') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_module ON referentiel(module);

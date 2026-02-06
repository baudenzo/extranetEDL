-- Table pour stocker les ressources déposées par les formateurs FPC
CREATE TABLE IF NOT EXISTS ressources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formateur_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(512) NOT NULL,
    type_fichier VARCHAR(100),
    taille_fichier INT,
    date_depot TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

<?php

// Charger la configuration
require_once __DIR__ . '/config.php';

function ConnexionBDD(){
    try {
        $host = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($host, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    } catch (Exception $e) {
        // En production, ne pas afficher le message d'erreur détaillé
        error_log("Erreur de connexion BDD: " . $e->getMessage());
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}
?>
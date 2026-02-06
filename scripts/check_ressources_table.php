<?php
require_once 'connexionbdd.php';
$pdo = ConnexionBDD();
try {
    $result = $pdo->query("DESCRIBE ressources");
    echo "Structure de la table ressources :\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

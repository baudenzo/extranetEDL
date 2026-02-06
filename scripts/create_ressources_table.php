<?php
require_once 'connexionbdd.php';
$pdo = ConnexionBDD();
$sql = file_get_contents('sql/create_ressources_table.sql');
try {
    $pdo->exec($sql);
    echo "Table ressources créée avec succès\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

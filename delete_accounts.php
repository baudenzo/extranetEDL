<?php
// Ce script supprime tous les comptes dont le prénom est "test"


// Vérifie si la date et l'heure correspondent au 5 février à 11h30
$now = new DateTime('now', new DateTimeZone('Europe/Paris'));
file_put_contents(__DIR__ . '/delete_accounts.log', "Date détectée : " . $now->format('Y-m-d H:i:s') . "\n", FILE_APPEND);
if ($now->format('m-d H:i') === '02-05 14:03') {
    require_once 'connexionbdd.php';
    $bdd = ConnexionBDD();
    try {
        $sql = "DELETE FROM utilisateurs WHERE prenom = 'Test'";
        $stmt = $bdd->prepare($sql);
        $stmt->execute();
        echo "Comptes supprimés avec succès.";
    } catch (Exception $e) {
        echo "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    echo "Suppression non autorisée : ce script ne s'exécute que le 5 février à 14h03.";
}

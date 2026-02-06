<?php

$now = new DateTime('now', new DateTimeZone('Europe/Paris'));
file_put_contents(__DIR__ . '/delete_accounts.log', "Date détectée : " . $now->format('Y-m-d H:i:s') . "\n", FILE_APPEND);
if ($now->format('m-d H:i') === '08-28 14:03') {
    require_once 'connexionbdd.php';
    $bdd = ConnexionBDD();
    try {
        $sql = "DELETE FROM utilisateurs WHERE role = 'stagiaire OP'";
        $stmt = $bdd->prepare($sql);
        $stmt->execute();
        echo "Comptes supprimés avec succès.";
    } catch (Exception $e) {
        echo "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    echo "Suppression non autorisée : ce script ne s'exécute que le 28 août à 14h03.";
}

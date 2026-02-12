<?php
/**
 * ===================================================================
 * TEST SIMPLE - VÉRIFICATION PHP
 * ===================================================================
 * 
 * Page de test basique pour vérifier que PHP fonctionne correctement
 * sur le serveur.
 * 
 * UTILISATION :
 *    Accéder à : http://localhost/EDL/test_simple.php
 *    Doit afficher : "Test de base" + date PHP actuelle
 * 
 * TESTS EFFECTUÉS :
 *    - PHP est bien installé et actif
 *    - Configuration minimale fonctionnelle
 *    - Date/timezone correctement configurées
 * 
 * USAGE :
 *    - Déploiement initial : vérifier que PHP fonctionne
 *    - Dépannage : isoler les problèmes de configuration
 *    - Post-migration : valider l'environnement
 * 
 * NOTE :
 *    Fichier de dev/test, peut être supprimé en production.
 * 
 * ===================================================================
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Simple</title>
</head>
<body>
    <h1>Test de base</h1>
    <p>Si vous voyez ce message, PHP fonctionne.</p>
    <?php
    echo "<p>Date PHP: " . date('Y-m-d H:i:s') . "</p>";
    ?>
</body>
</html>

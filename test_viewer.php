<?php
/**
 * ===================================================================
 * TEST VIEWER - DEBUG DE LA VISIONNEUSE
 * ===================================================================
 * 
 * Page de débogage pour tester la visionneuse de ressources.
 * Affiche des informations de debug à chaque étape.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. TESTS SÉQUENTIELS :
 *    ✓ Démarrage de la session
 *    ✓ Connexion à la base de données
 *    ✓ Vérification de la connexion utilisateur
 *    ✓ Création de l'objet PDO
 *    ✓ Récupération de l'ID ressource
 *    ✓ Requête SQL vers la table ressources
 *    ✓ Existence de la ressource
 *    ✓ Contrôle des droits d'accès
 * 
 * 2. AFFICHAGE DES INFOS :
 *    - Messages de succès (✓) ou d'erreur (❌)
 *    - Valeurs des variables importantes
 *    - État de la session et des permissions
 * 
 * 3. DEBUG DES PROBLÈMES COURANTS :
 *    - Problèmes de connexion BDD
 *    - Erreurs de session
 *    - Contrôle d'accès défaillant
 *    - ID ressource invalide
 * 
 * UTILISATION :
 *    Accéder à : test_viewer.php?id=123
 *    (Remplacer 123 par un ID de ressource existant)
 * 
 * ACTIVATION DES ERREURS :
 *    - display_errors : ON
 *    - error_reporting : E_ALL
 *    Affiche toutes les erreurs PHP pour faciliter le debug
 * 
 * USAGE :
 *    - Développement : identifier les problèmes du viewer
 *    - Dépannage : tracer les étapes de chargement
 *    - Validation : vérifier les contrôles d'accès
 * 
 * NOTE :
 *    ⚠️ NE PAS utiliser en production (affiche infos sensibles)
 *    Fichier de dev/test, supprimer ou protéger en production.
 * 
 * ===================================================================
 */

// Test pour déboguer le viewer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Viewer Debug</h2>";

session_start();
echo "✓ Session démarrée<br>";

require_once 'connexionbdd.php';
echo "✓ Connexion BDD incluse<br>";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('❌ Non connecté');
}
echo "✓ Utilisateur connecté<br>";

$pdo = ConnexionBDD();
echo "✓ PDO créé<br>";

$ressource_id = (int)($_GET['id'] ?? 0);
echo "✓ ID ressource: " . $ressource_id . "<br>";

$stmt = $pdo->prepare('SELECT * FROM ressources WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ressource_id]);
$ressource = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ressource) {
    die('❌ Ressource introuvable');
}

echo "✓ Ressource trouvée: " . htmlspecialchars($ressource['nom_fichier_original']) . "<br>";
echo "✓ Chemin: " . htmlspecialchars($ressource['chemin_fichier']) . "<br>";
echo "✓ Type: " . htmlspecialchars($ressource['type_fichier']) . "<br>";

// Vérifier si le fichier existe
if (file_exists($ressource['chemin_fichier'])) {
    echo "✓ Fichier physique existe<br>";
    echo "✓ Taille: " . filesize($ressource['chemin_fichier']) . " octets<br>";
} else {
    echo "❌ Fichier physique n'existe PAS: " . $ressource['chemin_fichier'] . "<br>";
}

echo "<hr>";
echo "<h3>Test affichage PDF:</h3>";
$chemin = htmlspecialchars($ressource['chemin_fichier']);
echo '<embed src="' . $chemin . '" type="application/pdf" width="100%" height="600px">';

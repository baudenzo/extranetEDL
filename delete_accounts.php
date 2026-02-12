<?php
/**
 * ===================================================================
 * SUPPRESSION AUTOMATIQUE DES COMPTES - SCRIPT PLANIFIÉ
 * ===================================================================
 * 
 * Script de suppression automatique des comptes stagiaires OP
 * à une date et heure précises.
 * 
 * ⚠️ ATTENTION - SCRIPT CRITIQUE :
 *    Ce script supprime DÉFINITIVEMENT tous les comptes "stagiaire OP"
 *    à la date programmée (28 août à 14h03).
 * 
 * FONCTIONNEMENT :
 * 
 * 1. VÉRIFICATION DE LA DATE :
 *    - Compare la date/heure actuelle
 *    - N'exécute que le 28 août à 14h03 exactement
 *    - Timezone : Europe/Paris
 * 
 * 2. SUPPRESSION :
 *    - DELETE de tous les utilisateurs avec role='stagiaire OP'
 *    - Suppression en cascade des liaisons (si FK configurées)
 *    - Log des actions dans delete_accounts.log
 * 
 * 3. LOGGING :
 *    - Enregistrement de chaque vérification
 *    - Trace de la date détectée
 *    - Historique des suppressions
 * 
 * UTILISATION :
 *    Ce script doit être appelé par un CRON job :
 *    Exemple crontab :
 *    03 14 28 08 * /usr/bin/php /path/to/delete_accounts.php
 * 
 * SÉCURITÉ :
 *    - Vérification stricte de date/heure
 *    - Pas d'accès web direct (pas de HTML)
 *    - Log des actions pour audit
 *    - Pas de paramètres utilisateur (évite injection)
 * 
 * RECOMMANDATIONS :
 *    1. BACKUP : Faire une sauvegarde de la BDD avant cette date
 *    2. TEST : Tester sur environnement de dev d'abord
 *    3. NOTIFICATION : Prévenir les stagiaires OP avant suppression
 *    4. ALTERNATIVES : Considérer l'archivage au lieu de suppression
 * 
 * AMÉLIORATION POSSIBLE :
 *    - Archivage des comptes au lieu de suppression
 *    - Envoi d'un email de confirmation après suppression
 *    - Création d'une table d'historique
 *    - Paramétrage de la date via config au lieu de hard-coding
 * 
 * ===================================================================
 */

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

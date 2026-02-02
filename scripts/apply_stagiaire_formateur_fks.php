<?php
// Script safe d'ajout des FK pour stagiaire_formateur
// Usage: php scripts/apply_stagiaire_formateur_fks.php

require_once __DIR__ . '/../connexionbdd.php';
$pdo = ConnexionBDD();

function tableExists(PDO $pdo, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
    $stmt->execute(['t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c");
    $stmt->execute(['t' => $table, 'c' => $column]);
    return (bool)$stmt->fetchColumn();
}

function engineIsInnoDB(PDO $pdo, $table) {
    $stmt = $pdo->prepare("SELECT ENGINE FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
    $stmt->execute(['t' => $table]);
    $eng = $stmt->fetchColumn();
    return strtoupper($eng) === 'INNODB';
}

// 1) vérifications
$requiredTables = ['stagiaire_formateur', 'utilisateurs'];
foreach ($requiredTables as $t) {
    if (!tableExists($pdo, $t)) {
        echo "Table manquante: $t\n";
        exit(1);
    }
}

if (!columnExists($pdo, 'utilisateurs', 'id')) {
    echo "La table utilisateurs n'a pas de colonne 'id' valide.\n";
    exit(1);
}
if (!columnExists($pdo, 'stagiaire_formateur', 'stagiaire_id') || !columnExists($pdo, 'stagiaire_formateur', 'formateur_id')) {
    echo "La table stagiaire_formateur doit contenir les colonnes 'stagiaire_id' et 'formateur_id'.\n";
    exit(1);
}

// 2) vérifier engine InnoDB
if (!engineIsInnoDB($pdo, 'utilisateurs')) {
    echo "Attention: la table 'utilisateurs' n'est pas en InnoDB. Convertissez-la en InnoDB avant d'ajouter les FK. Exemple:\n";
    echo "ALTER TABLE utilisateurs ENGINE=InnoDB;\n";
    exit(1);
}

if (!engineIsInnoDB($pdo, 'stagiaire_formateur')) {
    echo "Conversion de 'stagiaire_formateur' en InnoDB...\n";
    $pdo->exec('ALTER TABLE stagiaire_formateur ENGINE=InnoDB');
}

// 3) ajouter les contraintes (si non existantes)
// vérifier d'abord qu'il n'existe pas déjà des contraintes avec ces noms
$stmt = $pdo->prepare("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE table_schema = DATABASE() AND table_name = 'stagiaire_formateur' AND constraint_type = 'FOREIGN KEY'");
$stmt->execute();
$existingFks = $stmt->fetchAll(PDO::FETCH_COLUMN);

$fksToAdd = [];
if (!in_array('fk_sf_stagiaire', $existingFks)) {
    $fksToAdd[] = "ADD CONSTRAINT `fk_sf_stagiaire` FOREIGN KEY (`stagiaire_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE";
}
if (!in_array('fk_sf_formateur', $existingFks)) {
    $fksToAdd[] = "ADD CONSTRAINT `fk_sf_formateur` FOREIGN KEY (`formateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE";
}

if (empty($fksToAdd)) {
    echo "Aucune contrainte à ajouter — les FK existent déjà.\n";
    exit(0);
}

$sql = 'ALTER TABLE `stagiaire_formateur` ' . implode(', ', $fksToAdd) . ';';
try {
    $pdo->exec($sql);
    echo "Clés étrangères ajoutées avec succès.\n";
} catch (PDOException $e) {
    echo "Erreur lors de l'ajout des FK: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;

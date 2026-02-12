<?php
/**
 * ===================================================================
 * GESTION DES LIAISONS FORMATEUR-STAGIAIRE
 * ===================================================================
 * 
 * Page d'administration permettant de gérer les liaisons entre
 * formateurs et stagiaires dans l'application EDL+.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. CRÉATION DE LIAISON :
 *    - Association d'un stagiaire (OP ou FPC) à un formateur
 *    - Formulaire avec dropdowns pour sélectionner stagiaire et formateur
 *    - Utilisation de INSERT IGNORE pour éviter les doublons
 * 
 * 2. MODIFICATION DE LIAISON :
 *    - Changement du formateur associé à un stagiaire
 *    - Mise à jour via modal dédié
 * 
 * 3. SUPPRESSION DE LIAISON :
 *    - Suppression d'une association formateur-stagiaire
 *    - Confirmation avant suppression
 * 
 * 4. AFFICHAGE DES LIAISONS :
 *    - Tableau listant toutes les liaisons actives
 *    - Affichage du nom du stagiaire, du formateur et de la date de création
 *    - Options de modification et suppression pour chaque liaison
 * 
 * TABLE CONCERNÉE :
 * - stagiaire_formateur : Table de jonction contenant :
 *   * id : Identifiant unique de la liaison
 *   * stagiaire_id : Référence vers utilisateurs (stagiaire)
 *   * formateur_id : Référence vers utilisateurs (formateur)
 *   * created_at : Date de création de la liaison
 * 
 * ACCÈS :
 * - Réservé exclusivement aux administrateurs
 * - Redirection vers login si non autorisé
 * 
 * DÉPENDANCES :
 * - connexionbdd.php : Connexion à la base de données
 * - footer.php : Pied de page commun
 * - Table utilisateurs : Pour récupérer les listes de formateurs et stagiaires
 * 
 * ===================================================================
 */

session_start();
include 'connexionbdd.php';

// ===================================================================
// CONTRÔLE D'ACCÈS : ADMINISTRATEURS UNIQUEMENT
// ===================================================================

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// ===================================================================
// INITIALISATION
// ===================================================================

$pdo = ConnexionBDD();

// Message de feedback pour l'utilisateur (succès/erreur)
$feedback = '';

// Récupération des informations de l'administrateur connecté (pour la navbar)
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

// ===================================================================
// TRAITEMENT DES ACTIONS (CRÉATION, MODIFICATION, SUPPRESSION)
// ===================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // -----------------------------------------------------------
        // ACTION : CRÉATION D'UNE NOUVELLE LIAISON
        // -----------------------------------------------------------
        if ($action === 'create') {
            $stagiaire_id = intval($_POST['stagiaire_id'] ?? 0);
            $formateur_id = intval($_POST['formateur_id'] ?? 0);
            
            // Vérification de la validité des IDs
            if ($stagiaire_id <= 0 || $formateur_id <= 0) {
                throw new Exception('Sélection invalide.');
            }
            
            // Insertion de la liaison (INSERT IGNORE évite les doublons)
            $stmt = $pdo->prepare('INSERT IGNORE INTO stagiaire_formateur (stagiaire_id, formateur_id) VALUES (:s, :f)');
            $stmt->execute(['s' => $stagiaire_id, 'f' => $formateur_id]);
            $feedback = 'Liaison ajoutée.';
            
        // -----------------------------------------------------------
        // ACTION : MODIFICATION D'UNE LIAISON (CHANGEMENT DE FORMATEUR)
        // -----------------------------------------------------------
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $formateur_id = intval($_POST['formateur_id'] ?? 0);
            
            // Vérification de la validité des données
            if ($id <= 0 || $formateur_id <= 0) {
                throw new Exception('Données invalides pour la mise à jour.');
            }
            
            // Mise à jour du formateur associé
            $stmt = $pdo->prepare('UPDATE stagiaire_formateur SET formateur_id = :f WHERE id = :id');
            $stmt->execute(['f' => $formateur_id, 'id' => $id]);
            $feedback = 'Liaison mise à jour.';
            
        // -----------------------------------------------------------
        // ACTION : SUPPRESSION D'UNE LIAISON
        // -----------------------------------------------------------
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            // Vérification que l'ID est valide
            if ($id <= 0) {
                throw new Exception('Identifiant invalide.');
            }
            
            // Suppression de la liaison
            $stmt = $pdo->prepare('DELETE FROM stagiaire_formateur WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $feedback = 'Liaison supprimée.';
        }
        
    } catch (Exception $e) {
        // Gestion des erreurs : affichage du message d'erreur
        $feedback = 'Erreur: ' . htmlspecialchars($e->getMessage());
    }
}

// ===================================================================
// RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE
// ===================================================================

// Récupération de toutes les liaisons avec les noms des stagiaires et formateurs
$stmt = $pdo->query('SELECT sf.id, sf.stagiaire_id, sf.formateur_id, sf.created_at,
                 s.prenom AS stagiaire_prenom, s.nom AS stagiaire_nom, s.photo AS stagiaire_photo,
                 f.prenom AS formateur_prenom, f.nom AS formateur_nom
                      FROM stagiaire_formateur sf
                      JOIN utilisateurs s ON s.id = sf.stagiaire_id
                      JOIN utilisateurs f ON f.id = sf.formateur_id
                      ORDER BY sf.id ASC');
$liaisons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste de tous les formateurs (pour les dropdowns)
$stmt = $pdo->prepare("SELECT id, prenom, nom FROM utilisateurs WHERE role = 'formateur' ORDER BY prenom, nom");
$stmt->execute();
$formateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de la liste de tous les stagiaires (OP et FPC) pour la création
$stmt = $pdo->prepare("SELECT id, prenom, nom FROM utilisateurs WHERE role IN ('stagiaire OP','stagiaire FPC') ORDER BY prenom, nom");
$stmt->execute();
$stagiaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ===================================================================
     STRUCTURE HTML: PAGE DE GESTION DES LIAISONS
     ===================================================================
     
     Cette page affiche :
     - Une barre de navigation admin
     - Un bouton pour créer une nouvelle liaison
     - Un tableau listant toutes les liaisons existantes
     - Des modals pour créer/modifier/supprimer des liaisons
     
     ================================================================= -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des liaisons - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .muted-field { color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand site-logo me-3 d-flex align-items-center" href="dashboard.php">
                <img src="img/logo.png" alt="EDL+ logo" style="height:40px; object-fit:contain;" />
            </a>
            <a class="navbar-brand d-flex align-items-center" href="profil.php">
                <img src="<?php echo !empty($current['photo']) ? htmlspecialchars($current['photo']) : 'pp/default.jpg'; ?>" alt="Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <span><?php echo htmlspecialchars(($current['prenom'] ?? $_SESSION['prenom']) . ' ' . ($current['nom'] ?? $_SESSION['nom'])); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdownGestion" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestion</a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestion">
                            <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                            <li><a class="dropdown-item" href="referentiel.php">Gestion référentiel</a></li>
                            <li><a class="dropdown-item active" href="gestion_liaisons.php">Gestion des liaisons</a></li>
                            <li><a class="dropdown-item" href="gestion_ressources.php">Gestion des ressources</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-md-5 mt-4">
        <h2>Gestion des liaisons stagiaire ↔ formateur</h2>
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Ajouter une liaison</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-6">
                        <label class="form-label">Stagiaire</label>
                        <select name="stagiaire_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($stagiaires as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Formateur</label>
                        <select name="formateur_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($formateurs as $f): ?>
                                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['prenom'] . ' ' . $f['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Ajouter la liaison</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Liaisons existantes</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Stagiaire</th>
                                <th>Formateur</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($liaisons)): ?>
                            <tr><td colspan="6" class="text-center">Aucune liaison.</td></tr>
                        <?php else: ?>
                            <?php foreach ($liaisons as $l): ?>
                                <tr>
                                    <td><?php echo $l['id']; ?></td>
                                    <td class="muted-field">
                                        <?php $photo = !empty($l['stagiaire_photo']) ? $l['stagiaire_photo'] : 'pp/default.jpg'; ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="pp" class="rounded-circle me-2" style="width:36px;height:36px;object-fit:cover;vertical-align:middle;"> 
                                        <?php echo htmlspecialchars($l['stagiaire_prenom'] . ' ' . $l['stagiaire_nom']); ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                            <select name="formateur_id" class="form-select form-select-sm me-2" style="min-width:200px;">
                                                <?php foreach ($formateurs as $f): ?>
                                                    <option value="<?php echo $f['id']; ?>" <?php echo $f['id'] == $l['formateur_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['prenom'] . ' ' . $f['nom']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary me-2">Sauvegarder</button>
                                        </form>
                                    </td>
                                    <td><?php echo $l['created_at']; ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Supprimer cette liaison ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

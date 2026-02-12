<?php
/**
 * ===================================================================
 * GESTION DES UTILISATEURS - INTERFACE ADMINISTRATEUR
 * ===================================================================
 * 
 * Page d'administration permettant la gestion complète (CRUD) des
 * utilisateurs de l'application EDL+.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. CRÉATION D'UTILISATEUR :
 *    - Formulaire de création avec tous les champs nécessaires
 *    - Upload de photo de profil (ou photo par défaut selon sexe)
 *    - Option d'envoi d'email avec identifiants
 *    - Génération automatique du mot de passe haché (SHA2-256)
 * 
 * 2. MODIFICATION D'UTILISATEUR :
 *    - Modification en masse : édition de tous les utilisateurs affichés
 *    - Modification individuelle : via modal détaillé
 *    - Changement de mot de passe optionnel
 *    - Upload de nouvelle photo de profil
 * 
 * 3. SUPPRESSION D'UTILISATEUR :
 *    - Suppression avec confirmation
 *    - Nettoyage de la photo de profil
 *    - Suppression des liaisons dans stagiaire_formateur
 * 
 * 4. RECHERCHE :
 *    - Recherche par prénom, nom, email, login
 *    - Filtrage en temps réel de la liste
 * 
 * RÔLES SUPPORTÉS :
 * - admin : Administrateur
 * - formateur : Formateur
 * - stagiaire OP : Stagiaire Objectif Professionnel
 * - stagiaire FPC : Stagiaire Formation Professionnelle Continue
 * 
 * OPTIONS SUPPLÉMENTAIRES :
 * - Distanciel : Disponible pour les stagiaires FPC
 * 
 * ACCÈS :
 * - Réservé exclusivement aux administrateurs
 * - Redirection si non autorisé
 * 
 * SÉCURITÉ :
 * - Validation et sanitisation de tous les champs
 * - Mots de passe hachés avec SHA2-256
 * - Contrôle des rôles autorisés
 * - Vérification des formats de fichiers (images uniquement)
 * 
 * DÉPENDANCES :
 * - connexionbdd.php : Connexion à la base de données
 * - email_functions.php : Fonction envoyerEmailNouveauCompte()
 * - footer.php : Pied de page commun
 * 
 * ===================================================================
 */

session_start();
include 'connexionbdd.php';
include 'email_functions.php';

// ===================================================================
// CONTRÔLE D'ACCÈS : ADMINISTRATEURS UNIQUEMENT
// ===================================================================

// Vérification que l'utilisateur est connecté et a le rôle admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// ===================================================================
// INITIALISATION
// ===================================================================

$pdo = ConnexionBDD();

// Récupération des informations de l'administrateur connecté (pour l'affichage)
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

/**
 * ====================================
 * FONCTION : sanitize_role
 * ====================================
 * Valide que le rôle est parmi les valeurs autorisées.
 * 
 * @param string $role Le rôle à valider
 * @return string|null Le rôle validé ou null si invalide
 */
function sanitize_role($role) {
    $allowed = ['admin', 'formateur', 'stagiaire OP', 'stagiaire FPC'];
    return in_array($role, $allowed, true) ? $role : null;
}

/**
 * ====================================
 * FONCTION : sanitize_sexe
 * ====================================
 * Valide que le sexe est parmi les valeurs autorisées.
 * 
 * @param string $sexe Le sexe à valider
 * @return string|null Le sexe validé ou null si invalide
 */
function sanitize_sexe($sexe) {
    $allowed = ['masculin', 'feminin', 'autre'];
    return in_array($sexe, $allowed, true) ? $sexe : null;
}

/**
 * ====================================
 * FONCTION : getDefaultPhoto
 * ====================================
 * Retourne le chemin vers la photo de profil par défaut
 * selon le sexe de l'utilisateur.
 * 
 * @param string $sexe Le sexe de l'utilisateur
 * @return string Le chemin vers la photo par défaut
 */
function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

// Message de feedback pour l'utilisateur (succès/erreur)
$feedback = '';

// Paramètre de recherche
$q = trim($_GET['q'] ?? '');

// ===================================================================
// TRAITEMENT DES ACTIONS (CRÉATION, MODIFICATION, SUPPRESSION)
// ===================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // -----------------------------------------------------------
        // ACTION : CRÉATION D'UN NOUVEL UTILISATEUR
        // -----------------------------------------------------------
        if ($action === 'create') {
            $email = trim($_POST['email'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $numlogin = trim($_POST['numlogin'] ?? '');
            $role = sanitize_role($_POST['role'] ?? '');
            $sexe = sanitize_sexe($_POST['sexe'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $distanciel = isset($_POST['distanciel']) && $_POST['distanciel'] === '1' ? 1 : 0;

            // Vérification que tous les champs obligatoires sont remplis
            if (!$email || !$prenom || !$nom || !$numlogin || !$role || !$sexe || !$password) {
                throw new Exception('Tous les champs sont requis pour la création.');
            }

            // Insertion du nouvel utilisateur dans la base de données
            // Le mot de passe est haché avec SHA2-256
            $stmt = $pdo->prepare('INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe, distanciel) VALUES (:email, :prenom, :nom, :numlogin, SHA2(:password, 256), :role, :sexe, :distanciel)');
            $stmt->execute([
                'email' => $email,
                'prenom' => $prenom,
                'nom' => $nom,
                'numlogin' => $numlogin,
                'password' => $password,
                'role' => $role,
                'sexe' => $sexe,
                'distanciel' => $distanciel,
            ]);
            
            // Récupération de l'ID du nouvel utilisateur créé
            $newId = (int)$pdo->lastInsertId();

            // --- Gestion de la photo de profil ---
            // Si une photo est uploadée, la traiter, sinon utiliser la photo par défaut
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['photo']['tmp_name'];
                $origName = $_FILES['photo']['name'];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                
                // Vérification du format de l'image
                if (in_array($ext, $allowed, true)) {
                    $imgDir = __DIR__ . DIRECTORY_SEPARATOR . 'pp';
                    
                    // Vérification que le dossier pp existe
                    if (!is_dir($imgDir)) {
                        $feedback = 'Utilisateur créé. Dossier pp introuvable, photo ignorée.';
                    } else {
                        // Enregistrement de la photo avec l'ID de l'utilisateur comme nom
                        $targetFs = $imgDir . DIRECTORY_SEPARATOR . $newId . '.' . $ext;
                        $targetWeb = 'pp/' . $newId . '.' . $ext;
                        if (move_uploaded_file($tmpPath, $targetFs)) {
                            $up = $pdo->prepare('UPDATE utilisateurs SET photo = :photo WHERE id = :id');
                            $up->execute(['photo' => $targetWeb, 'id' => $newId]);
                            $feedback = 'Utilisateur créé avec succès.';
                        } else {
                            $feedback = 'Utilisateur créé, mais échec de l\'enregistrement de la photo.';
                        }
                    }
                } else {
                    $feedback = 'Utilisateur créé. Format de photo non supporté (autorisé: jpg, jpeg, png).';
                }
            } else {
                // Pas de photo uploadée : utilisation de la photo par défaut selon le sexe
                $defaultPhoto = getDefaultPhoto($sexe);
                $up = $pdo->prepare('UPDATE utilisateurs SET photo = :photo WHERE id = :id');
                $up->execute(['photo' => $defaultPhoto, 'id' => $newId]);
                $feedback = 'Utilisateur créé avec succès.';
            }
            
            // --- Option : Envoi d'email avec les identifiants ---
            if (isset($_POST['envoyer_email']) && $_POST['envoyer_email'] === '1') {
                $resultEmail = envoyerEmailNouveauCompte($email, $prenom . ' ' . $nom, $numlogin, $password);
                if ($resultEmail['success']) {
                    $feedback .= ' Email envoyé avec les identifiants.';
                } else {
                    $feedback .= ' Attention : échec de l\'envoi de l\'email.';
                }
            }
            
        // -----------------------------------------------------------
        // ACTION : MISE À JOUR MULTIPLE DE TOUS LES UTILISATEURS
        // -----------------------------------------------------------
        } elseif ($action === 'update_all') {
            // Mise à jour multiple de tous les utilisateurs modifiés
            $ids = $_POST['ids'] ?? [];
            $emails = $_POST['emails'] ?? [];
            $prenoms = $_POST['prenoms'] ?? [];
            $noms = $_POST['noms'] ?? [];
            $numlogins = $_POST['numlogins'] ?? [];
            $roles = $_POST['roles'] ?? [];
            $sexes = $_POST['sexes'] ?? [];
            $passwords = $_POST['passwords'] ?? [];
            
            $updateCount = 0;
            $distanciels = $_POST['distanciels'] ?? [];
            
            // Itération sur tous les utilisateurs à mettre à jour
            foreach ($ids as $id) {
                $id = intval($id);
                $email = trim($emails[$id] ?? '');
                $prenom = trim($prenoms[$id] ?? '');
                $nom = trim($noms[$id] ?? '');
                $numlogin = trim($numlogins[$id] ?? '');
                $role = sanitize_role($roles[$id] ?? '');
                $sexe = sanitize_sexe($sexes[$id] ?? '');
                $password = trim($passwords[$id] ?? '');
                $dist = isset($distanciels[$id]) && $distanciels[$id] == '1' ? 1 : 0;
                
                // Vérification de la validité des données
                if ($id > 0 && $email && $prenom && $nom && $numlogin && $role && $sexe) {
                    // Récupérer les valeurs actuelles pour détecter les changements
                    $stmt_current = $pdo->prepare('SELECT email, prenom, nom, numlogin, role, sexe FROM utilisateurs WHERE id = :id');
                    $stmt_current->execute(['id' => $id]);
                    $current = $stmt_current->fetch(PDO::FETCH_ASSOC);
                    
                    if ($current) {
                        // Vérifier si quelque chose a changé (hors mot de passe)
                        $hasChanged = ($current['email'] !== $email || 
                                      $current['prenom'] !== $prenom || 
                                      $current['nom'] !== $nom || 
                                      $current['numlogin'] !== $numlogin || 
                                      $current['role'] !== $role || 
                                      $current['sexe'] !== $sexe ||
                                      $password !== '');
                        
                        // Mise à jour uniquement si des changements ont été détectés
                        if ($hasChanged) {
                            // Si un nouveau mot de passe est fourni, le hacher
                            if ($password !== '') {
                                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, distanciel = :distanciel, password = SHA2(:password, 256) WHERE id = :id');
                                $stmt->execute([
                                    'email' => $email,
                                    'prenom' => $prenom,
                                    'nom' => $nom,
                                    'numlogin' => $numlogin,
                                    'role' => $role,
                                    'sexe' => $sexe,
                                    'distanciel' => $dist,
                                    'password' => $password,
                                    'id' => $id,
                                ]);
                            } else {
                                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, distanciel = :distanciel WHERE id = :id');
                                $stmt->execute([
                                    'email' => $email,
                                    'prenom' => $prenom,
                                    'nom' => $nom,
                                    'numlogin' => $numlogin,
                                    'role' => $role,
                                    'sexe' => $sexe,
                                    'distanciel' => $dist,
                                    'id' => $id,
                                ]);
                            }
                            $updateCount++;
                        }
                    }
                }
            }
            $feedback = $updateCount > 0 ? "$updateCount utilisateur(s) mis à jour." : 'Aucune modification effectuée.';
            
        // -----------------------------------------------------------
        // ACTION : MISE À JOUR INDIVIDUELLE D'UN UTILISATEUR
        // -----------------------------------------------------------
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $numlogin = trim($_POST['numlogin'] ?? '');
            $role = sanitize_role($_POST['role'] ?? '');
            $sexe = sanitize_sexe($_POST['sexe'] ?? '');
            $distanciel = isset($_POST['distanciel']) && $_POST['distanciel'] === '1' ? 1 : 0;
            $password = trim($_POST['password'] ?? '');

            // Vérification de la validité des données
            if ($id <= 0 || !$email || !$prenom || !$nom || !$numlogin || !$role || !$sexe) {
                throw new Exception('Champs invalides pour la mise à jour.');
            }

            // Mise à jour avec ou sans changement de mot de passe
            if ($password !== '') {
                // Mise à jour avec nouveau mot de passe (haché avec SHA2-256)
                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, distanciel = :distanciel, password = SHA2(:password, 256) WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'numlogin' => $numlogin,
                    'role' => $role,
                    'sexe' => $sexe,
                    'distanciel' => $distanciel,
                    'password' => $password,
                    'id' => $id,
                ]);
            } else {
                // Mise à jour sans changement de mot de passe
                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, distanciel = :distanciel WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'numlogin' => $numlogin,
                    'role' => $role,
                    'sexe' => $sexe,
                    'distanciel' => $distanciel,
                    'id' => $id,
                ]);
            }
            $feedback = 'Utilisateur mis à jour.';
            
        // -----------------------------------------------------------
        // ACTION : SUPPRESSION D'UN UTILISATEUR
        // -----------------------------------------------------------
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            // Vérification que l'ID est valide
            if ($id <= 0) {
                throw new Exception('Identifiant invalide pour suppression.');
            }
            
            // Suppression de l'utilisateur de la base de données
            // Note : Les liaisons dans stagiaire_formateur sont supprimées via CASCADE
            $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $feedback = 'Utilisateur supprimé.';
        }
        
    } catch (Exception $e) {
        // Gestion des erreurs : affichage du message d'erreur
        $feedback = 'Erreur: ' . htmlspecialchars($e->getMessage());
    }
}

// ===================================================================
// RECHERCHE ET RÉCUPÉRATION DES UTILISATEURS
// ===================================================================

// Si un terme de recherche est fourni (paramètre 'q' dans l'URL)
if ($q !== '') {
    // Recherche dans email, prénom, nom et login
    $stmt = $pdo->prepare('SELECT id, email, prenom, nom, numlogin, role, sexe, photo, created_at, distanciel
                            FROM utilisateurs
                            WHERE email LIKE :q OR prenom LIKE :q OR nom LIKE :q OR numlogin LIKE :q
                            ORDER BY id ASC');
    $like = '%' . $q . '%';
    $stmt->execute(['q' => $like]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si aucune recherche : récupérer tous les utilisateurs
    $stmt = $pdo->query('SELECT id, email, prenom, nom, numlogin, role, sexe, photo, created_at, distanciel FROM utilisateurs ORDER BY id ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- ===================================================================
     STRUCTURE HTML: PAGE DE GESTION DES UTILISATEURS
     ===================================================================
     
     Cette page affiche :
     - Une barre de navigation admin
     - Un bouton pour créer un nouvel utilisateur
     - Une barre de recherche
     - Un tableau/liste des utilisateurs avec options d'édition
     - Des modals pour créer/modifier/supprimer
     
     ================================================================= -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdownGestion" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Gestion
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestion">
                            <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                            <li><a class="dropdown-item" href="referentiel.php">Gestion référentiel</a></li>
                            <li><a class="dropdown-item" href="gestion_liaisons.php">Gestion des liaisons</a></li>
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
        <h2>Gestion des utilisateurs</h2>
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Créer un nouvel utilisateur</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Numéro de login</label>
                        <div class="input-group">
                            <input type="text" name="numlogin" id="numlogin" class="form-control" required>
                            <button type="button" class="btn btn-secondary" id="btnGenererLogin" title="Générer un login aléatoire">
                                🎲 Générer
                            </button>
                        </div>
                        <small class="text-muted">6 caractères alphanumériques</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rôle</label>
                            <select name="role" id="create_role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="formateur">Formateur</option>
                            <option value="stagiaire OP">Stagiaire OP</option>
                            <option value="stagiaire FPC">Stagiaire FPC</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sexe</label>
                        <select name="sexe" class="form-select" required>
                            <option value="masculin">Masculin</option>
                            <option value="feminin">Féminin</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Photo (optionnel)</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted">Formats acceptés: JPG, JPEG, PNG.</small>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="envoyer_email" value="1" id="envoyerEmail">
                            <label class="form-check-label" for="envoyerEmail">
                                Envoyer les identifiants par email à l'utilisateur
                            </label>
                        </div>
                    </div>
                    <div class="col-12" id="createDistRow" style="display:none; margin-top:8px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="distanciel" value="1" id="createDistCheck">
                            <label class="form-check-label" for="createDistCheck">Session en distanciel (stagiaire FPC)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Liste des utilisateurs</span>
                <form method="get" class="d-flex align-items-center">
                    <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>">
                    <button type="submit" class="btn btn-sm btn-secondary">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a href="gestion_utilisateurs.php" class="btn btn-sm btn-link ms-2">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="update_all">
                    
                    <!-- Vue tableau pour desktop -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Photo</th>
                                        <th>Email</th>
                                        <th>Prénom</th>
                                        <th>Nom</th>
                                        <th>Login</th>
                                        <th>Rôle</th>
                                        <th>Distanciel</th>
                                        <th>Sexe</th>
                                        <th>Mot de passe</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">Aucun utilisateur trouvé<?php echo $q !== '' ? ' pour la recherche "' . htmlspecialchars($q) . '"' : ''; ?>.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['id']); ?><input type="hidden" name="ids[]" value="<?php echo htmlspecialchars($u['id']); ?>"></td>
                                        <td><img src="<?php echo htmlspecialchars($u['photo'] ?: 'pp/default.jpg'); ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></td>
                                        <td><input type="email" name="emails[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" required></td>
                                        <td><input type="text" name="prenoms[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['prenom']); ?>" style="max-width: 100px;" required></td>
                                        <td><input type="text" name="noms[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['nom']); ?>" style="max-width: 130px;" required></td>
                                        <td><input type="text" name="numlogins[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['numlogin']); ?>" style="max-width: 160px;" required></td>
                                        <td>
                                            <select name="roles[<?php echo $u['id']; ?>]" class="form-select" required>
                                                <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                                <option value="formateur" <?php echo $u['role']==='formateur'?'selected':''; ?>>Formateur</option>
                                                <option value="stagiaire OP" <?php echo $u['role']==='stagiaire OP'?'selected':''; ?>>Stagiaire OP</option>
                                                <option value="stagiaire FPC" <?php echo $u['role']==='stagiaire FPC'?'selected':''; ?>>Stagiaire FPC</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="distanciels[<?php echo $u['id']; ?>]" value="1" <?php echo !empty($u['distanciel']) ? 'checked' : ''; ?> >
                                        </td>
                                        <td>
                                            <select name="sexes[<?php echo $u['id']; ?>]" class="form-select" style="min-width: 110px;" required>
                                                <option value="masculin" <?php echo $u['sexe']==='masculin'?'selected':''; ?>>Masculin</option>
                                                <option value="feminin" <?php echo $u['sexe']==='feminin'?'selected':''; ?>>Féminin</option>
                                                <option value="autre" <?php echo $u['sexe']==='autre'?'selected':''; ?>>Autre</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="password" name="passwords[<?php echo $u['id']; ?>]" class="form-control" placeholder="Optionnel" style="max-width:150px;">
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-success btn-sm save-user-btn" style="width: 110px;" data-user-id="<?php echo $u['id']; ?>">Sauvegarder</button>
                                                <button type="button" class="btn btn-danger btn-sm delete-user-btn" style="width: 110px;" data-user-id="<?php echo $u['id']; ?>">Supprimer</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Vue cartes pour mobile -->
                    <div class="d-md-none">
                        <?php if (empty($users)): ?>
                            <div class="alert alert-info text-center">
                                Aucun utilisateur trouvé<?php echo $q !== '' ? ' pour la recherche "' . htmlspecialchars($q) . '"' : ''; ?>.
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($users as $u): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <input type="hidden" name="ids[]" value="<?php echo htmlspecialchars($u['id']); ?>">
                                    
                                    <div class="text-center mb-3">
                                        <img src="<?php echo htmlspecialchars($u['photo'] ?: 'pp/default.jpg'); ?>" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                                        <p class="mt-2 mb-0 fw-bold">ID: <?php echo htmlspecialchars($u['id']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" name="emails[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" required>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Prénom</label>
                                            <input type="text" name="prenoms[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['prenom']); ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Nom</label>
                                            <input type="text" name="noms[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['nom']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Login</label>
                                        <input type="text" name="numlogins[<?php echo $u['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($u['numlogin']); ?>" required>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Rôle</label>
                                            <select name="roles[<?php echo $u['id']; ?>]" class="form-select" required>
                                                <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                                <option value="formateur" <?php echo $u['role']==='formateur'?'selected':''; ?>>Formateur</option>
                                                <option value="stagiaire OP" <?php echo $u['role']==='stagiaire OP'?'selected':''; ?>>Stagiaire OP</option>
                                                <option value="stagiaire FPC" <?php echo $u['role']==='stagiaire FPC'?'selected':''; ?>>Stagiaire FPC</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold">Sexe</label>
                                            <select name="sexes[<?php echo $u['id']; ?>]" class="form-select" required>
                                                <option value="masculin" <?php echo $u['sexe']==='masculin'?'selected':''; ?>>Masculin</option>
                                                <option value="feminin" <?php echo $u['sexe']==='feminin'?'selected':''; ?>>Féminin</option>
                                                <option value="autre" <?php echo $u['sexe']==='autre'?'selected':''; ?>>Autre</option>
                                            </select>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="distanciels[<?php echo $u['id']; ?>]" value="1" id="dist_<?php echo $u['id']; ?>" <?php echo !empty($u['distanciel']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="dist_<?php echo $u['id']; ?>">Session en distanciel (stagiaire FPC)</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nouveau mot de passe (optionnel)</label>
                                        <input type="password" name="passwords[<?php echo $u['id']; ?>]" class="form-control" placeholder="Laisser vide pour ne pas modifier">
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success save-user-btn" data-user-id="<?php echo $u['id']; ?>">Sauvegarder</button>
                                        <button type="button" class="btn btn-danger delete-user-btn" data-user-id="<?php echo $u['id']; ?>">Supprimer cet utilisateur</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du bouton "Générer login"
        document.getElementById('btnGenererLogin').addEventListener('click', function() {
            const caracteres = '0123456789abcdefghijklmnopqrstuvwxyz';
            let login = '';
            for (let i = 0; i < 6; i++) {
                login += caracteres.charAt(Math.floor(Math.random() * caracteres.length));
            }
            document.getElementById('numlogin').value = login;
        });
        
        // Toggle distanciel checkbox for create form
        var createRole = document.getElementById('create_role');
        var createDistRow = document.getElementById('createDistRow');
        if(createRole && createDistRow){
            function updateCreateDist(){ createDistRow.style.display = (createRole.value === 'stagiaire FPC') ? '' : 'none'; }
            createRole.addEventListener('change', updateCreateDist);
            updateCreateDist();
        }
        
        // Gestion des boutons de suppression
        document.querySelectorAll('.delete-user-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (confirm('Supprimer cet utilisateur ?')) {
                    var userId = this.getAttribute('data-user-id');
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + userId + '">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Gestion des boutons de sauvegarde par utilisateur (création sécurisée du formulaire)
        document.querySelectorAll('.save-user-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-user-id');
                if (!id) return;

                function getVal(field) {
                    var el = document.querySelector('[name="' + field + '[' + id + ']"]');
                    return el ? el.value : '';
                }

                var form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';

                function add(name, value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }

                add('action', 'update');
                add('id', id);
                add('email', getVal('emails'));
                add('prenom', getVal('prenoms'));
                add('nom', getVal('noms'));
                add('numlogin', getVal('numlogins'));
                var roleEl = document.querySelector('[name="roles[' + id + ']"]');
                var sexeEl = document.querySelector('[name="sexes[' + id + ']"]');
                var pwdEl = document.querySelector('[name="passwords[' + id + ']"]');
                add('role', roleEl ? roleEl.value : '');
                add('sexe', sexeEl ? sexeEl.value : '');
                add('password', pwdEl ? pwdEl.value : '');
                var distEl = document.querySelector('[name="distanciels[' + id + ']"]');
                add('distanciel', distEl && distEl.checked ? '1' : '0');

                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>

<?php
session_start();
include 'connexionbdd.php';
include 'email_functions.php';

// accès réservé aux admins
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$pdo = ConnexionBDD();
// informations de l'utilisateur utilisant la session
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}


function sanitize_role($role) {
    $allowed = ['admin', 'formateur', 'stagiaire OP', 'stagiaire FPC'];
    return in_array($role, $allowed, true) ? $role : null;
}

function sanitize_sexe($sexe) {
    $allowed = ['masculin', 'feminin', 'autre'];
    return in_array($sexe, $allowed, true) ? $sexe : null;
}

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

$feedback = '';
$q = trim($_GET['q'] ?? '');

// actions pour créer, supprimer ou modifier un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $email = trim($_POST['email'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $numlogin = trim($_POST['numlogin'] ?? '');
            $role = sanitize_role($_POST['role'] ?? '');
            $sexe = sanitize_sexe($_POST['sexe'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (!$email || !$prenom || !$nom || !$numlogin || !$role || !$sexe || !$password) {
                throw new Exception('Tous les champs sont requis pour la création.');
            }

            $stmt = $pdo->prepare('INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe) VALUES (:email, :prenom, :nom, :numlogin, SHA2(:password, 256), :role, :sexe)');
            $stmt->execute([
                'email' => $email,
                'prenom' => $prenom,
                'nom' => $nom,
                'numlogin' => $numlogin,
                'password' => $password,
                'role' => $role,
                'sexe' => $sexe,
            ]);
            // dernier id inséré
            $newId = (int)$pdo->lastInsertId();

            // gestion de la photo de profil optionnelle
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['photo']['tmp_name'];
                $origName = $_FILES['photo']['name'];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed, true)) {
                    $imgDir = __DIR__ . DIRECTORY_SEPARATOR . 'pp';
                    if (!is_dir($imgDir)) {
                        // Si le dossier pp est manquant, garder la photo par défaut
                        $feedback = 'Utilisateur créé. Dossier pp introuvable, photo ignorée.';
                    } else {
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
                $defaultPhoto = getDefaultPhoto($sexe);
                $up = $pdo->prepare('UPDATE utilisateurs SET photo = :photo WHERE id = :id');
                $up->execute(['photo' => $defaultPhoto, 'id' => $newId]);
                $feedback = 'Utilisateur créé avec succès.';
            }
            
            if (isset($_POST['envoyer_email']) && $_POST['envoyer_email'] === '1') {
                $resultEmail = envoyerEmailNouveauCompte($email, $prenom . ' ' . $nom, $numlogin, $password);
                if ($resultEmail['success']) {
                    $feedback .= ' Email envoyé avec les identifiants.';
                } else {
                    $feedback .= ' Attention : échec de l\'envoi de l\'email.';
                }
            }
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
            foreach ($ids as $id) {
                $id = intval($id);
                $email = trim($emails[$id] ?? '');
                $prenom = trim($prenoms[$id] ?? '');
                $nom = trim($noms[$id] ?? '');
                $numlogin = trim($numlogins[$id] ?? '');
                $role = sanitize_role($roles[$id] ?? '');
                $sexe = sanitize_sexe($sexes[$id] ?? '');
                $password = trim($passwords[$id] ?? '');
                
                if ($id > 0 && $email && $prenom && $nom && $numlogin && $role && $sexe) {
                    // Récupérer les valeurs actuelles
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
                        
                        if ($hasChanged) {
                            if ($password !== '') {
                                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, password = SHA2(:password, 256) WHERE id = :id');
                                $stmt->execute([
                                    'email' => $email,
                                    'prenom' => $prenom,
                                    'nom' => $nom,
                                    'numlogin' => $numlogin,
                                    'role' => $role,
                                    'sexe' => $sexe,
                                    'password' => $password,
                                    'id' => $id,
                                ]);
                            } else {
                                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe WHERE id = :id');
                                $stmt->execute([
                                    'email' => $email,
                                    'prenom' => $prenom,
                                    'nom' => $nom,
                                    'numlogin' => $numlogin,
                                    'role' => $role,
                                    'sexe' => $sexe,
                                    'id' => $id,
                                ]);
                            }
                            $updateCount++;
                        }
                    }
                }
            }
            $feedback = $updateCount > 0 ? "$updateCount utilisateur(s) mis à jour." : 'Aucune modification effectuée.';
        } elseif ($action === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $numlogin = trim($_POST['numlogin'] ?? '');
            $role = sanitize_role($_POST['role'] ?? '');
            $sexe = sanitize_sexe($_POST['sexe'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($id <= 0 || !$email || !$prenom || !$nom || !$numlogin || !$role || !$sexe) {
                throw new Exception('Champs invalides pour la mise à jour.');
            }

            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe, password = SHA2(:password, 256) WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'numlogin' => $numlogin,
                    'role' => $role,
                    'sexe' => $sexe,
                    'password' => $password,
                    'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET email = :email, prenom = :prenom, nom = :nom, numlogin = :numlogin, role = :role, sexe = :sexe WHERE id = :id');
                $stmt->execute([
                    'email' => $email,
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'numlogin' => $numlogin,
                    'role' => $role,
                    'sexe' => $sexe,
                    'id' => $id,
                ]);
            }
            $feedback = 'Utilisateur mis à jour.';
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Identifiant invalide pour suppression.');
            }
            $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $feedback = 'Utilisateur supprimé.';
        }
    } catch (Exception $e) {
        $feedback = 'Erreur: ' . htmlspecialchars($e->getMessage());
    }
}

// recherche des utilisateurs en fonction du nom ou prénom
if ($q !== '') {
    $stmt = $pdo->prepare('SELECT id, email, prenom, nom, numlogin, role, sexe, photo, created_at
                            FROM utilisateurs
                            WHERE email LIKE :q OR prenom LIKE :q OR nom LIKE :q OR numlogin LIKE :q
                            ORDER BY id ASC');
    $like = '%' . $q . '%';
    $stmt->execute(['q' => $like]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query('SELECT id, email, prenom, nom, numlogin, role, sexe, photo, created_at FROM utilisateurs ORDER BY id ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
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
                        <select name="role" class="form-select" required>
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
                                📧 Envoyer les identifiants par email à l'utilisateur
                            </label>
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
                                        <button type="button" class="btn btn-danger btn-sm delete-user-btn" style="width: 110px;" data-user-id="<?php echo $u['id']; ?>">Supprimer</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($users)): ?>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success" style="min-width: 200px;">Sauvegarder toutes les modifications</button>
                        </div>
                    <?php endif; ?>
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
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>

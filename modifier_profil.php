<?php
session_start();
include 'connexionbdd.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

// Fonction pour adapter le rôle selon le sexe
function getRoleLabel($role, $sexe) {
    if ($role === 'admin') {
        if ($sexe === 'feminin') return 'Administratrice';
        if ($sexe === 'autre') return 'Administrateur/Administratrice';
        return 'Administrateur';
    }
    if ($role === 'formateur') {
        if ($sexe === 'feminin') return 'Formatrice';
        if ($sexe === 'autre') return 'Formateur/Formatrice';
        return 'Formateur';
    }
    return ucfirst($role);
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
$feedbackType = 'info';

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sexe = sanitize_sexe($_POST['sexe'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$prenom || !$nom || !$email || !$sexe) {
            throw new Exception('Tous les champs sont requis.');
        }

        // Mise à jour des informations de base
        if ($password !== '') {
            // Si un nouveau mot de passe est fourni
            $stmt = $pdo->prepare('UPDATE utilisateurs SET prenom = :prenom, nom = :nom, email = :email, sexe = :sexe, password = SHA2(:password, 256) WHERE id = :id');
            $stmt->execute([
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
                'sexe' => $sexe,
                'password' => $password,
                'id' => $user_id,
            ]);
        } else {
            // Sans changement de mot de passe
            $stmt = $pdo->prepare('UPDATE utilisateurs SET prenom = :prenom, nom = :nom, email = :email, sexe = :sexe WHERE id = :id');
            $stmt->execute([
                'prenom' => $prenom,
                'nom' => $nom,
                'email' => $email,
                'sexe' => $sexe,
                'id' => $user_id,
            ]);
        }

        // Gestion de la photo de profil
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['photo']['tmp_name'];
            $origName = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            
            if (in_array($ext, $allowed, true)) {
                $imgDir = __DIR__ . DIRECTORY_SEPARATOR . 'pp';
                if (!is_dir($imgDir)) {
                    mkdir($imgDir, 0755, true);
                }
                
                // Supprimer les anciennes photos de cet utilisateur
                foreach (['jpg', 'jpeg', 'png'] as $oldExt) {
                    $oldFile = $imgDir . DIRECTORY_SEPARATOR . $user_id . '.' . $oldExt;
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                $targetFs = $imgDir . DIRECTORY_SEPARATOR . $user_id . '.' . $ext;
                $targetWeb = 'pp/' . $user_id . '.' . $ext;
                
                if (move_uploaded_file($tmpPath, $targetFs)) {
                    $up = $pdo->prepare('UPDATE utilisateurs SET photo = :photo WHERE id = :id');
                    $up->execute(['photo' => $targetWeb, 'id' => $user_id]);
                    $feedback = 'Profil mis à jour avec succès, photo incluse.';
                } else {
                    $feedback = 'Profil mis à jour, mais échec de l\'enregistrement de la photo.';
                }
            } else {
                $feedback = 'Profil mis à jour. Format de photo non supporté (autorisé: jpg, jpeg, png).';
            }
        } else {
            $feedback = 'Profil mis à jour avec succès.';
        }

        $feedbackType = 'success';
        
        // Mise à jour de la session
        $_SESSION['prenom'] = $prenom;
        $_SESSION['nom'] = $nom;
        
    } catch (Exception $e) {
        $feedback = 'Erreur: ' . htmlspecialchars($e->getMessage());
        $feedbackType = 'danger';
    }
}

// Récupération des données actuelles
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Modifier mon profil</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="profil.php">
                <?php if ($user['photo']): ?>
                    <img src="<?php echo htmlspecialchars($user['photo']); ?>?v=<?php echo time(); ?>" alt="Photo de profil" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <?php else: ?>
                    <img src="<?php echo getDefaultPhoto($user['sexe']); ?>" alt="Photo par défaut" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <?php endif; ?>
                <span><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Accueil</a>
                    </li>
                    <?php if ($user['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownGestion" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Gestion
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestion">
                                <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                                <li><a class="dropdown-item" href="referentiel.php">Gestion référentiel</a></li>
                            </ul>
                        </li>
                    <?php elseif ($user['role'] == 'formateur'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes formations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Stagiaires</a>
                        </li>
                    <?php elseif ($user['role'] == 'stagiaire OP'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Calendrier des séances</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Ressources</a>
                        </li>
                    <?php elseif ($user['role'] == 'stagiaire FPC'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Documents</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Ressources</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Modifier mon profil</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($feedback): ?>
                            <div class="alert alert-<?php echo $feedbackType; ?>"><?php echo htmlspecialchars($feedback); ?></div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data">
                            <div class="text-center mb-3">
                                <?php if ($user['photo']): ?>
                                    <img src="<?php echo htmlspecialchars($user['photo']); ?>?v=<?php echo time(); ?>" alt="Photo de profil" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="<?php echo getDefaultPhoto($user['sexe']); ?>" alt="Photo par défaut" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Changer la photo de profil</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg">
                                    <small class="form-text text-muted">Formats acceptés: JPG, JPEG, PNG</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="sexe" class="form-label">Sexe</label>
                                <select class="form-select" id="sexe" name="sexe" required>
                                    <option value="masculin" <?php echo $user['sexe'] === 'masculin' ? 'selected' : ''; ?>>Masculin</option>
                                    <option value="feminin" <?php echo $user['sexe'] === 'feminin' ? 'selected' : ''; ?>>Féminin</option>
                                    <option value="autre" <?php echo $user['sexe'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Numéro de login</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['numlogin']); ?>" disabled>
                                <small class="form-text text-muted">Le numéro de login ne peut pas être modifié</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rôle</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(getRoleLabel($user['role'], $user['sexe'])); ?>" disabled>
                                <small class="form-text text-muted">Le rôle ne peut pas être modifié</small>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Laisser vide pour ne pas changer">
                                <small class="form-text text-muted">Laisser vide si vous ne souhaitez pas changer votre mot de passe</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                <a href="profil.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>

</body>
</html>

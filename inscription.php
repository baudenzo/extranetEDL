<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Inscription Stagiaire</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<?php
include 'connexionbdd.php';
include 'email_functions.php';
$pdo = ConnexionBDD();

session_start();

$error = '';
$success = '';

function sanitize_role($role) {
    $allowed = ['stagiaire OP', 'stagiaire FPC'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $sexe = sanitize_sexe($_POST['sexe'] ?? '');
        $role = sanitize_role($_POST['role'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        if (!$email || !$prenom || !$nom || !$sexe || !$role || !$password) {
            throw new Exception('Tous les champs sont requis.');
        }
        
        if (strlen($password) < 4) {
            throw new Exception('Le mot de passe doit contenir au moins 4 caractères.');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Les mots de passe ne correspondent pas.');
        }
        
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette adresse email est déjà utilisée.');
        }
        
        $numlogin = genererLoginUnique($pdo);
        
        $stmt = $pdo->prepare('INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe, photo) VALUES (:email, :prenom, :nom, :numlogin, SHA2(:password, 256), :role, :sexe, :photo)');
        $stmt->execute([
            'email' => $email,
            'prenom' => $prenom,
            'nom' => $nom,
            'numlogin' => $numlogin,
            'password' => $password,
            'role' => $role,
            'sexe' => $sexe,
            'photo' => getDefaultPhoto($sexe)
        ]);
        
        $resultEmail = envoyerEmailNouveauCompte($email, $prenom . ' ' . $nom, $numlogin);
        
        if ($resultEmail['success']) {
            $success = "Votre compte a été créé avec succès ! Un email contenant votre login a été envoyé à votre adresse.";
        } else {
            $success = "Votre compte a été créé avec succès ! Cependant, l'email n'a pas pu être envoyé. Votre login est : <strong>$numlogin</strong>";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center mt-5">
                <a href="index.php"><img src="img/logo.png" alt="Logo EDL" class="logo img-fluid mb-4" style="max-width: 180px;"></a>
                <h1 class="mb-3">Inscription Stagiaire</h1>
                <?php if (!$success): ?>
                    <p class="text-muted mb-5">Créez votre compte pour accéder à la plateforme EDL</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-8 col-lg-6">
                <div class="card p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>📧 Prochaines étapes :</strong>
                            <ul class="mb-0 mt-2">
                                <li>Vérifiez votre boîte de réception</li>
                                <li>Notez votre login pour vous connecter</li>
                                <li>Cliquez sur le bouton ci-dessous pour vous connecter</li>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg w-100">Se connecter</a>
                        </div>
                        
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <small class="form-text text-muted">Votre login sera envoyé à cette adresse</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sexe" class="form-label">Sexe *</label>
                                    <select class="form-select" id="sexe" name="sexe" required>
                                        <option value="">-- Choisir --</option>
                                        <option value="masculin" <?php echo (($_POST['sexe'] ?? '') === 'masculin') ? 'selected' : ''; ?>>Masculin</option>
                                        <option value="feminin" <?php echo (($_POST['sexe'] ?? '') === 'feminin') ? 'selected' : ''; ?>>Féminin</option>
                                        <option value="autre" <?php echo (($_POST['sexe'] ?? '') === 'autre') ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Type de stagiaire *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">-- Choisir --</option>
                                        <option value="stagiaire OP" <?php echo (($_POST['role'] ?? '') === 'stagiaire OP') ? 'selected' : ''; ?>>Stagiaire OP</option>
                                        <option value="stagiaire FPC" <?php echo (($_POST['role'] ?? '') === 'stagiaire FPC') ? 'selected' : ''; ?>>Stagiaire FPC</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Minimum 4 caractères</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <strong>Information :</strong> Un login unique vous sera envoyé par mail suite à l'inscription !
                                </small>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    Créer mon compte
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-muted">Retour à la connexion</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>

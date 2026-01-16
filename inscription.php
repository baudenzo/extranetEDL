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

    <div class="waves">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" fill="#199ea3"></path>
            <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" fill="#199ea3"></path>
            <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="#199ea3"></path>
        </svg>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Réinitialisation du mot de passe</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<?php
include 'connexionbdd.php';
$pdo = ConnexionBDD();

session_start();

$step = 1; 
$error = '';
$success = '';
$user_info = null;

// vérification de l'email
if (isset($_POST['email']) && !isset($_POST['new_password'])) {
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare('SELECT id, prenom, nom, numlogin FROM utilisateurs WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $step = 2;
        $user_info = $user;
    } else {
        $error = "Aucun compte n'est associé à cette adresse email.";
    }
}

// changer le mot de passe
if (isset($_POST['new_password']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 4) {
        $error = "Le mot de passe doit contenir au moins 4 caractères.";
        $step = 2;
        // Récupérer les infos utilisateur
        $stmt = $pdo->prepare('SELECT id, prenom, nom, numlogin FROM utilisateurs WHERE id = :id');
        $stmt->execute(['id' => $user_id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
        $step = 2;
        // Récupérer les infos utilisateur
        $stmt = $pdo->prepare('SELECT id, prenom, nom, numlogin FROM utilisateurs WHERE id = :id');
        $stmt->execute(['id' => $user_id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare('UPDATE utilisateurs SET password = SHA2(:password, 256) WHERE id = :id');
        $result = $stmt->execute([
            'password' => $new_password,
            'id' => $user_id
        ]);
        
        $success = "Votre mot de passe a été modifié avec succès !";
        $step = 3;
    }
}

?>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center mt-5">
                <a href="index.php"><img src="img/logo.png" alt="Logo EDL" class="logo img-fluid mb-4" style="max-width: 180px;"></a>
                <h1 class="mb-3">Réinitialisation du mot de passe</h1>
                <?php if ($step == 1): ?>
                    <p class="text-muted mb-5">Entrez votre adresse email pour identifier votre compte</p>
                <?php elseif ($step == 2): ?>
                    <p class="text-muted mb-5">Choisissez votre nouveau mot de passe</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($step == 1): ?>
                        <form method="post" action="">
                            <div class="form-group mb-3">
                                <label for="email">Adresse email :</label>
                                <input type="email" class="form-control" id="email" name="email" required autofocus>
                                <small class="form-text text-muted">L'email associé à votre compte EDL</small>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">Continuer</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-muted">Retour à la connexion</a>
                            </div>
                        </form>
                        
                    <?php elseif ($step == 2): ?>
                        <form method="post" action="">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_info['id']); ?>">
                            <div class="form-group mb-3">
                                <label for="new_password">Nouveau mot de passe :</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required autofocus>
                                <small class="form-text text-muted">Minimum 4 caractères</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="confirm_password">Confirmer le mot de passe :</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success">Modifier le mot de passe</button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="oubli_mdp.php" class="text-muted" onclick="<?php unset($_SESSION['reset_user_id']); unset($_SESSION['reset_user_info']); ?>">Recommencer</a>
                            </div>
                        </form>
                        
                    <?php elseif ($step == 3): ?>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-primary">Se connecter</a>
                        </div>
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

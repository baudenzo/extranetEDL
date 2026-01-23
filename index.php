<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Connexion</title> 
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<?php
include 'connexionbdd.php';
$pdo = ConnexionBDD();

session_start();

$error = '';
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE numlogin = :username AND password = SHA2(:password, 256)');
    $stmt->execute(['username' => $username, 'password' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect';
    }
}
?>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center mt-5">
                <a href="index.php"><img src="img/logo.png" alt="Logo EDL" class="logo img-fluid mb-4" style="max-width: 180px;"></a>
                <h1 class="mb-5">Bienvenue sur votre espace EDL</h1>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="form-group mb-3">
                            <label for="username">Nom d'utilisateur:</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Mot de passe:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Se connecter</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="oubli_mdp.php" class="text-muted">Mot de passe oublié ?</a>
                        </div>
                        <hr class="my-4">
                        <div class="text-center">
                            <p class="mb-2 text-muted">Vous êtes stagiaire ?</p>
                            <a href="inscription.php" class="btn btn-outline-primary w-100">Créer un compte</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
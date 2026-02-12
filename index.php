<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL+ - Connexion</title> 
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <!-- Bootstrap 5.3 pour le design responsive -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Styles personnalisés de l'application -->
    <link rel="stylesheet" href="styles.css">
</head>

<?php
/**
 * PAGE DE CONNEXION - EDL+
 * 
 * Point d'entrée principal de l'application
 * Permet aux utilisateurs (stagiaires, formateurs, admins) de se connecter
 * 
 * Fonctionnalités :
 * - Authentification par nom d'utilisateur et mot de passe
 * - Redirection vers le dashboard après connexion réussie
 * - Gestion des erreurs de connexion
 * - Liens vers inscription (stagiaires) et récupération de mot de passe
 * 
 * Sécurité :
 * - Mot de passe hashé avec SHA2-256 dans la base de données
 * - Protection contre les injections SQL via requêtes préparées
 * - Session démarrée pour maintenir l'état de connexion
 */

// Connexion à la base de données
include 'connexionbdd.php';
$pdo = ConnexionBDD();

// Démarrer la session pour gérer l'authentification
session_start();

// Variable pour stocker les messages d'erreur
$error = '';

/**
 * ============================================================================
 * TRAITEMENT DU FORMULAIRE DE CONNEXION
 * ============================================================================
 * Vérifie les identifiants de l'utilisateur et initialise la session
 */
if (isset($_POST['username']) && isset($_POST['password'])) {
    // Récupérer les données du formulaire
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Rechercher l'utilisateur dans la BDD avec identifiants hashés
    // Le mot de passe est hashé en SHA2-256 pour la sécurité
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE numlogin = :username AND password = SHA2(:password, 256)');
    $stmt->execute(['username' => $username, 'password' => $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si l'utilisateur existe et les identifiants sont corrects
    if ($user) {
        // Initialiser les variables de session
        $_SESSION['user_id'] = $user['id'];           // ID de l'utilisateur
        $_SESSION['role'] = $user['role'];            // Rôle : stagiaire, formateur, admin
        $_SESSION['nom'] = $user['nom'];              // Nom de famille
        $_SESSION['prenom'] = $user['prenom'];        // Prénom
        $_SESSION['logged_in'] = true;                // Marqueur de connexion
        
        // Redirection vers le tableau de bord
        header('Location: dashboard.php');
        exit;
    } else {
        // Afficher un message d'erreur si les identifiants sont incorrects
        $error = 'Identifiant ou mot de passe incorrect';
    }
}
?>

<body>
    <!-- ========================================================================
         CONTENEUR PRINCIPAL DE LA PAGE DE CONNEXION
         Design centré et responsive avec Bootstrap
         ======================================================================== -->
    <div class="container">
        <!-- Logo et titre de bienvenue -->
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center mt-5">
                <a href="index.php"><img src="img/logo.png" alt="Logo EDL+" class="logo img-fluid mb-4" style="max-width: 180px;"></a>
                <h1 class="mb-5">Bienvenue sur votre espace EDL+</h1>
            </div>
        </div>
        
        <!-- ====================================================================
             FORMULAIRE DE CONNEXION
             Authentification par nom d'utilisateur et mot de passe
             ==================================================================== -->
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card p-4">
                    <!-- Message d'erreur si les identifiants sont incorrects -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- Formulaire de connexion (soumission en POST) -->
                    <form method="post" action="">
                        <!-- Champ nom d'utilisateur -->
                        <div class="form-group mb-3">
                            <label for="username">Nom d'utilisateur:</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <!-- Champ mot de passe -->
                        <div class="form-group mb-3">
                            <label for="password">Mot de passe:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <!-- Bouton de soumission -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Se connecter</button>
                        </div>
                        
                        <!-- Lien vers la récupération de mot de passe -->
                        <div class="text-center mt-3">
                            <a href="oubli_mdp.php" class="text-muted">Mot de passe oublié ?</a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Lien vers l'inscription pour les nouveaux stagiaires -->
                        <div class="text-center">
                            <p class="mb-2 text-muted">Vous êtes stagiaire ?</p>
                            <a href="inscription.php" class="btn btn-outline-primary w-100">Créer un compte</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Inclusion du pied de page -->
    <?php include 'footer.php'; ?>

</body>
</html>
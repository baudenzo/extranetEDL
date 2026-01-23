<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

include 'connexionbdd.php';
$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

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

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EDL</title>
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
                        <a class="nav-link active" href="dashboard.php">Accueil</a>
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
                        <li class="nav-item">
                            <a class="nav-link" href="#">Émargement</a>
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

    <div class="container mt-4">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <h1>Bienvenue sur votre espace <?php echo getRoleLabel('admin', $user['sexe']); ?>, <?php echo $_SESSION['prenom']; ?> !</h1>
        <?php elseif ($_SESSION['role'] == 'formateur'): ?>
            <h1>Bienvenue sur votre espace <?php echo getRoleLabel('formateur', $user['sexe']); ?>, <?php echo $_SESSION['prenom']; ?> !</h1>
        <?php elseif ($_SESSION['role'] == 'stagiaire OP'): ?>
            <h1>Bienvenue sur votre espace Stagiaire OP, <?php echo $_SESSION['prenom']; ?> !</h1>
        <?php elseif ($_SESSION['role'] == 'stagiaire FPC'): ?>
            <h1>Bienvenue sur votre espace Stagiaire FPC, <?php echo $_SESSION['prenom']; ?> !</h1>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Rôle non reconnu. Contactez l'administrateur/administratrice.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>

</body>
</html>


<!-- CRUD -->
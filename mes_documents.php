<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Vérifier que c'est bien un stagiaire FPC
if ($_SESSION['role'] !== 'stagiaire FPC') {
    header('Location: dashboard.php');
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

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

// Liste des documents disponibles
$documents = [
    [
        'titre' => 'Convention / Contrat',
        'description' => 'Votre convention de formation ou contrat',
        'icone' => 'CC',
        'couleur' => '#199ea3'
    ],
    [
        'titre' => 'Guide d\'animation',
        'description' => 'Guide des activités et animations',
        'icone' => 'GA',
        'couleur' => '#17a2b8'
    ],
    [
        'titre' => 'Livret d\'accueil',
        'description' => 'Informations pratiques et règlement',
        'icone' => 'LA',
        'couleur' => '#28a745'
    ],
    [
        'titre' => 'Catalogue de formations',
        'description' => 'Liste complète des formations disponibles',
        'icone' => 'CF',
        'couleur' => '#ffc107'
    ],
    [
        'titre' => 'Présentation du formateur',
        'description' => 'Profil et parcours de votre formateur',
        'icone' => 'PF',
        'couleur' => '#6f42c1'
    ],
    [
        'titre' => 'Registre d\'accessibilité',
        'description' => 'Informations sur l\'accessibilité des locaux',
        'icone' => 'RA',
        'couleur' => '#fd7e14'
    ],
    [
        'titre' => 'Présentation des locaux',
        'description' => 'Plan et présentation des espaces',
        'icone' => 'PL',
        'couleur' => '#e83e8c'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Documents - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="mes_documents.php">Mes Documents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Mes Ressources</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownDistanciel" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Distanciel
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownDistanciel">
                            <li><a class="dropdown-item" href="#">Émargement</a></li>
                            <li><a class="dropdown-item" href="#">Évaluation des acquis</a></li>
                            <li><a class="dropdown-item" href="#">Questionnaire de satisfaction</a></li>
                            <li><a class="dropdown-item" href="#">Teams</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <h2 class="mb-4">Mes Documents</h2>
        <p class="text-muted mb-4">Retrouvez ici tous les documents importants liés à votre formation.</p>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($documents as $doc): ?>
                <div class="col-md-6">
                    <div class="card document-tile h-100 shadow-sm" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" data-bs-toggle="modal" data-bs-target="#modalPlaceholder">
                        <div class="card-body text-center">
                            <h5 class="card-title" style="color: <?php echo $doc['couleur']; ?>;">
                                <?php echo htmlspecialchars($doc['titre']); ?>
                            </h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars($doc['description']); ?>
                            </p>
                            <span class="badge bg-secondary">Bientôt disponible</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Placeholder -->
    <div class="modal fade" id="modalPlaceholder" tabindex="-1" aria-labelledby="modalPlaceholderLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #199ea3; color: white;">
                    <h5 class="modal-title" id="modalPlaceholderLabel">Document en préparation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-5">
                    <h4 class="mb-3">Document bientôt disponible</h4>
                    <p class="text-muted">Ce document sera mis en ligne prochainement par l'administration.</p>
                    <p class="text-muted small mb-0">Vous serez notifié dès sa mise à disposition.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .document-tile:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
        }
        
        .document-tile {
            border-left: 4px solid #199ea3;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'footer.php'; ?>

</body>
</html>

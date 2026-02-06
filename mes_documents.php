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

// Liste des documents disponibles (avec slug pour ouverture ciblée)
$documents = [
    ['slug' => 'convention-contrat', 'titre' => 'Convention / Contrat', 'description' => 'Votre convention de formation ou contrat', 'icone' => 'CC', 'couleur' => '#199ea3'],
    ['slug' => 'guide-animation', 'titre' => 'Guide d\'animation', 'description' => 'Guide des activités et animations', 'icone' => 'GA', 'couleur' => '#17a2b8'],
    ['slug' => 'livret-accueil', 'titre' => 'Livret d\'accueil', 'description' => 'Informations pratiques et règlement', 'icone' => 'LA', 'couleur' => '#28a745'],
    ['slug' => 'catalogue-formations', 'titre' => 'Catalogue de formations', 'description' => 'Liste complète des formations disponibles', 'icone' => 'CF', 'couleur' => '#ffc107'],
    ['slug' => 'presentation-formateur', 'titre' => 'Présentation du formateur', 'description' => 'Profil et parcours de votre formateur', 'icone' => 'PF', 'couleur' => '#6f42c1'],
    ['slug' => 'registre-accessibilite', 'titre' => 'Registre d\'accessibilité', 'description' => 'Informations sur l\'accessibilité des locaux', 'icone' => 'RA', 'couleur' => '#fd7e14'],
    ['slug' => 'presentation-locaux', 'titre' => 'Présentation des locaux', 'description' => 'Plan et présentation des espaces', 'icone' => 'PL', 'couleur' => '#e83e8c']
];

// Si un paramètre 'open' est fourni, chercher le document correspondant
$open_doc = null;
if (!empty($_GET['open'])) {
    $open_slug = $_GET['open'];
    foreach ($documents as $d) {
        if ($d['slug'] === $open_slug) {
            $open_doc = $d;
            break;
        }
    }
}
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
            <a class="navbar-brand site-logo me-3 d-flex align-items-center" href="dashboard.php">
                <img src="img/logo.png" alt="EDL+ logo" style="height:40px; object-fit:contain;" />
            </a>
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
                        <a class="nav-link" href="mes_ressources.php">Ressources du formateur</a>
                    </li>
                    <?php if ((int)($user['distanciel'] ?? 0) === 1): ?>
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

    <div class="container mt-4 mb-5">
        <h2 class="mb-4">Mes Documents</h2>
        <p class="text-muted mb-4">Retrouvez ici tous les documents importants liés à votre formation.</p>
        
        <div class="row g-4 justify-content-center">
            <?php foreach ($documents as $doc): ?>
                <div class="col-md-6">
                    <div class="card document-tile h-100 shadow-sm document-card" role="button" tabindex="0"
                         data-slug="<?php echo htmlspecialchars($doc['slug']); ?>"
                         data-title="<?php echo htmlspecialchars($doc['titre']); ?>"
                         data-desc="<?php echo htmlspecialchars($doc['description']); ?>"
                         style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
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

    <!-- Modal Document Detail (s'utilise aussi pour ouverture ciblée via ?open=slug) -->
    <div class="modal fade" id="modalDocumentDetail" tabindex="-1" aria-labelledby="modalDocumentDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #199ea3; color: white;">
                    <h5 class="modal-title" id="modalDocumentDetailLabel"><?php echo $open_doc ? htmlspecialchars($open_doc['titre']) : 'Document en préparation'; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <?php if ($open_doc): ?>
                        <p class="mb-2 fw-semibold"><?php echo htmlspecialchars($open_doc['description']); ?></p>
                        <p class="text-muted small">Ce document est affiché en aperçu. Si un fichier est disponible, il sera téléchargeable depuis cette page.</p>
                        <div class="mt-3">
                            <a href="#" class="btn btn-outline-primary">Télécharger</a>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-3">Document bientôt disponible</h4>
                        <p class="text-muted">Ce document sera mis en ligne prochainement par l'administration.</p>
                        <p class="text-muted small mb-0">Vous serez notifié dès sa mise à disposition.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <?php if ($open_doc): ?>
                        <a href="mes_documents.php" class="btn btn-primary">Voir la liste</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .document-tile:hover { transform: translateY(-10px); box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important; }
        .document-tile { border-left: 4px solid #199ea3; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var cards = document.querySelectorAll('.document-card');
        var modalEl = document.getElementById('modalDocumentDetail');
        if (!modalEl) return;
        var bsModal = new bootstrap.Modal(modalEl);
        var modalTitle = modalEl.querySelector('.modal-title');
        var modalBody = modalEl.querySelector('.modal-body');

        cards.forEach(function(card) {
            function openFromCard() {
                var title = card.dataset.title || 'Document';
                var desc = card.dataset.desc || '';
                modalTitle.textContent = title;
                modalBody.innerHTML = '<p class="mb-2 fw-semibold">' + desc + '</p>' +
                    '<p class="text-muted small">Ce document est affiché en aperçu. Si un fichier est disponible, il sera téléchargeable depuis cette page.</p>' +
                    '<div class="mt-3"><a href="#" class="btn btn-outline-primary">Télécharger</a></div>';
                bsModal.show();
            }
            card.addEventListener('click', openFromCard);
            card.addEventListener('keypress', function(e){ if (e.key === 'Enter' || e.keyCode === 13) openFromCard(); });
        });
    });
    </script>
    <?php if ($open_doc): ?>
    <script>
        // Ouvrir automatiquement le modal si un document ciblé est demandé
        document.addEventListener('DOMContentLoaded', function () {
            var modal = new bootstrap.Modal(document.getElementById('modalDocumentDetail'));
            modal.show();
        });
    </script>
    <?php endif; ?>
    <?php include 'footer.php'; ?>

</body>
</html>

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

// Si l'utilisateur est formateur, préparer les données pour afficher l'espace formateur sur la page d'accueil
if ($user['role'] === 'formateur') {
    // récupérer les stagiaires liés à ce formateur
    $st = $pdo->prepare('SELECT u.id, u.prenom, u.nom, u.role, u.photo FROM utilisateurs u JOIN stagiaire_formateur sf ON u.id = sf.stagiaire_id WHERE sf.formateur_id = :fid ORDER BY u.prenom, u.nom');
    $st->execute(['fid' => $user_id]);
    $stagiaires = $st->fetchAll(PDO::FETCH_ASSOC);
    $hasFPC = false;
    $hasOP = false;
    foreach ($stagiaires as $r) {
        if (isset($r['role'])) {
            if ($r['role'] === 'stagiaire FPC') $hasFPC = true;
            if ($r['role'] === 'stagiaire OP') $hasOP = true;
        }
    }
}

// Si l'utilisateur est stagiaire, récupérer son formateur lié (si présent)
$formateur = null;
if (strpos($user['role'], 'stagiaire') === 0) {
    $fstmt = $pdo->prepare('SELECT f.id, f.prenom, f.nom, f.email, f.photo FROM utilisateurs f JOIN stagiaire_formateur sf ON f.id = sf.formateur_id WHERE sf.stagiaire_id = :sid LIMIT 1');
    $fstmt->execute(['sid' => $user_id]);
    $formateur = $fstmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .card-clickable { cursor: pointer; }
        .card-clickable:focus { outline: 2px solid #0d6efd; outline-offset: 2px; }
        .document-tile { border-left: 4px solid #199ea3; transition: transform 0.18s, box-shadow 0.18s; }
        .document-tile:hover { transform: translateY(-6px); box-shadow: 0 10px 25px rgba(0,0,0,0.12) !important; }
        .document-tile .card-title { font-size: 0.95rem; font-weight:600; }
    </style>
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
                                <li><a class="dropdown-item" href="gestion_liaisons.php">Gestion des liaisons</a></li>
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
                            <a class="nav-link" href="mes_d
                            ocuments.php">Mes Documents</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Ressources</a>
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
                                <li><a class="dropdown-item" href="#">Lien Teams</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
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

            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_stagiaires">Stagiaires liés</div>
                        <div class="card-body">
                            <?php if (empty($stagiaires)): ?>
                                <p>Aucun stagiaire lié pour le moment.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($stagiaires as $s): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($s['role']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php if ($hasFPC): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_fpc_overview">Formateur FPC — Tableaux et outils</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_checklist">
                                            <div class="card-body">
                                                <h6 class="card-title">Checklist formateurs</h6>
                                                <p class="card-text small text-muted">Outils et vérifications rapides</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_satisfaction">
                                            <div class="card-body">
                                                <h6 class="card-title">Questionnaire de satisfaction</h6>
                                                <p class="card-text small text-muted">Remplissable en ligne</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_evaluation">
                                            <div class="card-body">
                                                <h6 class="card-title">Évaluation des acquis</h6>
                                                <p class="card-text small text-muted">Remplissable en ligne</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_emargement">
                                            <div class="card-body">
                                                <h6 class="card-title">Feuille d'émargement</h6>
                                                <p class="card-text small text-muted">Émargement / signature</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasOP): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_op_overview">Formateur OP — Sessions</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_sessions">
                                            <div class="card-body">
                                                <h6 class="card-title">Liste des sessions</h6>
                                                <p class="card-text small text-muted">Sessions OP cliquables</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_dossier">
                                            <div class="card-body">
                                                <h6 class="card-title">Dossier de séance</h6>
                                                <p class="card-text small text-muted">Boîtes thématiques et documents</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$hasFPC && !$hasOP): ?>
                        <div class="card placeholder-card">
                            <div class="card-body">
                                <p>Aucune activité détectée pour ce formateur. Lorsque des stagiaires seront liés, les outils apparaîtront ici.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($_SESSION['role'] == 'stagiaire OP'): ?>
            <h1>Bienvenue sur votre espace Stagiaire OP, <?php echo $_SESSION['prenom']; ?> !</h1>

            <div class="container mt-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalOPPresentation">
                            <div class="card-header"><strong>Présentation du formateur</strong></div>
                            <div class="card-body">
                                <p>Consultez le profil et le parcours du formateur en charge de votre session.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalOPPlanning">
                            <div class="card-header"><strong>Planning</strong></div>
                            <div class="card-body">
                                <p>Accédez au planning de vos séances (le jour indiqué dans l'intitulé de la session).</p>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-3">
                        <div class="col-md-4">
                            <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalMonFormateur">
                                <div class="card-header"><strong>Mon formateur</strong></div>
                                <div class="card-body">
                                    <?php if ($formateur): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($formateur['prenom'] . ' ' . $formateur['nom']); ?></p>
                                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($formateur['email'] ?? ''); ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">Aucun formateur lié.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalOPRessources">
                            <div class="card-header"><strong>Ressources associées</strong></div>
                            <div class="card-body">
                                <p>Accès aux documents du référentiel liés à la thématique des séances réalisées.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($_SESSION['role'] == 'stagiaire FPC'): ?>
            <h1>Bienvenue sur votre espace Stagiaire FPC, <?php echo $_SESSION['prenom']; ?> !</h1>

            <?php
            // Récupérer les ressources visibles (récentes)
            try {
                $resStmt = $pdo->prepare('SELECT r.id, r.titre, r.description, r.chemin_fichier, r.uploader_id, u.prenom AS uploader_prenom, u.nom AS uploader_nom FROM ressources r LEFT JOIN utilisateurs u ON r.uploader_id = u.id WHERE r.visible = 1 ORDER BY r.id DESC LIMIT 12');
                $resStmt->execute();
                $ressources = $resStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $ressources = [];
            }
            // Documents disponibles (même configuration que dans mes_documents.php)
            $documents = [
                ['slug' => 'convention-contrat', 'titre' => 'Convention / Contrat', 'description' => 'Votre convention de formation ou contrat', 'couleur' => '#199ea3'],
                ['slug' => 'guide-animation', 'titre' => 'Guide d\'animation', 'description' => 'Guide des activités et animations', 'couleur' => '#17a2b8'],
                ['slug' => 'livret-accueil', 'titre' => 'Livret d\'accueil', 'description' => 'Informations pratiques et règlement', 'couleur' => '#28a745'],
                ['slug' => 'catalogue-formations', 'titre' => 'Catalogue de formations', 'description' => 'Liste complète des formations disponibles', 'couleur' => '#ffc107'],
                ['slug' => 'presentation-formateur', 'titre' => 'Présentation du formateur', 'description' => 'Profil et parcours de votre formateur', 'couleur' => '#6f42c1'],
                ['slug' => 'registre-accessibilite', 'titre' => 'Registre d\'accessibilité', 'description' => 'Informations sur l\'accessibilité des locaux', 'couleur' => '#fd7e14'],
                ['slug' => 'presentation-locaux', 'titre' => 'Présentation des locaux', 'description' => 'Plan et présentation des espaces', 'couleur' => '#e83e8c']
            ];
            // Mélanger et prendre 3 éléments aléatoires
            shuffle($documents);
            $rand_docs = array_slice($documents, 0, 3);
            ?>

            <div class="container mt-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalDocuments">
                            <div class="card-header">
                                <strong>Documents</strong>
                            </div>
                            <div class="card-body">
                                <p>Documents fournis pour la formation (convention/contrat, guide d'animation, livret d'accueil, catalogue de formations, présentation du formateur, registre d'accessibilité, présentation des locaux).</p>
                                <ul>
                                    <li>Convention / Contrat</li>
                                    <li>Guide d'animation</li>
                                    <li>Livret d'accueil</li>
                                    <li>Catalogue des formations</li>
                                    <li>Présentation du formateur</li>
                                    <li>Registre d'accessibilité</li>
                                    <li>Présentation des locaux</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalRessources">
                            <div class="card-header">
                                <strong>Ressources pédagogiques</strong>
                            </div>
                            <div class="card-body">
                                <p>Accès aux ressources importées par les formateurs et aux documents du référentiel en lien avec votre thématique.</p>
                                <?php if (!empty($ressources)): ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($ressources as $r): ?>
                                            <li class="mb-2">
                                                <strong><?php echo htmlspecialchars($r['titre']); ?></strong>
                                                <?php if (!empty($r['chemin_fichier'])): ?>
                                                    - <a href="<?php echo htmlspecialchars($r['chemin_fichier']); ?>" target="_blank">Télécharger / Ouvrir</a>
                                                <?php endif; ?>
                                                <br><small class="text-muted">Par <?php echo htmlspecialchars(trim(($r['uploader_prenom'] . ' ' . $r['uploader_nom'])) ?: 'Formateur'); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted">Aucune ressource disponible pour le moment.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalDistanciel">
                            <div class="card-header">
                                <strong>Distanciel</strong>
                            </div>
                            <div class="card-body">
                                <p>Si la session est en distanciel, utilisez les outils ci-dessous :</p>
                                <div class="d-grid gap-2 d-md-flex">
                                    <a class="btn btn-outline-primary" href="#">Émargement</a>
                                    <a class="btn btn-outline-primary" href="#">Évaluation des acquis</a>
                                    <a class="btn btn-outline-primary" href="#">Questionnaire de satisfaction (à chaud)</a>
                                    <a class="btn btn-outline-primary" href="#">Lien Teams</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-md-4">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalMonFormateur">
                            <div class="card-header"><strong>Mon formateur</strong></div>
                            <div class="card-body">
                                <?php if ($formateur): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($formateur['prenom'] . ' ' . $formateur['nom']); ?></p>
                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars($formateur['email'] ?? ''); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Aucun formateur lié.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modalSatisfaction">
                            <div class="card-header">
                                <strong>Satisfaction & Matériel</strong>
                            </div>
                            <div class="card-body">
                                <p><a href="#">Questionnaire de satisfaction à froid</a> (envoyé après X jours).</p>
                                <p>Matériel mis à disposition :</p>
                                <ul>
                                    <li>Ordinateur / Tablette</li>
                                    <li>Supports papier</li>
                                    <li>Projecteur</li>
                                    <li>Connexion internet</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Rôle non reconnu. Contactez l'administrateur/administratrice.</p>
            </div>
        <?php endif; ?>
    </div>

        <!-- Modals overview pour chaque rubrique -->
        <div class="modal fade" id="modalDocuments" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background-color:#199ea3;color:#fff;">
                        <h5 class="modal-title">Aperçu — Documents</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2 fw-bold">Voici quelques uns de vos dossiers disponibles</p>
                        <p class="text-muted small mb-3">Cliquez sur une vignette pour plus d'informations.</p>
                        <div class="row g-3">
                            <?php foreach ($rand_docs as $doc): ?>
                                <div class="col-6 col-md-4">
                                    <a href="mes_documents.php?open=<?php echo urlencode($doc['slug']); ?>" class="card document-tile h-100 text-decoration-none text-body">
                                        <div class="card-body text-center py-3">
                                            <h6 class="card-title" style="color:<?php echo $doc['couleur']; ?>;"><?php echo htmlspecialchars($doc['titre']); ?></h6>
                                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($doc['description']); ?></p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <a href="mes_documents.php" class="btn btn-primary">Accéder</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalRessources" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aperçu — Ressources pédagogiques</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Consultez les ressources partagées par vos formateurs et téléchargez les supports pédagogiques associés.</p>
                        <p>Les ressources récentes figurent déjà dans votre tableau de bord.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <a href="upload_ressource.php" class="btn btn-primary">Accéder</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalDistanciel" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aperçu — Distanciel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Outils disponibles lorsque la session est en distanciel : émargement, évaluation des acquis, questionnaires et lien Teams.</p>
                        <p>Si une page dédiée existe, utilisez le bouton Accéder pour y aller.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <a href="#" class="btn btn-primary">Accéder</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalSatisfaction" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aperçu — Satisfaction & Matériel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Questionnaires de satisfaction (à chaud et à froid) et liste du matériel mis à disposition pendant la formation.</p>
                        <ul>
                                <li>Ordinateur / Tablette</li>
                                <li>Supports papier</li>
                                <li>Projecteur</li>
                                <li>Connexion internet</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <a href="#" class="btn btn-primary">Accéder</a>
                    </div>
                </div>
            </div>
        </div>

                <!-- Modals pour Stagiaire OP -->
                <div class="modal fade" id="modalOPPresentation" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color:#199ea3;color:#fff;">
                                <h5 class="modal-title">Présentation du formateur</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Consultez le profil, le parcours et les informations de contact du formateur pour votre session.</p>
                                <p class="text-muted small">Si une présentation est disponible, elle sera téléchargeable depuis la page dédiée.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <a href="mes_documents.php?open=presentation-formateur" class="btn btn-primary">Accéder</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalOPPlanning" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color:#0d6efd;color:#fff;">
                                <h5 class="modal-title">Planning de la session</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Le planning indique le jour et l'heure des séances — figurant dans l'intitulé de la session.</p>
                                <p class="text-muted small">Vous pouvez consulter et télécharger votre planning complet depuis la page dédiée.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <a href="planning.php" class="btn btn-primary">Accéder</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalOPRessources" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color:#17a2b8;color:#fff;">
                                <h5 class="modal-title">Ressources associées aux séances</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Accédez aux documents du référentiel en lien avec la thématique de chaque séance.</p>
                                <p class="text-muted small">Les fichiers sont nommés selon le format : <em>Date de la séance.contenu</em>.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <a href="referentiel.php" class="btn btn-primary">Accéder</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modals pour espace formateur (FPC / OP) -->
                <div class="modal fade" id="modal_stagiaires" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Stagiaires liés</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (empty($stagiaires)): ?>
                                    <p>Aucun stagiaire lié.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($stagiaires as $s): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <div class="flex-grow-1"><?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?></div>
                                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($s['role']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_fpc_overview" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Formateur FPC — Outils</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Accès rapide aux outils FPC :</p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_checklist" data-bs-toggle="modal" data-bs-dismiss="modal">Checklist formateurs</button>
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_satisfaction" data-bs-toggle="modal" data-bs-dismiss="modal">Questionnaire de satisfaction</button>
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_evaluation" data-bs-toggle="modal" data-bs-dismiss="modal">Évaluation des acquis</button>
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_emargement" data-bs-toggle="modal" data-bs-dismiss="modal">Feuille d'émargement</button>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_op_overview" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Formateur OP — Outils</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Accès rapide aux outils OP :</p>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_sessions" data-bs-toggle="modal" data-bs-dismiss="modal">Liste des sessions</button>
                                    <button class="btn btn-outline-primary" data-bs-target="#modal_dossier" data-bs-toggle="modal" data-bs-dismiss="modal">Dossier de séance</button>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modals placeholders FPC/OP -->
                <div class="modal fade" id="modal_checklist" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Checklist formateurs</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : checklist et actions rapides pour le formateur FPC.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_satisfaction" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Questionnaire de satisfaction</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : formulaire de satisfaction (intégration future de Google Forms ou formulaire interne).</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_evaluation" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Évaluation des acquis</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : évaluation des acquis (à implémenter).</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_emargement" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Feuille d'émargement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : feuille d'émargement et options de génération (PDF / émargement en distanciel).</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_sessions" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Liste des sessions</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : liste des sessions OP (clickable). Ici on affichera le planning et liens vers dossiers de séance.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal_dossier" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Dossier de séance</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <p>Placeholder : dossier de séance avec boîtes thématiques et ressources.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal: Mon formateur (pour stagiaires) -->
                <div class="modal fade" id="modalMonFormateur" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Mon formateur</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <?php if ($formateur): ?>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($formateur['photo'] ?? 'pp/default.jpg'); ?>" alt="Photo" class="rounded-circle me-3" style="width:64px; height:64px; object-fit:cover;">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($formateur['prenom'] . ' ' . $formateur['nom']); ?></h6>
                                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($formateur['email'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p>Aucun formateur n'est lié à votre compte pour le moment.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <?php include 'footer.php'; ?>

</body>
</html>
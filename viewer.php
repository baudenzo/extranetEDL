<?php
/**
 * ===================================================================
 * VISIONNEUSE DE RESSOURCES - VERSION COMPLÈTE
 * ===================================================================
 * 
 * Page de visualisation des ressources pédagogiques avec interface
 * enrichie et informations détaillées.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. AFFICHAGE DE LA RESSOURCE :
 *    - Visualisation intégrée selon le type (PDF, audio, vidéo, image)
 *    - Métadonnées : nom du fichier, uploader, date, taille
 *    - Code référentiel associé (module et contenu)
 *    - Bouton de téléchargement
 * 
 * 2. CONTRÔLE D'ACCÈS :
 *    - Vérification que l'utilisateur est connecté
 *    - Contrôle des droits :
 *      * Formateur : peut voir ses propres ressources
 *      * Stagiaire FPC : peut voir les ressources de son formateur
 *      * Admin : accès complet
 *    - Ressource doit être visible=1
 * 
 * 3. TYPES DE FICHIERS SUPPORTÉS :
 *    - PDF : iframe avec PDF.js
 *    - Audio : lecteur audio HTML5
 *    - Vidéo : lecteur vidéo HTML5
 *    - Images : affichage direct
 *    - Autres : lien de téléchargement uniquement
 * 
 * 4. INTERFACE :
 *    - Design responsive Bootstrap
 *    - Barre de navigation complète
 *    - Messages de debug (activables)
 *    - Formulaire de modification (si formateur)
 * 
 * SÉCURITÉ :
 *    - Validation de l'ID ressource
 *    - Vérification stricte des droits d'accès
 *    - Contrôle de la relation formateur-stagiaire
 * 
 * DÉPENDANCES :
 *    - connexionbdd.php : Connexion à la base
 *    - Table ressources : Stockage des fichiers
 *    - Table stagiaire_formateur : Liaisons
 *    - Table referentiel : Codes référentiel
 * 
 * ===================================================================
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DEBUG: Début du script -->\n";

// Visionneuse de ressources
session_start();
echo "<!-- DEBUG: Session démarrée -->\n";

require_once 'connexionbdd.php';
echo "<!-- DEBUG: BDD connectée -->\n";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$pdo = ConnexionBDD();
$ressource_id = (int)($_GET['id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

// Récupérer la ressource
$stmt = $pdo->prepare('SELECT r.*, u.prenom, u.nom FROM ressources r JOIN utilisateurs u ON r.uploader_id = u.id WHERE r.id = :id AND r.visible = 1 LIMIT 1');
$stmt->execute(['id' => $ressource_id]);
$ressource = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ressource) {
    die('Ressource introuvable ou non accessible.');
}

// Vérifier que l'utilisateur a le droit de voir cette ressource
$hasAccess = false;

// Si c'est le formateur qui a uploadé
if ($ressource['uploader_id'] == $user_id) {
    $hasAccess = true;
}

// Si c'est un stagiaire du formateur
if ($_SESSION['role'] === 'stagiaire FPC') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM stagiaire_formateur WHERE stagiaire_id = :sid AND formateur_id = :fid');
    $stmt->execute(['sid' => $user_id, 'fid' => $ressource['uploader_id']]);
    if ($stmt->fetchColumn() > 0) {
        $hasAccess = true;
    }
}

// Si c'est un admin
if ($_SESSION['role'] === 'admin') {
    $hasAccess = true;
}

if (!$hasAccess) {
    die('Vous n\'avez pas accès à cette ressource.');
}

// Récupérer les infos du user pour la navbar
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' octets';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($ressource['titre']); ?> - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .viewer-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .viewer-content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        iframe, video, audio, img {
            max-width: 100%;
        }
        iframe {
            width: 100%;
            min-height: 600px;
            border: none;
        }
        video {
            max-height: 70vh;
        }
        .file-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-back {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand site-logo me-3 d-flex align-items-center" href="dashboard.php">
                <img src="img/logo.png" alt="EDL+ logo" style="height:40px; object-fit:contain;" />
            </a>
            <a class="navbar-brand d-flex align-items-center" href="profil.php">
                <img src="<?php echo !empty($current['photo']) ? htmlspecialchars($current['photo']) : 'pp/default.jpg'; ?>" alt="Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <span><?php echo htmlspecialchars(($current['prenom'] ?? $_SESSION['prenom']) . ' ' . ($current['nom'] ?? $_SESSION['nom'])); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
                    <?php if ($_SESSION['role'] === 'stagiaire FPC'): ?>
                        <li class="nav-item"><a class="nav-link" href="mes_ressources.php">Ressources</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <a href="mes_ressources.php" class="btn btn-secondary btn-back">
            ← Retour aux ressources
        </a>

        <div class="file-info">
            <h3><?php echo htmlspecialchars($ressource['titre']); ?></h3>
            <p class="mb-2">
                <strong>Déposé par :</strong> <?php echo htmlspecialchars($ressource['prenom'] . ' ' . $ressource['nom']); ?><br>
                <strong>Date :</strong> <?php echo date('d/m/Y à H:i', strtotime($ressource['date_upload'])); ?><br>
                <strong>Type :</strong> <?php echo strtoupper($ressource['type_fichier']); ?> 
                (<?php echo strtoupper($ressource['extension']); ?>)<br>
                <strong>Taille :</strong> <?php echo formatFileSize($ressource['taille_fichier']); ?>
            </p>
            <a href="<?php echo htmlspecialchars($ressource['chemin_fichier']); ?>" class="btn btn-primary btn-sm" download>
                📥 Télécharger
            </a>
        </div>

        <div class="viewer-container">
            <div class="viewer-content">
                <?php
                $chemin = htmlspecialchars($ressource['chemin_fichier']);
                $type = $ressource['type_fichier'];
                
                switch($type) {
                    case 'pdf':
                        echo '<embed src="' . $chemin . '" type="application/pdf" width="100%" height="600px" />';
                        echo '<p class="text-center mt-2"><small>Si le PDF ne s\'affiche pas, <a href="' . $chemin . '" target="_blank">cliquez ici pour l\'ouvrir</a></small></p>';
                        break;
                    
                    case 'image':
                        echo '<div class="text-center">';
                        echo '<img src="' . $chemin . '" alt="' . htmlspecialchars($ressource['titre']) . '" class="img-fluid" />';
                        echo '</div>';
                        break;
                    
                    case 'video':
                        echo '<div class="text-center">';
                        echo '<video controls class="w-100">';
                        echo '<source src="' . $chemin . '" type="video/' . htmlspecialchars($ressource['extension']) . '">';
                        echo 'Votre navigateur ne supporte pas la lecture de vidéos.';
                        echo '</video>';
                        echo '</div>';
                        break;
                    
                    case 'audio':
                        echo '<div class="text-center p-5">';
                        echo '<h4 class="mb-4">🎵 Fichier audio</h4>';
                        echo '<audio controls class="w-100">';
                        echo '<source src="' . $chemin . '" type="audio/' . htmlspecialchars($ressource['extension']) . '">';
                        echo 'Votre navigateur ne supporte pas la lecture audio.';
                        echo '</audio>';
                        echo '</div>';
                        break;
                    
                    default:
                        echo '<div class="alert alert-info text-center">';
                        echo '<h4>📎 Fichier ' . strtoupper($type) . '</h4>';
                        echo '<p>Ce type de fichier ne peut pas être visualisé directement dans le navigateur.</p>';
                        echo '<p>Veuillez le télécharger pour le consulter.</p>';
                        echo '<a href="' . $chemin . '" class="btn btn-primary" download>Télécharger le fichier</a>';
                        echo '</div>';
                        break;
                }
                ?>
            </div>
        </div>

        <?php if (!empty($ressource['description'])): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <strong>Description</strong>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($ressource['description'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

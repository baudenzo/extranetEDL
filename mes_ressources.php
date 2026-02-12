<?php
/**
 * ===================================================================
 * MES RESSOURCES - CONSULTATION DES RESSOURCES DU FORMATEUR
 * ===================================================================
 * 
 * Page permettant aux stagiaires FPC de consulter et télécharger
 * les ressources déposées par leur formateur.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. AFFICHAGE DES RESSOURCES :
 *    - Liste des ressources du formateur lié
 *    - Groupement par module du référentiel
 *    - Infobulles avec le contenu du référentiel
 *    - Icônes adaptées au type de fichier (PDF, audio, vidéo, image, autre)
 * 
 * 2. OPTIONS DE TRI :
 *    - Par date décroissante (par défaut)
 *    - Par date croissante
 *    - Par code référentiel
 *    - Par nom de fichier
 *    - Par type de fichier
 * 
 * 3. TÉLÉCHARGEMENT :
 *    - Accès direct aux fichiers pour visualisation/téléchargement
 *    - Affichage du nom original et de la taille du fichier
 * 
 * ORGANISATION :
 * - Les ressources sont groupées par module du référentiel
 * - Vue responsive (table sur desktop, cards sur mobile)
 * 
 * ACCÈS :
 * - Réservé aux stagiaires FPC uniquement
 * - Affiche uniquement les ressources visible=1 de leur formateur
 * 
 * ===================================================================
 */

// Page de consultation des ressources pour les stagiaires FPC
session_start();
require_once 'connexionbdd.php';

// Vérifier si l'utilisateur est connecté et est stagiaire FPC
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'stagiaire FPC') {
    header('Location: index.php');
    exit();
}

$pdo = ConnexionBDD();
$stagiaire_id = (int)($_SESSION['user_id'] ?? 0);

// Récupérer le formateur lié à ce stagiaire
$stmt = $pdo->prepare('SELECT f.id, f.prenom, f.nom FROM utilisateurs f JOIN stagiaire_formateur sf ON f.id = sf.formateur_id WHERE sf.stagiaire_id = :sid LIMIT 1');
$stmt->execute(['sid' => $stagiaire_id]);
$formateur = $stmt->fetch(PDO::FETCH_ASSOC);

$ressources = [];
$ressourcesParModule = [];
if ($formateur) {
    // Gérer le tri des ressources
    $tri = $_GET['tri'] ?? 'date_desc';
    $orderBy = match($tri) {
        'date_asc' => 'date_upload ASC',
        'date_desc' => 'date_upload DESC',
        'ref_asc' => 'code_referentiel ASC, date_upload DESC',
        'nom_asc' => 'nom_fichier_original ASC',
        'type_asc' => 'type_fichier ASC, date_upload DESC',
        default => 'date_upload DESC'
    };
    
    // Récupérer les ressources déposées par ce formateur avec les infos du référentiel
    $stmt = $pdo->prepare("
        SELECT r.*, ref.module, ref.contenu 
        FROM ressources r 
        LEFT JOIN referentiel ref ON r.code_referentiel = ref.code 
        WHERE r.uploader_id = :fid AND r.visible = 1 
        ORDER BY $orderBy
    ");
    $stmt->execute(['fid' => $formateur['id']]);
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Grouper les ressources par module
    foreach ($ressources as $res) {
        $module = $res['module'] ?? 'Sans référentiel';
        if (!isset($ressourcesParModule[$module])) {
            $ressourcesParModule[$module] = [];
        }
        $ressourcesParModule[$module][] = $res;
    }
}

// Récupérer les infos du stagiaire pour la navbar
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour formater la taille du fichier
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

// Fonction pour obtenir l'icône selon le type de fichier
function getFileIcon($type) {
    if (strpos($type, 'pdf') !== false) return '📄';
    if (strpos($type, 'audio') !== false) return '🔊';
    if (strpos($type, 'video') !== false) return '🎬';
    if (strpos($type, 'image') !== false) return '🖼️';
    if (strpos($type, 'word') !== false) return '📝';
    if (strpos($type, 'excel') !== false || strpos($type, 'spreadsheet') !== false) return '📊';
    if (strpos($type, 'powerpoint') !== false || strpos($type, 'presentation') !== false) return '📊';
    if (strpos($type, 'zip') !== false || strpos($type, 'rar') !== false) return '📦';
    return '📎';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ressources du formateur - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <img src="<?php echo !empty($current['photo']) ? htmlspecialchars($current['photo']) : 'pp/default.jpg'; ?>" alt="Photo" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                <span><?php echo htmlspecialchars(($current['prenom'] ?? $_SESSION['prenom']) . ' ' . ($current['nom'] ?? $_SESSION['nom'])); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="mes_documents.php">Mes Documents</a></li>
                    <li class="nav-item"><a class="nav-link active" href="mes_ressources.php">Ressources du formateur</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Ressources partagées par votre formateur</h2>
        
        <?php if ($formateur): ?>
            <div class="alert alert-info">
                <strong>Formateur :</strong> <?php echo htmlspecialchars($formateur['prenom'] . ' ' . $formateur['nom']); ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Aucun formateur n'est lié à votre compte.
            </div>
        <?php endif; ?>

        <?php if (empty($ressources)): ?>
            <div class="alert alert-secondary">
                Aucune ressource disponible pour le moment.
            </div>
        <?php else: ?>

            
            <!-- Accordéons par module -->
            <div class="accordion" id="accordeonRessources">
                <?php 
                $accordionIndex = 0;
                foreach ($ressourcesParModule as $module => $ressourcesModule): 
                    $accordionId = 'collapse' . $accordionIndex;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $accordionIndex; ?>">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $accordionId; ?>" aria-expanded="true" aria-controls="<?php echo $accordionId; ?>">
                                <strong><?php echo htmlspecialchars($module); ?></strong> 
                                <span class="badge bg-secondary ms-2"><?php echo count($ressourcesModule); ?></span>
                            </button>
                        </h2>
                        <div id="<?php echo $accordionId; ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?php echo $accordionIndex; ?>">
                            <div class="accordion-body">
                                <div class="row">
                                    <?php foreach ($ressourcesModule as $ressource): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <span style="font-size: 1.5rem;"><?php echo getFileIcon($ressource['type_fichier']); ?></span>
                                                        <?php echo htmlspecialchars($ressource['titre'] ?? $ressource['nom_fichier_original']); ?>
                                                    </h5>
                                                    <?php if (!empty($ressource['description'])): ?>
                                                        <p class="card-text">
                                                            <em>"<?php echo htmlspecialchars($ressource['description']); ?>"</em>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ressource['code_referentiel'])): ?>
                                                        <p class="card-text">
                                                            <span class="badge bg-info text-dark"><?php echo htmlspecialchars($ressource['code_referentiel']); ?></span>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p class="card-text text-muted small">
                                                        <strong>Taille :</strong> <?php echo formatFileSize($ressource['taille_fichier']); ?><br>
                                                        <strong>Déposé le :</strong> <?php echo date('d/m/Y à H:i', strtotime($ressource['date_upload'])); ?>
                                                    </p>
                                                    <button class="btn btn-primary btn-sm" onclick="ouvrirVisionneuse(<?php echo $ressource['id']; ?>, '<?php echo htmlspecialchars($ressource['titre'] ?? $ressource['nom_fichier_original'], ENT_QUOTES); ?>')">
                                                        Voir
                                                    </button>
                                                    <a href="<?php echo htmlspecialchars($ressource['chemin_fichier']); ?>" class="btn btn-outline-secondary btn-sm" download>
                                                        Télécharger
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    $accordionIndex++;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Visionneuse -->
    <div class="modal fade" id="modalVisionneuse" tabindex="-1" aria-labelledby="modalVisionneuseLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVisionneuseLabel">Aperçu du fichier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="height: 80vh; padding: 0;">
                    <iframe id="iframeVisionneuse" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function ouvrirVisionneuse(id, nom) {
            document.getElementById('modalVisionneuseLabel').textContent = nom;
            document.getElementById('iframeVisionneuse').src = 'viewer_simple.php?id=' + id;
            var modal = new bootstrap.Modal(document.getElementById('modalVisionneuse'));
            modal.show();
        }
        
        // Nettoyer l'iframe quand on ferme le modal
        document.getElementById('modalVisionneuse').addEventListener('hidden.bs.modal', function () {
            document.getElementById('iframeVisionneuse').src = '';
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>

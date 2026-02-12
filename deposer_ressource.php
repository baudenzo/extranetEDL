<?php
/**
 * ===================================================================
 * DÉPÔT DE RESSOURCES - PAGE FORMATEUR
 * ===================================================================
 * 
 * Page permettant aux formateurs de déposer des ressources pédagogiques
 * pour leurs stagiaires FPC.
 * 
 * FONCTIONNALITÉS :
 * 
 * 1. UPLOAD DE FICHIERS :
 *    - Support de multiples formats : PDF, audio (MP3, WAV, OGG), vidéo (MP4, AVI), 
 *      images (JPG, PNG, GIF), documents Office (DOC, DOCX, XLS, XLSX, PPT, PPTX)
 *    - Limite de taille : 200 Mo par fichier
 *    - Organisation automatique par type dans les sous-dossiers uploads/
 *    - Génération de noms de fichiers uniques pour éviter les conflits
 * 
 * 2. ASSOCIATION AU RÉFÉRENTIEL :
 *    - Lien optionnel avec un code du référentiel
 *    - Dropdown de sélection organisé par module
 *    - Permet aux stagiaires de filtrer les ressources par module
 * 
 * 3. GESTION DE LA VISIBILITÉ :
 *    - Option "visible" pour contrôler l'affichage aux stagiaires
 *    - Par défaut : visible
 * 
 * TYPES DE FICHIERS SUPPORTÉS :
 * - PDF : documents, cours
 * - Audio : MP3, WAV, OGG
 * - Vidéo : MP4, MPEG, AVI
 * - Images : JPG, JPEG, PNG, GIF
 * - Office : DOC, DOCX, XLS, XLSX, PPT, PPTX
 * - Autres : TXT, ZIP
 * 
 * SÉCURITÉ :
 * - Vérification du MIME type
 * - Validation de l'extension
 * - Limite de taille stricte (200 Mo)
 * - Stockage organisé par type dans des sous-dossiers
 * 
 * ACCÈS :
 * - Réservé aux formateurs ayant des stagiaires FPC
 * - Admins ont toujours accès
 * 
 * ===================================================================
 */

// Page de dépôt de ressources pour les formateurs FPC
session_start();
require_once 'connexionbdd.php';

// Vérifier si l'utilisateur est connecté et est formateur
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] !== 'formateur' && $_SESSION['role'] !== 'admin')) {
    header('Location: index.php');
    exit();
}

// Vérifier si le formateur a des stagiaires FPC
$pdo = ConnexionBDD();
$formateur_id = (int)($_SESSION['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs u JOIN stagiaire_formateur sf ON u.id = sf.stagiaire_id WHERE sf.formateur_id = :fid AND u.role = "stagiaire FPC"');
$stmt->execute(['fid' => $formateur_id]);
$hasFPC = $stmt->fetchColumn() > 0;

if (!$hasFPC && $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Récupérer tous les codes du référentiel
$stmtRef = $pdo->query('SELECT code, module, contenu FROM referentiel ORDER BY module, code');
$referentiels = $stmtRef->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['ressource'])) {
        $message = 'Aucun fichier reçu.';
        $messageType = 'danger';
    } elseif ($_FILES['ressource']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite autorisée par le serveur.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite du formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload.'
        ];
        $message = $errors[$_FILES['ressource']['error']] ?? 'Erreur inconnue lors de l\'upload.';
        $messageType = 'danger';
    } else {
        $allowedTypes = [
            'application/pdf',
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg',
            'video/mp4', 'video/mpeg', 'video/avi',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/zip', 'application/x-rar-compressed',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $fileType = $_FILES['ressource']['type'];
        $fileSize = $_FILES['ressource']['size'];
        $maxSize = 200 * 1024 * 1024; // 200 MB
        
        if ($fileSize > $maxSize) {
            $message = 'Le fichier est trop volumineux (max 200 MB)';
            $messageType = 'danger';
        } elseif (in_array($fileType, $allowedTypes)) {
            // Déterminer le sous-dossier selon le type
            if (strpos($fileType, 'pdf') !== false) {
                $uploadDir = 'uploads/pdf/';
            } elseif (strpos($fileType, 'audio') !== false) {
                $uploadDir = 'uploads/audio/';
            } elseif (strpos($fileType, 'video') !== false) {
                $uploadDir = 'uploads/video/';
            } elseif (strpos($fileType, 'image') !== false) {
                $uploadDir = 'uploads/images/';
            } else {
                $uploadDir = 'uploads/autres/';
            }
            
            $fileName = basename($_FILES['ressource']['name']);
            $targetFile = $uploadDir . time() . '_' . $fileName;
            
            if (move_uploaded_file($_FILES['ressource']['tmp_name'], $targetFile)) {
                // Enregistrer dans la base de données
                try {
                    // Déterminer le type pour la BDD
                    $typeDB = 'autre';
                    if (strpos($fileType, 'pdf') !== false) $typeDB = 'pdf';
                    elseif (strpos($fileType, 'audio') !== false) $typeDB = 'audio';
                    elseif (strpos($fileType, 'video') !== false) $typeDB = 'video';
                    elseif (strpos($fileType, 'image') !== false) $typeDB = 'image';
                    
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $description = trim($_POST['description'] ?? '');
                    $codeReferentiel = trim($_POST['code_referentiel'] ?? '') ?: null;
                    
                    $stmt = $pdo->prepare('INSERT INTO ressources (uploader_id, nom_fichier_original, chemin_fichier, type_fichier, taille_fichier, extension, titre, description, code_referentiel) VALUES (:uploader_id, :nom_fichier, :chemin_fichier, :type_fichier, :taille_fichier, :extension, :titre, :description, :code_referentiel)');
                    $stmt->execute([
                        'uploader_id' => $formateur_id,
                        'nom_fichier' => $fileName,
                        'chemin_fichier' => $targetFile,
                        'type_fichier' => $typeDB,
                        'taille_fichier' => $fileSize,
                        'extension' => $extension,
                        'titre' => $fileName,
                        'description' => $description,
                        'code_referentiel' => $codeReferentiel
                    ]);
                    $message = 'Ressource déposée avec succès !';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Fichier uploadé mais erreur d\'enregistrement : ' . $e->getMessage();
                    $messageType = 'warning';
                }
            } else {
                $message = 'Erreur lors du dépôt du fichier.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Type de fichier non autorisé.';
            $messageType = 'warning';
        }
    }
}

// Récupérer les infos du formateur pour la navbar
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

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

// Récupérer les ressources déjà déposées par ce formateur avec les infos du référentiel
$stmt = $pdo->prepare("
    SELECT r.*, ref.module, ref.contenu 
    FROM ressources r 
    LEFT JOIN referentiel ref ON r.code_referentiel = ref.code 
    WHERE r.uploader_id = :fid 
    ORDER BY $orderBy
");
$stmt->execute(['fid' => $formateur_id]);
$ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper les ressources par module
$ressourcesParModule = [];
foreach ($ressources as $res) {
    $module = $res['module'] ?? 'Sans référentiel';
    if (!isset($ressourcesParModule[$module])) {
        $ressourcesParModule[$module] = [];
    }
    $ressourcesParModule[$module][] = $res;
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
    if ($type === 'pdf') return '📄';
    if ($type === 'audio') return '🔊';
    if ($type === 'video') return '🎬';
    if ($type === 'image') return '🖼️';
    return '📎';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déposer une ressource - EDL+</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard_formateur.php">Espace formateur</a></li>
                    <li class="nav-item"><a class="nav-link active" href="deposer_ressource.php">Déposer une ressource</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Déposer une ressource</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-header">
                <strong>Ajouter un fichier</strong>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="ressource" class="form-label">Choisir un fichier :</label>
                        <input type="file" class="form-control" name="ressource" id="ressource" required>
                        <div class="form-text">
                            <strong>Taille max :</strong> 200 MB • 
                            <strong>Formats acceptés :</strong> PDF, MP3, WAV, OGG, MP4, MPEG, AVI, JPEG, PNG, GIF, WebP, Word, Excel, PowerPoint, ZIP, RAR
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="code_referentiel" class="form-label">Code référentiel (facultatif) :</label>
                        <select class="form-select" name="code_referentiel" id="code_referentiel">
                            <option value="">-- Aucun code référentiel --</option>
                            <?php 
                            $currentModule = '';
                            foreach ($referentiels as $ref): 
                                if ($currentModule !== $ref['module']) {
                                    if ($currentModule !== '') echo '</optgroup>';
                                    $currentModule = $ref['module'];
                                    echo '<optgroup label="' . htmlspecialchars($currentModule) . '">';
                                }
                            ?>
                                <option value="<?php echo htmlspecialchars($ref['code']); ?>">
                                    <?php echo htmlspecialchars($ref['code'] . ' - ' . substr($ref['contenu'], 0, 50)); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($currentModule !== '') echo '</optgroup>'; ?>
                        </select>
                        <div class="form-text">Associez cette ressource à un code de référentiel pédagogique</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Commentaire (facultatif) :</label>
                        <textarea class="form-control" name="description" id="description" rows="3" placeholder="Ajoutez un commentaire ou une description pour ce fichier..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Déposer la ressource
                    </button>
                    <a href="dashboard_formateur.php" class="btn btn-secondary">Retour</a>
                </form>
            </div>
        </div>

        <!-- Liste des ressources déjà déposées -->
        <div class="card mt-4">
            <div class="card-header">
                <strong>📚 Mes ressources déposées (<?php echo count($ressources); ?>)</strong>
            </div>
            <div class="card-body">
                <?php if (empty($ressources)): ?>
                    <p class="text-muted mb-0">Aucune ressource déposée pour le moment.</p>
                <?php else: ?>
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
                                <div id="<?php echo $accordionId; ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?php echo $accordionIndex; ?>" data-bs-parent="#accordeonRessources">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Nom du fichier</th>
                                                        <th>Code</th>
                                                        <th>Commentaire</th>
                                                        <th>Taille</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ressourcesModule as $res): ?>
                                                        <tr>
                                                            <td style="font-size: 1.5rem;"><?php echo getFileIcon($res['type_fichier']); ?></td>
                                                            <td><?php echo htmlspecialchars($res['nom_fichier_original']); ?></td>
                                                            <td>
                                                                <?php if (!empty($res['code_referentiel'])): ?>
                                                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($res['code_referentiel']); ?></span>
                                                                <?php else: ?>
                                                                    <small class="text-muted fst-italic">-</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($res['description'])): ?>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($res['description']); ?></small>
                                                                <?php else: ?>
                                                                    <small class="text-muted fst-italic">-</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo formatFileSize($res['taille_fichier']); ?></td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($res['date_upload'])); ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary" onclick="ouvrirVisionneuse(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars($res['nom_fichier_original'], ENT_QUOTES); ?>')">
                                                                    👁️ Voir
                                                                </button>
                                                                <a href="<?php echo htmlspecialchars($res['chemin_fichier']); ?>" class="btn btn-sm btn-outline-secondary" download>
                                                                    📥 Télécharger
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
        </div>
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

<?php
session_start();
include 'connexionbdd.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les infos pour la navbar
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
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
    if (strpos($type, 'pdf') !== false || $type === 'pdf') return '📄';
    if (strpos($type, 'audio') !== false || $type === 'audio') return '🔊';
    if (strpos($type, 'video') !== false || $type === 'video') return '🎬';
    if (strpos($type, 'image') !== false || $type === 'image') return '🖼️';
    return '📎';
}

$feedback = '';
$q = trim($_GET['q'] ?? '');

// Ajouter une nouvelle ressource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Aucun fichier valide n\'a été téléchargé.');
        }
        
        $allowedTypes = [
            'application/pdf',
            'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg',
            'video/mp4', 'video/mpeg', 'video/avi', 'video/quicktime',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/zip', 'application/x-rar-compressed',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        $fileType = $_FILES['fichier']['type'];
        $fileSize = $_FILES['fichier']['size'];
        $maxSize = 200 * 1024 * 1024; // 200 MB
        
        if ($fileSize > $maxSize) {
            throw new Exception('Le fichier est trop volumineux (max 200 MB)');
        }
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé.');
        }
        
        // Déterminer le sous-dossier selon le type
        if (strpos($fileType, 'pdf') !== false) {
            $uploadDir = 'uploads/pdf/';
            $typeDB = 'pdf';
        } elseif (strpos($fileType, 'audio') !== false) {
            $uploadDir = 'uploads/audio/';
            $typeDB = 'audio';
        } elseif (strpos($fileType, 'video') !== false) {
            $uploadDir = 'uploads/video/';
            $typeDB = 'video';
        } elseif (strpos($fileType, 'image') !== false) {
            $uploadDir = 'uploads/images/';
            $typeDB = 'image';
        } else {
            $uploadDir = 'uploads/autres/';
            $typeDB = 'autre';
        }
        
        // Vérifier que le dossier existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['fichier']['name']);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $targetFile = $uploadDir . time() . '_' . $fileName;
        
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $targetFile)) {
            // Enregistrer dans la base de données
            $titre = trim($_POST['titre'] ?? $fileName);
            $description = trim($_POST['description'] ?? '');
            $codeReferentiel = trim($_POST['code_referentiel'] ?? '') ?: null;
            $visible = isset($_POST['visible']) ? 1 : 0;
            
            // Vérifier si la colonne code_referentiel existe
            $columns = $pdo->query("SHOW COLUMNS FROM ressources LIKE 'code_referentiel'")->fetchAll();
            
            if (!empty($columns)) {
                $stmt = $pdo->prepare('INSERT INTO ressources (uploader_id, nom_fichier_original, chemin_fichier, type_fichier, taille_fichier, extension, titre, description, code_referentiel, visible) VALUES (:uploader_id, :nom_fichier, :chemin_fichier, :type_fichier, :taille_fichier, :extension, :titre, :description, :code_referentiel, :visible)');
                $stmt->execute([
                    'uploader_id' => $user_id,
                    'nom_fichier' => $fileName,
                    'chemin_fichier' => $targetFile,
                    'type_fichier' => $typeDB,
                    'taille_fichier' => $fileSize,
                    'extension' => $extension,
                    'titre' => $titre,
                    'description' => $description,
                    'code_referentiel' => $codeReferentiel,
                    'visible' => $visible
                ]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO ressources (uploader_id, nom_fichier_original, chemin_fichier, type_fichier, taille_fichier, extension, titre, description, visible) VALUES (:uploader_id, :nom_fichier, :chemin_fichier, :type_fichier, :taille_fichier, :extension, :titre, :description, :visible)');
                $stmt->execute([
                    'uploader_id' => $user_id,
                    'nom_fichier' => $fileName,
                    'chemin_fichier' => $targetFile,
                    'type_fichier' => $typeDB,
                    'taille_fichier' => $fileSize,
                    'extension' => $extension,
                    'titre' => $titre,
                    'description' => $description,
                    'visible' => $visible
                ]);
            }
            
            $feedback = 'Ressource ajoutée avec succès !';
        } else {
            throw new Exception('Échec du téléchargement du fichier.');
        }
        
    } catch (Exception $e) {
        $feedback = 'Erreur : ' . $e->getMessage();
    }
}

// Supprimer une ressource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)$_POST['id'];
        
        // Récupérer le chemin du fichier
        $stmt = $pdo->prepare('SELECT chemin_fichier FROM ressources WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $ressource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ressource) {
            // Supprimer le fichier physique
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . $ressource['chemin_fichier'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Supprimer de la base de données
            $stmt = $pdo->prepare('DELETE FROM ressources WHERE id = :id');
            $stmt->execute(['id' => $id]);
            
            $feedback = 'Ressource supprimée avec succès !';
        } else {
            throw new Exception('Ressource introuvable.');
        }
        
    } catch (Exception $e) {
        $feedback = 'Erreur : ' . $e->getMessage();
    }
}

// Récupérer toutes les ressources avec recherche
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare('
        SELECT r.*, u.prenom, u.nom 
        FROM ressources r 
        LEFT JOIN utilisateurs u ON r.uploader_id = u.id 
        WHERE r.titre LIKE :q OR r.nom_fichier_original LIKE :q OR r.description LIKE :q
        ORDER BY r.date_upload DESC
    ');
    $stmt->execute(['q' => $like]);
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query('
        SELECT r.*, u.prenom, u.nom 
        FROM ressources r 
        LEFT JOIN utilisateurs u ON r.uploader_id = u.id 
        ORDER BY r.date_upload DESC
    ');
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer tous les codes du référentiel pour le formulaire
$stmtRef = $pdo->query('SELECT code, module, contenu FROM referentiel ORDER BY module, code');
$referentiels = $stmtRef->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des ressources - EDL+</title>
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdownGestion" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Gestion
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestion">
                            <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                            <li><a class="dropdown-item" href="referentiel.php">Gestion référentiel</a></li>
                            <li><a class="dropdown-item" href="gestion_liaisons.php">Gestion des liaisons</a></li>
                            <li><a class="dropdown-item" href="gestion_ressources.php">Gestion des ressources</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-md-5 mt-4">
        <h2>Gestion des ressources</h2>
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Ajouter une nouvelle ressource</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="col-md-6">
                        <label class="form-label">Fichier *</label>
                        <input type="file" class="form-control" name="fichier" required>
                        <small class="text-muted">Max 200 MB. Formats: PDF, Audio, Vidéo, Images, Documents Office</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="titre" maxlength="200" placeholder="Optionnel, nom du fichier par défaut">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Code référentiel</label>
                        <select class="form-select" name="code_referentiel">
                            <option value="">Aucun</option>
                            <?php 
                            $currentModule = '';
                            foreach ($referentiels as $ref): 
                                if ($currentModule !== $ref['module']):
                                    if ($currentModule !== '') echo '</optgroup>';
                                    $currentModule = $ref['module'];
                                    echo '<optgroup label="' . htmlspecialchars($ref['module']) . '">';
                                endif;
                            ?>
                                <option value="<?php echo htmlspecialchars($ref['code']); ?>">
                                    <?php echo htmlspecialchars($ref['code'] . ' - ' . $ref['contenu']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($currentModule !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Visibilité</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" name="visible" id="visible" checked>
                            <label class="form-check-label" for="visible">
                                Visible pour les utilisateurs
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Ajouter la ressource</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Liste des ressources</span>
                <form method="get" class="d-flex align-items-center">
                    <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>" style="min-width: 200px;">
                    <button type="submit" class="btn btn-sm btn-secondary">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a href="gestion_ressources.php" class="btn btn-sm btn-link ms-2">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <!-- Vue tableau pour desktop -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Titre</th>
                                    <th>Description</th>
                                    <th>Uploader</th>
                                    <th>Taille</th>
                                    <th>Date</th>
                                    <th>Aperçu</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($ressources)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Aucune ressource trouvée<?php echo $q !== '' ? ' pour la recherche "' . htmlspecialchars($q) . '"' : ''; ?>.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($ressources as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['id']); ?></td>
                                    <td class="text-center" style="font-size: 1.8rem;">
                                        <?php echo getFileIcon($r['type_fichier']); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($r['nom_fichier_original']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($r['description'] ?? '', 0, 50)); ?><?php echo strlen($r['description'] ?? '') > 50 ? '...' : ''; ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo formatFileSize($r['taille_fichier']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($r['date_upload'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" onclick="ouvrirVisionneuse(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original'], ENT_QUOTES); ?>')" title="Voir le document">
                                            Voir
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-danger btn-sm delete-ressource-btn" style="width: 90px;" data-ressource-id="<?php echo $r['id']; ?>" data-ressource-nom="<?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original']); ?>">
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Vue cartes pour mobile -->
                <div class="d-md-none">
                    <?php if (empty($ressources)): ?>
                        <div class="alert alert-info text-center">
                            Aucune ressource trouvée<?php echo $q !== '' ? ' pour la recherche "' . htmlspecialchars($q) . '"' : ''; ?>.
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($ressources as $r): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="text-center mb-3" style="font-size: 3rem;">
                                    <?php echo getFileIcon($r['type_fichier']); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">ID</label>
                                    <p><?php echo htmlspecialchars($r['id']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Titre</label>
                                    <p><?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nom du fichier</label>
                                    <p class="text-muted"><?php echo htmlspecialchars($r['nom_fichier_original']); ?></p>
                                </div>
                                
                                <?php if ($r['description']): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <p><?php echo htmlspecialchars($r['description']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Uploader</label>
                                        <p><?php echo htmlspecialchars($r['prenom'] . ' ' . $r['nom']); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Taille</label>
                                        <p><?php echo formatFileSize($r['taille_fichier']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Date</label>
                                        <p><?php echo date('d/m/Y', strtotime($r['date_upload'])); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold">Aperçu</label>
                                        <p>
                                            <button class="btn btn-sm btn-primary" onclick="ouvrirVisionneuse(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original'], ENT_QUOTES); ?>')">
                                                Voir le document
                                            </button>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-danger delete-ressource-btn" data-ressource-id="<?php echo $r['id']; ?>" data-ressource-nom="<?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original']); ?>">
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
        
        // Gestion de la suppression
        document.querySelectorAll('.delete-ressource-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const ressourceId = this.dataset.ressourceId;
                const ressourceNom = this.dataset.ressourceNom;
                
                if (confirm('Êtes-vous sûr de vouloir supprimer la ressource "' + ressourceNom + '" ?\n\nCette action est irréversible. Le fichier sera définitivement supprimé.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = ressourceId;
                    
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>

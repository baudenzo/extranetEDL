<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Uploader une ressource</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<?php
session_start();
include 'connexionbdd.php';
include 'upload_config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'formateur') {
    header('Location: dashboard.php');
    exit;
}

$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
    try {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($titre)) {
            throw new Exception('Le titre est obligatoire.');
        }
        
        $file = $_FILES['fichier'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier.');
        }
        
        $originalFilename = $file['name'];
        $tmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        if (!isExtensionAllowed($extension)) {
            throw new Exception('Extension de fichier non autorisée : .' . $extension);
        }
        
        $fileType = getFileType($extension);
        $maxSize = getMaxSize($fileType);
        
        if ($fileSize > $maxSize) {
            throw new Exception('Fichier trop volumineux. Maximum autorisé : ' . formatFileSize($maxSize));
        }
        
        $mimeType = mime_content_type($tmpPath);
        if (!isMimeTypeAllowed($mimeType, $fileType)) {
            throw new Exception('Type de fichier non autorisé.');
        }
        
        $secureFilename = generateSecureFilename($originalFilename);
        $uploadDir = UPLOADS_DIR . DIRECTORY_SEPARATOR . $fileType;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $secureFilename;
        $dbPath = 'uploads/' . $fileType . '/' . $secureFilename;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new Exception('Impossible de déplacer le fichier uploadé.');
        }
        
        $stmt = $pdo->prepare('INSERT INTO ressources (titre, description, type_fichier, chemin_fichier, nom_fichier_original, taille_fichier, extension, uploader_id, visible) VALUES (:titre, :description, :type_fichier, :chemin_fichier, :nom_original, :taille, :extension, :uploader_id, 1)');
        $stmt->execute([
            'titre' => $titre,
            'description' => $description,
            'type_fichier' => $fileType,
            'chemin_fichier' => $dbPath,
            'nom_original' => $originalFilename,
            'taille' => $fileSize,
            'extension' => $extension,
            'uploader_id' => $user_id
        ]);
        
        $success = 'Ressource uploadée avec succès !';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Accueil</a></li>
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
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active" href="upload_ressource.php">Uploader une ressource</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-5">
                    <div class="card-header">
                        <h2>Uploader une ressource</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <strong>Erreur :</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <strong>Succès :</strong> <?php echo htmlspecialchars($success); ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="upload_ressource.php" class="btn btn-primary">Uploader une autre ressource</a>
                            </div>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <label for="titre" class="form-label">Titre de la ressource *</label>
                                    <input type="text" class="form-control" id="titre" name="titre" required maxlength="200" value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
                                    <small class="text-muted">Maximum 200 caractères</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Décrivez brièvement le contenu de cette ressource</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="fichier" class="form-label">Fichier *</label>
                                    <input type="file" class="form-control" id="fichier" name="fichier" required>
                                    <small class="text-muted">
                                        <strong>Formats autorisés :</strong><br>
                                        • Audio : mp3, wav, ogg, m4a, flac (max <?php echo formatFileSize(MAX_SIZE_AUDIO); ?>)<br>
                                        • Vidéo : mp4, avi, mov, wmv, flv, mkv, webm (max <?php echo formatFileSize(MAX_SIZE_VIDEO); ?>)<br>
                                        • PDF : pdf (max <?php echo formatFileSize(MAX_SIZE_PDF); ?>)<br>
                                        • Images : jpg, jpeg, png, gif, webp, svg (max <?php echo formatFileSize(MAX_SIZE_IMAGE); ?>)<br>
                                        • Autres : doc, docx, xls, xlsx, ppt, pptx, txt, zip, rar (max <?php echo formatFileSize(MAX_SIZE_AUTRE); ?>)
                                    </small>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Uploader la ressource
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('fichier').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size;
                const fileName = file.name;
                const extension = fileName.split('.').pop().toLowerCase();
                
                console.log('Fichier sélectionné:', fileName);
                console.log('Taille:', (fileSize / 1024 / 1024).toFixed(2) + ' MB');
                console.log('Extension:', extension);
            }
        });
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>

<?php
/**
 * GESTION DES RESSOURCES - Administration EDL+
 * 
 * Ce fichier permet aux administrateurs de gérer toutes les ressources pédagogiques de la plateforme.
 * Fonctionnalités principales :
 * - Ajouter de nouvelles ressources (PDF, audio, vidéo, images, documents Office)
 * - Visualiser toutes les ressources avec leurs métadonnées
 * - Rechercher des ressources par titre, nom de fichier ou description
 * - Supprimer des ressources (fichier + base de données)
 * - Associer les ressources à des codes du référentiel de compétences
 * - Gérer la visibilité des ressources pour les utilisateurs
 * 
 * IMPORTANT : Seuls les administrateurs ont accès à cette page.
 * Les fichiers sont organisés par type dans uploads/ (pdf, audio, video, images, autres)
 * Taille maximum par fichier : 200 MB
 */

session_start();
include 'connexionbdd.php';

// SÉCURITÉ : Vérifier que l'utilisateur est bien connecté
// Si l'utilisateur n'est pas connecté, le rediriger vers la page de connexion
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// SÉCURITÉ : Vérifier que l'utilisateur a le rôle d'administrateur
// Seuls les admins peuvent gérer les ressources
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Connexion à la base de données
$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur courant (admin)
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les infos affichées dans la barre de navigation (prénom, nom, photo)
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

/**
 * FONCTION UTILITAIRE : Obtenir la photo par défaut selon le sexe
 * (Cette fonction n'est pas utilisée dans cette page mais pourrait servir pour l'affichage des uploaders)
 * 
 * @param string $sexe - Le sexe de l'utilisateur ('feminin', 'masculin', ou autre)
 * @return string - Le chemin vers la photo par défaut
 */
function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

/**
 * FONCTION UTILITAIRE : Formater la taille d'un fichier en octets
 * Convertit les octets en une unité lisible (GB, MB, KB ou octets)
 * 
 * @param int $bytes - Taille du fichier en octets
 * @return string - Taille formatée avec l'unité (ex: "2.50 MB")
 */
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

/**
 * FONCTION UTILITAIRE : Obtenir l'icône emoji selon le type de fichier
 * Retourne un emoji représentant visuellement le type de fichier
 * 
 * @param string $type - Type de fichier (pdf, audio, video, image, autre)
 * @return string - Emoji correspondant au type
 */
function getFileIcon($type) {
    if (strpos($type, 'pdf') !== false || $type === 'pdf') return '📄';
    if (strpos($type, 'audio') !== false || $type === 'audio') return '🔊';
    if (strpos($type, 'video') !== false || $type === 'video') return '🎬';
    if (strpos($type, 'image') !== false || $type === 'image') return '🖼️';
    return '📎';
}

// Variable pour stocker les messages de feedback (succès ou erreur)
$feedback = '';

// Récupérer le terme de recherche depuis l'URL si présent
$q = trim($_GET['q'] ?? '');

/**
 * ============================================================================
 * TRAITEMENT POST : AJOUTER UNE NOUVELLE RESSOURCE
 * ============================================================================
 * Cette section gère l'upload d'un nouveau fichier et son enregistrement en BDD
 * Étapes :
 * 1. Vérifier que le fichier est valide
 * 2. Vérifier le type MIME et la taille (max 200 MB)
 * 3. Déterminer le sous-dossier de destination selon le type
 * 4. Déplacer le fichier uploadé vers le bon dossier
 * 5. Insérer les métadonnées dans la table 'ressources'
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        // ÉTAPE 1 : Vérifier qu'un fichier a bien été uploadé sans erreur
        if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Aucun fichier valide n\'a été téléchargé.');
        }
        
        // ÉTAPE 2 : Liste des types MIME autorisés pour la sécurité
        // Inclut : PDF, Audio (MP3, WAV, OGG), Vidéo (MP4, AVI, MOV), Images (JPEG, PNG, GIF, WEBP),
        // Archives (ZIP, RAR), Documents Office (PowerPoint, Word, Excel)
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
        
        // Récupérer les propriétés du fichier uploadé
        $fileType = $_FILES['fichier']['type'];
        $fileSize = $_FILES['fichier']['size'];
        $maxSize = 200 * 1024 * 1024; // 200 MB en octets
        
        // VALIDATION : Vérifier que le fichier ne dépasse pas la taille maximale
        if ($fileSize > $maxSize) {
            throw new Exception('Le fichier est trop volumineux (max 200 MB)');
        }
        
        // VALIDATION : Vérifier que le type MIME du fichier est autorisé
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé.');
        }
        
        // ÉTAPE 3 : Déterminer le sous-dossier de destination selon le type de fichier
        // Organisation des fichiers : uploads/pdf/, uploads/audio/, uploads/video/, uploads/images/, uploads/autres/
        // $typeDB sera stocké dans la base de données pour faciliter les recherches par type
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
        
        // Créer le dossier de destination s'il n'existe pas
        // 0777 = permissions complètes, true = créer récursivement les dossiers parents
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Générer un nom de fichier unique pour éviter les conflits
        // Format : timestamp_nomoriginal.ext (ex: 1642357890_cours.pdf)
        $fileName = basename($_FILES['fichier']['name']);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $targetFile = $uploadDir . time() . '_' . $fileName;
        
        // ÉTAPE 4 : Déplacer le fichier temporaire vers sa destination finale
        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $targetFile)) {
            // ÉTAPE 5 : Enregistrer les métadonnées dans la base de données
            // Récupérer les données du formulaire
            $titre = trim($_POST['titre'] ?? $fileName); // Si pas de titre, utiliser le nom du fichier
            $description = trim($_POST['description'] ?? '');
            $codeReferentiel = trim($_POST['code_referentiel'] ?? '') ?: null; // Code du référentiel de compétences (optionnel)
            $visible = isset($_POST['visible']) ? 1 : 0; // Checkbox pour la visibilité
            
            // COMPATIBILITÉ : Vérifier si la colonne code_referentiel existe dans la table
            // (Cette colonne peut avoir été ajoutée après la création initiale de la table)
            $columns = $pdo->query("SHOW COLUMNS FROM ressources LIKE 'code_referentiel'")->fetchAll();
            
            // Si la colonne code_referentiel existe, l'inclure dans l'INSERT
            if (!empty($columns)) {
                $stmt = $pdo->prepare('INSERT INTO ressources (uploader_id, nom_fichier_original, chemin_fichier, type_fichier, taille_fichier, extension, titre, description, code_referentiel, visible) VALUES (:uploader_id, :nom_fichier, :chemin_fichier, :type_fichier, :taille_fichier, :extension, :titre, :description, :code_referentiel, :visible)');
                $stmt->execute([
                    'uploader_id' => $user_id,           // ID de l'admin qui ajoute la ressource
                    'nom_fichier' => $fileName,           // Nom du fichier original
                    'chemin_fichier' => $targetFile,      // Chemin complet vers le fichier (ex: uploads/pdf/123456_cours.pdf)
                    'type_fichier' => $typeDB,            // Type : pdf, audio, video, image, autre
                    'taille_fichier' => $fileSize,        // Taille en octets
                    'extension' => $extension,            // Extension du fichier (pdf, mp3, jpg, etc.)
                    'titre' => $titre,                    // Titre personnalisé ou nom du fichier
                    'description' => $description,        // Description de la ressource
                    'code_referentiel' => $codeReferentiel, // Code du référentiel de compétences
                    'visible' => $visible                 // 1 = visible pour les utilisateurs, 0 = masqué
                ]);
            } else {
                // Sinon, utiliser l'ancien format sans code_referentiel (rétrocompatibilité)
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

/**
 * ============================================================================
 * TRAITEMENT POST : SUPPRIMER UNE RESSOURCE
 * ============================================================================
 * Cette section gère la suppression d'une ressource (fichier + base de données)
 * ATTENTION : Cette action est irréversible !
 * Étapes :
 * 1. Récupérer l'ID de la ressource à supprimer
 * 2. Chercher le chemin du fichier dans la BDD
 * 3. Supprimer le fichier physique du serveur
 * 4. Supprimer l'entrée dans la base de données
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        // Récupérer l'ID de la ressource à supprimer (convertir en entier pour la sécurité)
        $id = (int)$_POST['id'];
        
        // ÉTAPE 1 : Récupérer les informations de la ressource depuis la BDD
        $stmt = $pdo->prepare('SELECT chemin_fichier FROM ressources WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $ressource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ressource) {
            // ÉTAPE 2 : Supprimer le fichier physique du serveur
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . $ressource['chemin_fichier'];
            if (file_exists($filePath)) {
                unlink($filePath); // Suppression définitive du fichier
            }
            
            // ÉTAPE 3 : Supprimer l'entrée dans la base de données
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

/**
 * ============================================================================
 * RÉCUPÉRATION DES RESSOURCES POUR L'AFFICHAGE
 * ============================================================================
 * Récupère toutes les ressources avec les informations de leur uploader
 * Si un terme de recherche est présent, filtre les résultats
 */

// RECHERCHE : Si l'utilisateur a saisi un terme de recherche
if ($q !== '') {
    $like = '%' . $q . '%'; // Préparation du pattern pour LIKE (recherche partielle)
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
    // Sinon, récupérer toutes les ressources triées par date (les plus récentes en premier)
    $stmt = $pdo->query('
        SELECT r.*, u.prenom, u.nom 
        FROM ressources r 
        LEFT JOIN utilisateurs u ON r.uploader_id = u.id 
        ORDER BY r.date_upload DESC
    ');
    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer tous les codes du référentiel de compétences pour le select du formulaire
// Triés par module puis par code pour faciliter la navigation
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
    <!-- Bootstrap 5.3 pour le design responsive -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Styles personnalisés de l'application -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- ========================================================================
         BARRE DE NAVIGATION
         Navigation principale avec logo, profil utilisateur et menu
         ======================================================================== -->
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

    <!-- ========================================================================
         CONTENU PRINCIPAL
         ======================================================================== -->
    <div class="container-fluid px-3 px-md-5 mt-4">
        <h2>Gestion des ressources</h2>
        
        <!-- Message de feedback (succès ou erreur) après une action -->
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <!-- ====================================================================
             FORMULAIRE D'AJOUT D'UNE NOUVELLE RESSOURCE
             Permet à l'admin d'uploader un fichier avec ses métadonnées
             ==================================================================== -->
        <div class="card mb-4">
            <div class="card-header">Ajouter une nouvelle ressource</div>
            <div class="card-body">
                <!-- enctype="multipart/form-data" est OBLIGATOIRE pour l'upload de fichiers -->
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <!-- Champ caché pour identifier l'action à effectuer (create) -->
                    <input type="hidden" name="action" value="create">
                    
                    <!-- CHAMP 1 : Sélection du fichier (obligatoire) -->
                    <div class="col-md-6">
                        <label class="form-label">Fichier *</label>
                        <input type="file" class="form-control" name="fichier" required>
                        <small class="text-muted">Max 200 MB. Formats: PDF, Audio, Vidéo, Images, Documents Office</small>
                    </div>
                    
                    <!-- CHAMP 2 : Titre personnalisé (optionnel) -->
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="titre" maxlength="200" placeholder="Optionnel, nom du fichier par défaut">
                    </div>
                    
                    <!-- CHAMP 3 : Description de la ressource (optionnel) -->
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <!-- CHAMP 4 : Association à un code du référentiel de compétences (optionnel) -->
                    <div class="col-md-6">
                        <label class="form-label">Code référentiel</label>
                        <select class="form-select" name="code_referentiel">
                            <option value="">Aucun</option>
                            <?php 
                            // Générer les options groupées par module pour une meilleure navigation
                            $currentModule = '';
                            foreach ($referentiels as $ref): 
                                // Créer un nouveau groupe (optgroup) quand on change de module
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
                    
                    <!-- CHAMP 5 : Visibilité de la ressource pour les utilisateurs -->
                    <div class="col-md-6">
                        <label class="form-label">Visibilité</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" name="visible" id="visible" checked>
                            <label class="form-check-label" for="visible">
                                Visible pour les utilisateurs (coché par défaut)
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Ajouter la ressource</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ====================================================================
             LISTE DES RESSOURCES EXISTANTES
             Affichage en tableau (desktop) et en cartes (mobile)
             ==================================================================== -->
        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Liste des ressources</span>
                <!-- Formulaire de recherche (méthode GET pour conserver le terme dans l'URL) -->
                <form method="get" class="d-flex align-items-center">
                    <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Rechercher..." value="<?php echo htmlspecialchars($q); ?>" style="min-width: 200px;">
                    <button type="submit" class="btn btn-sm btn-secondary">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a href="gestion_ressources.php" class="btn btn-sm btn-link ms-2">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <!-- ================================================================
                     VUE TABLEAU (visible uniquement sur écrans moyens et grands)
                     d-none d-md-block = caché sur mobile, visible sur desktop
                     ================================================================ -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>          <!-- Icône emoji selon le type de fichier -->
                                    <th>Titre</th>         <!-- Titre + nom du fichier original -->
                                    <th>Description</th>   <!-- Tronquée à 50 caractères -->
                                    <th>Uploader</th>      <!-- Prénom + Nom de la personne qui a ajouté -->
                                    <th>Taille</th>        <!-- Formatée (MB, KB, etc.) -->
                                    <th>Date</th>          <!-- Date d'upload -->
                                    <th>Aperçu</th>        <!-- Bouton pour voir le fichier -->
                                    <th>Actions</th>       <!-- Bouton de suppression -->
                                </tr>
                            </thead>
                            <tbody>
                            <!-- Message si aucune ressource n'est trouvée -->
                            <?php if (empty($ressources)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Aucune ressource trouvée<?php echo $q !== '' ? ' pour la recherche "' . htmlspecialchars($q) . '"' : ''; ?>.</td>
                                </tr>
                            <?php endif; ?>
                            <!-- Boucle sur toutes les ressources pour afficher une ligne par ressource -->
                            <?php foreach ($ressources as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['id']); ?></td>
                                    <td class="text-center" style="font-size: 1.8rem;">
                                        <?php echo getFileIcon($r['type_fichier']); ?> <!-- Emoji : 📄 🔊 🎬 🖼️ 📎 -->
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
                                        <!-- Bouton pour ouvrir la visionneuse en modal -->
                                        <button class="btn btn-sm btn-primary" onclick="ouvrirVisionneuse(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['titre'] ?? $r['nom_fichier_original'], ENT_QUOTES); ?>')" title="Voir le document">
                                            Voir
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <!-- Bouton de suppression avec attributs data-* pour le JavaScript -->
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
                
                <!-- ================================================================
                     VUE CARTES (visible uniquement sur mobile)
                     d-md-none = visible sur mobile, caché sur desktop
                     Chaque ressource est affichée dans une carte individuelle
                     ================================================================ -->
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

    <!-- ========================================================================
         MODAL VISIONNEUSE
         Affiche le contenu du fichier dans une iframe Bootstrap
         Le fichier est chargé via viewer_simple.php?id=X
         ======================================================================== -->
    <div class="modal fade" id="modalVisionneuse" tabindex="-1" aria-labelledby="modalVisionneuseLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVisionneuseLabel">Aperçu du fichier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="height: 80vh; padding: 0;">
                    <!-- L'iframe charge dynamiquement le contenu via viewer_simple.php -->
                    <iframe id="iframeVisionneuse" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS pour les fonctionnalités interactives (dropdown, modal, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ========================================================================
         SCRIPTS JAVASCRIPT
         ======================================================================== -->
    <script>
        /**
         * FONCTION : Ouvrir la visionneuse en modal
         * Charge le fichier dans une iframe et affiche le modal Bootstrap
         * 
         * @param {number} id - ID de la ressource à afficher
         * @param {string} nom - Nom de la ressource (affiché dans le titre du modal)
         */
        function ouvrirVisionneuse(id, nom) {
            // Mettre à jour le titre du modal avec le nom de la ressource
            document.getElementById('modalVisionneuseLabel').textContent = nom;
            
            // Charger le fichier dans l'iframe via viewer_simple.php
            document.getElementById('iframeVisionneuse').src = 'viewer_simple.php?id=' + id;
            
            // Afficher le modal
            var modal = new bootstrap.Modal(document.getElementById('modalVisionneuse'));
            modal.show();
        }
        
        /**
         * ÉVÉNEMENT : Nettoyage de l'iframe à la fermeture du modal
         * Important pour libérer les ressources et arrêter la lecture des médias
         */
        document.getElementById('modalVisionneuse').addEventListener('hidden.bs.modal', function () {
            // Vider la source de l'iframe pour arrêter le chargement
            document.getElementById('iframeVisionneuse').src = '';
        });
        
        /**
         * GESTION DE LA SUPPRESSION DES RESSOURCES
         * Attache un écouteur d'événement à tous les boutons de suppression
         * Affiche une confirmation avant de soumettre le formulaire
         */
        document.querySelectorAll('.delete-ressource-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Récupérer les données stockées dans les attributs data-*
                const ressourceId = this.dataset.ressourceId;
                const ressourceNom = this.dataset.ressourceNom;
                
                // Demander confirmation à l'utilisateur (action irréversible !)
                if (confirm('Êtes-vous sûr de vouloir supprimer la ressource "' + ressourceNom + '" ?\n\nCette action est irréversible. Le fichier sera définitivement supprimé.')) {
                    // Créer un formulaire invisible pour soumettre la suppression en POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    // Ajouter le champ caché 'action' avec la valeur 'delete'
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    
                    // Ajouter le champ caché 'id' avec l'ID de la ressource
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = ressourceId;
                    
                    // Ajouter les champs au formulaire
                    form.appendChild(actionInput);
                    form.appendChild(idInput);
                    
                    // Ajouter le formulaire à la page et le soumettre
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>

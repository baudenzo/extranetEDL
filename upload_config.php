<?php
// Configuration pour l'upload de fichiers
// EDL - École des Langues Grand Calais

// Taille maximale par type de fichier (en octets)
define('MAX_SIZE_AUDIO', 50 * 1024 * 1024);    // 50 MB
define('MAX_SIZE_VIDEO', 200 * 1024 * 1024);   // 200 MB
define('MAX_SIZE_PDF', 20 * 1024 * 1024);      // 20 MB
define('MAX_SIZE_IMAGE', 10 * 1024 * 1024);    // 10 MB
define('MAX_SIZE_AUTRE', 50 * 1024 * 1024);    // 50 MB

// Extensions autorisées par type
$ALLOWED_EXTENSIONS = [
    'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'flac'],
    'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
    'pdf' => ['pdf'],
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'autre' => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar']
];

// MIME types autorisés (sécurité supplémentaire)
$ALLOWED_MIME_TYPES = [
    'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/flac'],
    'video' => ['video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/x-ms-wmv', 'video/x-flv', 'video/x-matroska', 'video/webm'],
    'pdf' => ['application/pdf'],
    'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
    'autre' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain', 'application/zip', 'application/x-rar-compressed']
];

// Chemin du dossier uploads
define('UPLOADS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads');

/**
 * Détermine le type de fichier en fonction de son extension
 */
function getFileType($extension) {
    global $ALLOWED_EXTENSIONS;
    
    $extension = strtolower($extension);
    
    foreach ($ALLOWED_EXTENSIONS as $type => $extensions) {
        if (in_array($extension, $extensions)) {
            return $type;
        }
    }
    
    return 'autre';
}

/**
 * Vérifie si l'extension est autorisée
 */
function isExtensionAllowed($extension) {
    global $ALLOWED_EXTENSIONS;
    
    $extension = strtolower($extension);
    
    foreach ($ALLOWED_EXTENSIONS as $extensions) {
        if (in_array($extension, $extensions)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Vérifie si le MIME type est autorisé pour le type de fichier
 */
function isMimeTypeAllowed($mimeType, $fileType) {
    global $ALLOWED_MIME_TYPES;
    
    if (!isset($ALLOWED_MIME_TYPES[$fileType])) {
        return false;
    }
    
    return in_array($mimeType, $ALLOWED_MIME_TYPES[$fileType]);
}

/**
 * Obtient la taille maximale autorisée pour un type de fichier
 */
function getMaxSize($fileType) {
    switch ($fileType) {
        case 'audio':
            return MAX_SIZE_AUDIO;
        case 'video':
            return MAX_SIZE_VIDEO;
        case 'pdf':
            return MAX_SIZE_PDF;
        case 'image':
            return MAX_SIZE_IMAGE;
        default:
            return MAX_SIZE_AUTRE;
    }
}

/**
 * Formate une taille en octets vers un format lisible
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
 * Génère un nom de fichier unique et sécurisé
 */
function generateSecureFilename($originalFilename) {
    $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    return $timestamp . '_' . $random . '.' . $extension;
}
?>

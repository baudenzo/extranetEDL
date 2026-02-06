<?php
session_start();
require_once 'connexionbdd.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Non connecté');
}

$pdo = ConnexionBDD();
$ressource_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ressources WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ressource_id]);
$ressource = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ressource) {
    die('Ressource introuvable');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($ressource['titre']); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        embed, iframe, img, video, audio {
            width: 100%;
            height: 100vh;
            border: none;
        }
        img {
            object-fit: contain;
            background: #f0f0f0;
        }
        .download-msg {
            padding: 40px;
            text-align: center;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <?php
    $chemin = htmlspecialchars($ressource['chemin_fichier']);
    $type = $ressource['type_fichier'];
    
    if ($type === 'pdf') {
        echo '<embed src="' . $chemin . '" type="application/pdf">';
    } elseif ($type === 'image') {
        echo '<img src="' . $chemin . '" alt="' . htmlspecialchars($ressource['nom_fichier_original']) . '">';
    } elseif ($type === 'video') {
        echo '<video controls><source src="' . $chemin . '"></video>';
    } elseif ($type === 'audio') {
        echo '<audio controls style="width: 100%; height: auto; margin-top: 50px;"><source src="' . $chemin . '"></audio>';
    } else {
        echo '<div class="download-msg">';
        echo '<h3>' . htmlspecialchars($ressource['nom_fichier_original']) . '</h3>';
        echo '<p>Ce type de fichier ne peut pas être visualisé directement.</p>';
        echo '<a href="' . $chemin . '" download class="btn btn-primary">Télécharger le fichier</a>';
        echo '</div>';
    }
    ?>
</body>
</html>

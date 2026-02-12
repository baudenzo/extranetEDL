<?php
/**
 * ===================================================================
 * PAGE D'INSCRIPTION - CRÉATION DE COMPTE STAGIAIRE
 * ===================================================================
 * 
 * Permet à un nouveau stagiaire de créer son compte dans l'application EDL+.
 * 
 * FONCTIONNALITÉS :
 * - Formulaire d'inscription avec validation des données
 * - Types de stagiaire : Stagiaire OP (Objectif Professionnel) ou FPC (Formation Professionnelle Continue)
 * - Option "distanciel" disponible uniquement pour les stagiaires FPC
 * - Validation de l'unicité de l'email
 * - Génération automatique d'un login unique
 * - Photo de profil par défaut selon le sexe
 * - Hachage sécurisé du mot de passe (SHA2-256)
 * - Envoi d'un email avec les identifiants de connexion
 * 
 * SÉCURITÉ :
 * - Validation et sanitisation des données d'entrée
 * - Mot de passe haché avant stockage en base (minimum 4 caractères)
 * - Protection contre les doublons d'email
 * - Vérification de la correspondance des mots de passe
 * 
 * DÉPENDANCES :
 * - connexionbdd.php : Connexion PDO à la base de données
 * - email_functions.php : Fonction envoyerEmailNouveauCompte() et genererLoginUnique()
 * - footer.php : Pied de page commun
 * 
 * ===================================================================
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL+ - Inscription Stagiaire</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<?php
// ===================================================================
// IMPORTS ET INITIALISATION
// ===================================================================

include 'connexionbdd.php';
include 'email_functions.php';
$pdo = ConnexionBDD();

session_start();

// Variables pour afficher les messages d'erreur ou de succès
$error = '';
$success = '';

/**
 * ====================================
 * FONCTION : sanitize_role
 * ====================================
 * Valide et normalise le rôle de l'utilisateur lors de l'inscription.
 * 
 * Seuls deux types de stagiaires sont autorisés :
 * - "stagiaire OP" : Objectif Professionnel
 * - "stagiaire FPC" : Formation Professionnelle Continue
 * 
 * @param string $role Le rôle à valider
 * @return string|null Le rôle validé ou null si invalide
 */
function sanitize_role($role) {
    $allowed = ['stagiaire OP', 'stagiaire FPC'];
    return in_array($role, $allowed, true) ? $role : null;
}

/**
 * ====================================
 * FONCTION : sanitize_sexe
 * ====================================
 * Valide et normalise le sexe de l'utilisateur.
 * Utilisé pour déterminer la photo de profil par défaut.
 * 
 * Valeurs autorisées :
 * - "masculin"
 * - "feminin"
 * - "autre"
 * 
 * @param string $sexe Le sexe à valider
 * @return string|null Le sexe validé ou null si invalide
 */
function sanitize_sexe($sexe) {
    $allowed = ['masculin', 'feminin', 'autre'];
    return in_array($sexe, $allowed, true) ? $sexe : null;
}

/**
 * ====================================
 * FONCTION : getDefaultPhoto
 * ====================================
 * Retourne le chemin complet vers la photo de profil par défaut
 * selon le sexe de l'utilisateur.
 * 
 * Les fichiers doivent être présents dans le dossier pp/
 * 
 * @param string $sexe Le sexe de l'utilisateur (masculin/feminin/autre)
 * @return string Le chemin vers la photo par défaut
 */
function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

// ===================================================================
// TRAITEMENT DU FORMULAIRE D'INSCRIPTION
// ===================================================================
// Ce bloc s'exécute uniquement lorsque le formulaire est soumis (méthode POST)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Récupération et nettoyage des données du formulaire ---
        $email = trim($_POST['email'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $sexe = sanitize_sexe($_POST['sexe'] ?? '');  // Validation du sexe
        $role = sanitize_role($_POST['role'] ?? '');  // Validation du rôle
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // --- Vérification que tous les champs obligatoires sont remplis ---
        if (!$email || !$prenom || !$nom || !$sexe || !$role || !$password) {
            throw new Exception('Tous les champs sont requis.');
        }
        // --- Validation du mot de passe : minimum 4 caractères ---
        if (strlen($password) < 4) {
            throw new Exception('Le mot de passe doit contenir au moins 4 caractères.');
        }
        
        // --- Vérification que les deux mots de passe correspondent ---
        if ($password !== $confirm_password) {
            throw new Exception('Les mots de passe ne correspondent pas.');
        }
        
        // --- Vérification que l'email n'est pas déjà utilisé ---
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cette adresse email est déjà utilisée.');
        }
        
        // --- Génération d'un login unique pour l'utilisateur ---
        // Cette fonction est définie dans email_functions.php
        $numlogin = genererLoginUnique($pdo);
        
        // --- Récupération de l'option "distanciel" (uniquement pour stagiaire FPC) ---
        $distanciel = isset($_POST['distanciel']) && $_POST['distanciel'] === '1' ? 1 : 0;
        
        // --- Insertion du nouvel utilisateur dans la base de données ---
        // Le mot de passe est haché avec SHA2-256 pour la sécurité
        // La photo par défaut est déterminée selon le sexe
        $stmt = $pdo->prepare('INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe, photo, distanciel) VALUES (:email, :prenom, :nom, :numlogin, SHA2(:password, 256), :role, :sexe, :photo, :distanciel)');
        $stmt->execute([
            'email' => $email,
            'prenom' => $prenom,
            'nom' => $nom,
            'numlogin' => $numlogin,
            'password' => $password,  // Sera haché par SHA2() dans la requête
            'role' => $role,
            'sexe' => $sexe,
            'photo' => getDefaultPhoto($sexe),
            'distanciel' => $distanciel
        ]);
        
        // --- Envoi de l'email avec les identifiants de connexion ---
        // La fonction envoyerEmailNouveauCompte est définie dans email_functions.php
        $resultEmail = envoyerEmailNouveauCompte($email, $prenom . ' ' . $nom, $numlogin);
        
        // --- Message de confirmation selon le résultat de l'envoi d'email ---
        if ($resultEmail['success']) {
            $success = "Votre compte a été créé avec succès ! Un email contenant votre login a été envoyé à votre adresse.";
        } else {
            // Si l'email n'a pas pu être envoyé, on affiche le login directement
            $success = "Votre compte a été créé avec succès ! Cependant, l'email n'a pas pu être envoyé. Votre login est : <strong>$numlogin</strong>";
        }
        
    } catch (Exception $e) {
        // --- Gestion des erreurs : affichage du message d'erreur ---
        $error = $e->getMessage();
    }
}
?>

<!-- ===================================================================
     STRUCTURE HTML DE LA PAGE D'INSCRIPTION
     ===================================================================
     
     Cette page affiche :
     - Un logo cliquable pour retourner à l'accueil
     - Le formulaire d'inscription (si compte non créé)
     - Un message de confirmation (si compte créé avec succès)
     - Des messages d'erreur si nécessaire
     
     ================================================================= -->

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center mt-5">
                <!-- Logo cliquable pour retourner à la page de connexion -->
                <a href="index.php"><img src="img/logo.png" alt="Logo EDL+" class="logo img-fluid mb-4" style="max-width: 180px;"></a>
                <h1 class="mb-3">Inscription Stagiaire</h1>
                <?php if (!$success): ?>
                    <p class="text-muted mb-5">Créez votre compte pour accéder à la plateforme EDL+</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-8 col-lg-6">
                <div class="card p-4">
                    <!-- ===== AFFICHAGE DES MESSAGES D'ERREUR ===== -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- ===== AFFICHAGE DU MESSAGE DE SUCCÈS ===== -->
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>Prochaines étapes :</strong>
                            <ul class="mb-0 mt-2">
                                <li>Vérifiez votre boîte de réception</li>
                                <li>Notez votre login pour vous connecter</li>
                                <li>Cliquez sur le bouton ci-dessous pour vous connecter</li>
                            </ul>
                        </div>
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary btn-lg w-100">Se connecter</a>
                        </div>
                        
                    <?php else: ?>
                        <!-- ===================================================================
                             FORMULAIRE D'INSCRIPTION
                             ===================================================================
                             
                             Champs obligatoires :
                             - Prénom et Nom
                             - Email (doit être unique dans le système)
                             - Sexe (détermine la photo de profil par défaut)
                             - Type de stagiaire (OP ou FPC)
                             - Mot de passe (minimum 4 caractères) + confirmation
                             
                             Option conditionnelle :
                             - Distanciel : affiché uniquement si "stagiaire FPC" est sélectionné
                             
                             ================================================================= -->
                        <form method="post" action="">
                            <!-- Prénom et Nom -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <small class="form-text text-muted">Votre login sera envoyé à cette adresse</small>
                            </div>
                            
                            <!-- Sexe et Type de stagiaire -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sexe" class="form-label">Sexe *</label>
                                    <select class="form-select" id="sexe" name="sexe" required>
                                        <option value="">-- Choisir --</option>
                                        <option value="masculin" <?php echo (($_POST['sexe'] ?? '') === 'masculin') ? 'selected' : ''; ?>>Masculin</option>
                                        <option value="feminin" <?php echo (($_POST['sexe'] ?? '') === 'feminin') ? 'selected' : ''; ?>>Féminin</option>
                                        <option value="autre" <?php echo (($_POST['sexe'] ?? '') === 'autre') ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Type de stagiaire *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">-- Choisir --</option>
                                        <option value="stagiaire OP" <?php echo (($_POST['role'] ?? '') === 'stagiaire OP') ? 'selected' : ''; ?>>Stagiaire OP</option>
                                        <option value="stagiaire FPC" <?php echo (($_POST['role'] ?? '') === 'stagiaire FPC') ? 'selected' : ''; ?>>Stagiaire FPC</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Option Distanciel (affichée uniquement pour stagiaire FPC) -->
                            <!-- Ce bloc est masqué par défaut et affiché dynamiquement via JavaScript -->
                            <div class="row mb-3" id="distancielRow" style="display:none;">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="distanciel" value="1" id="distancielCheck" <?php echo (!empty($_POST['distanciel']) && $_POST['distanciel']==='1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="distancielCheck">Session en distanciel</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mot de passe et confirmation -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Minimum 4 caractères</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <!-- Information sur l'envoi du login par email -->
                            <div class="alert alert-info">
                                <small>
                                    <strong>Information :</strong> Un login unique vous sera envoyé par mail suite à l'inscription !
                                </small>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    Créer mon compte
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-muted">Retour à la connexion</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>

<!-- ===================================================================
     JAVASCRIPT : AFFICHAGE CONDITIONNE DE L'OPTION DISTANCIEL
     ===================================================================
     
     Ce script masque/affiche l'option "distanciel" selon le type de stagiaire :
     - Caché par défaut
     - Affiché uniquement si "stagiaire FPC" est sélectionné
     - Caché si "stagiaire OP" est sélectionné
     
     ================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    var role = document.getElementById('role');
    var distRow = document.getElementById('distancielRow');
    
    // Fonction pour afficher/masquer la ligne "distanciel"
    function updateDistRow(){
        if(!role) return;
        // Afficher uniquement si le rôle est "stagiaire FPC"
        distRow.style.display = (role.value === 'stagiaire FPC') ? '' : 'none';
    }
    
    // Attacher l'événement au changement de rôle et déclencher immédiatement
    if(role){ 
        role.addEventListener('change', updateDistRow); 
        updateDistRow();  // Exécution immédiate au chargement de la page
    }
});
</script>

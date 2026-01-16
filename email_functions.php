<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Forcer la timezone de PHP pour correspondre à MySQL
date_default_timezone_set('Europe/Paris');

/**
 * Fonction pour envoyer un email de réinitialisation de mot de passe
 * 
 * @param string $destinataire_email L'email de l'utilisateur
 * @param string $destinataire_nom Le nom complet de l'utilisateur
 * @param string $token Le token de réinitialisation unique
 * @return array Tableau avec 'success' (bool) et 'message' (string)
 */
function envoyerEmailResetPassword($destinataire_email, $destinataire_nom, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = MAIL_CHARSET;
        $mail->SMTPDebug  = MAIL_DEBUG;
        
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($destinataire_email, $destinataire_nom);
        
        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe - EDL';
        
        $reset_link = BASE_URL . '/reset_password.php?token=' . urlencode($token);
        
        $mail->Body = "
        <html>
        <body>
            <h1>EDL - Réinitialisation de mot de passe</h1>
            
            <p>Bonjour <strong>" . htmlspecialchars($destinataire_nom) . "</strong>,</p>
            
            <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte EDL.</p>
            
            <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>
            
            <p><a href='" . $reset_link . "'>Réinitialiser mon mot de passe</a></p>
            
            <p>Ou copiez-collez ce lien dans votre navigateur :</p>
            <p>" . $reset_link . "</p>
            
            <p><strong>Important :</strong></p>
            <ul>
                <li>Ce lien est valable pendant <strong>" . TOKEN_EXPIRATION_MINUTES . " minutes</strong></li>
                <li>Il ne peut être utilisé qu'une seule fois</li>
                <li>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email</li>
            </ul>
            
            <p>Pour des raisons de sécurité, ne partagez jamais ce lien avec qui que ce soit.</p>
            
            <hr>
            <p><small>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</small></p>
            <p><small>&copy; " . date('Y') . " EDL - Tous droits réservés</small></p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Bonjour " . $destinataire_nom . ",\n\n"
                       . "Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte EDL.\n\n"
                       . "Pour réinitialiser votre mot de passe, copiez-collez ce lien dans votre navigateur :\n"
                       . $reset_link . "\n\n"
                       . "Ce lien est valable pendant " . TOKEN_EXPIRATION_MINUTES . " minutes et ne peut être utilisé qu'une seule fois.\n\n"
                       . "Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n"
                       . "Cordialement,\nL'équipe EDL";
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email envoyé avec succès'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}"
        ];
    }
}

/**
 * Fonction pour générer un token sécurisé aléatoire
 * 
 * @return string Token de 64 caractères (hexadécimal)
 */
function genererToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Fonction pour créer un token de réinitialisation en base de données
 * 
 * @param PDO $connexion La connexion à la base de données
 * @param int $user_id L'ID de l'utilisateur
 * @return string Le token généré
 */
function creerTokenReset($connexion, $user_id) {
    $token = genererToken();
    
    if (!defined('TOKEN_EXPIRATION_MINUTES')) {
        die("ERREUR : TOKEN_EXPIRATION_MINUTES n'est pas défini !");
    }
    
    $minutes = TOKEN_EXPIRATION_MINUTES;
    $timestamp = time() + ($minutes * 60);
    $expiration = date('Y-m-d H:i:s', $timestamp);
    
    $stmt = $connexion->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used = 0");
    $stmt->execute([$user_id]);
    
    $stmt = $connexion->prepare("
        INSERT INTO password_reset_tokens (user_id, token, expiration) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $token, $expiration]);
    
    return $token;
}

/**
 * Fonction pour vérifier si un token est valide
 * 
 * @param PDO $connexion La connexion à la base de données
 * @param string $token Le token à vérifier
 * @return array|false Retourne les données du token si valide, false sinon
 */
function verifierToken($connexion, $token) {
    $stmt = $connexion->prepare("
        SELECT * FROM password_reset_tokens 
        WHERE token = ? 
        AND used = 0 
        AND expiration > NOW()
    ");
    $stmt->execute([$token]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fonction pour marquer un token comme utilisé
 * 
 * @param PDO $connexion La connexion à la base de données
 * @param string $token Le token à marquer comme utilisé
 * @return bool Succès de l'opération
 */
function marquerTokenUtilise($connexion, $token) {
    $stmt = $connexion->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
    return $stmt->execute([$token]);
}

/**
 * Fonction pour générer un login aléatoire unique
 * 
 * @param PDO $connexion La connexion à la base de données
 * @return string Login de 6 caractères alphanumériques (minuscules + chiffres)
 */
function genererLoginUnique($connexion) {
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyz';
    $tentatives_max = 10;
    
    for ($tentative = 0; $tentative < $tentatives_max; $tentative++) {
        $login = '';
        for ($i = 0; $i < 6; $i++) {
            $login .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        $stmt = $connexion->prepare("SELECT COUNT(*) FROM utilisateurs WHERE numlogin = ?");
        $stmt->execute([$login]);
        $existe = $stmt->fetchColumn();
        
        if (!$existe) {
            return $login;
        }
    }
    
    throw new Exception("Impossible de générer un login unique après " . $tentatives_max . " tentatives");
}

/**
 * Fonction pour envoyer un email avec les identifiants de connexion
 * 
 * @param string $destinataire_email L'email de l'utilisateur
 * @param string $destinataire_nom Le nom complet de l'utilisateur
 * @param string $login Le login généré
 * @param string $password Le mot de passe (optionnel si déjà défini par l'utilisateur)
 * @return array Tableau avec 'success' (bool) et 'message' (string)
 */
function envoyerEmailNouveauCompte($destinataire_email, $destinataire_nom, $login, $password = null) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = MAIL_CHARSET;
        $mail->SMTPDebug  = MAIL_DEBUG;
        
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($destinataire_email, $destinataire_nom);
        
        $mail->isHTML(true);
        $mail->Subject = 'Vos identifiants de connexion EDL';
        
        $login_url = BASE_URL . '/index.php';
        
        $password_section = '';
        $password_text = '';
        if ($password) {
            $password_section = "<p style='margin: 5px 0;'><strong>Mot de passe :</strong> <code style='background: white; padding: 5px 10px; border-radius: 4px; font-size: 16px;'>" . htmlspecialchars($password) . "</code></p>";
            $password_text = "\nMot de passe : " . $password;
        }
        
        $mail->Body = "
        <html>
        <body>
            <h1>EDL - Bienvenue !</h1>
            
            <p>Bonjour <strong>" . htmlspecialchars($destinataire_nom) . "</strong>,</p>
            
            <p>Votre compte EDL a été créé avec succès. Voici vos identifiants de connexion :</p>
            
            <div style='background-color: #f0f0f0; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Login :</strong> <code style='background: white; padding: 5px 10px; border-radius: 4px; font-size: 16px;'>" . htmlspecialchars($login) . "</code></p>
                " . $password_section . "
            </div>
            
            <p>Pour vous connecter, rendez-vous sur :</p>
            <p><a href='" . $login_url . "'>" . $login_url . "</a></p>
            
            <p><strong>Important :</strong></p>
            <ul>
                <li><strong>Conservez précieusement ces identifiants</strong></li>
                <li>Vous pouvez modifier votre mot de passe après votre première connexion</li>
                <li>Votre login ne peut pas être modifié</li>
                <li>Pour des raisons de sécurité, ne partagez jamais vos identifiants</li>
            </ul>
            
            <hr>
            <p><small>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</small></p>
            <p><small>&copy; " . date('Y') . " EDL - Tous droits réservés</small></p>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Bonjour " . $destinataire_nom . ",\n\n"
                       . "Votre compte EDL a été créé avec succès.\n\n"
                       . "Vos identifiants de connexion :\n"
                       . "Login : " . $login
                       . $password_text . "\n\n"
                       . "Pour vous connecter : " . $login_url . "\n\n"
                       . "Conservez précieusement ces identifiants.\n\n"
                       . "Cordialement,\nL'équipe EDL";
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email envoyé avec succès'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Erreur lors de l'envoi de l'email : {$mail->ErrorInfo}"
        ];
    }
}
?>
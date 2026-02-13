# 📚 EDL+ - Plateforme d'Apprentissage du Français

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.x-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.x-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)

## 📋 Table des matières

1. [Présentation du projet](#présentation-du-projet)
2. [Technologies utilisées](#technologies-utilisées)
3. [Structure du projet](#structure-du-projet)
4. [Architecture de la base de données](#architecture-de-la-base-de-données)
5. [Installation et configuration](#installation-et-configuration)
6. [Fonctionnalités principales](#fonctionnalités-principales)
7. [Guide développeur](#guide-développeur)
8. [Gestion des utilisateurs](#gestion-des-utilisateurs)
9. [Système de ressources](#système-de-ressources)
10. [Sécurité](#sécurité)
11. [Résolution de problèmes](#résolution-de-problèmes)

---

## 🎯 Présentation du projet

**EDL+** (Espace de Langues Plus) est une plateforme web d'apprentissage du français destinée aux stagiaires et formateurs. Elle permet de :

- Gérer des utilisateurs avec différents rôles (Admin, Formateur, Stagiaire OP, Stagiaire FPC)
- Partager et consulter des ressources pédagogiques (PDF, vidéos, audio, images)
- Suivre la progression des stagiaires
- Accéder à un référentiel de compétences linguistiques (A1 à C2)
- Gérer les liaisons formateur-stagiaire
- Permettre le dépôt de documents personnels (stagiaires FPC)

### 🎓 Types d'utilisateurs

| Rôle | Description | Accès principal |
|------|-------------|-----------------|
| **Admin** | Gestion complète de la plateforme | Tous les menus de gestion |
| **Formateur** | Suivi des stagiaires et dépôt de ressources | Dashboard formateur, ressources |
| **Stagiaire OP** | Objectif Professionnel (formation courte) | Ressources, calendrier |
| **Stagiaire FPC** | Formation Professionnelle Continue | Ressources, mes documents, distanciel |

---

## 🛠️ Technologies utilisées

### Backend
- **PHP 8.x** : Langage serveur principal
- **MySQL 8.x** : Base de données relationnelle
- **PDO** : Interface d'accès aux données (requêtes préparées)
- **PHPMailer** : Envoi d'emails (récupération de mot de passe)

### Frontend
- **HTML5 / CSS3** : Structure et styles
- **Bootstrap 5.3** : Framework CSS responsive
- **JavaScript** : Interactions côté client (validation de formulaires)

### Serveur local
- **WAMP Server** : Apache + MySQL + PHP pour Windows
- Chemin d'installation : `c:\wamp64\www\EDL`

### Dépendances (Composer)
```json
{
    "phpmailer/phpmailer": "^7.0"
}
```

---

## 📁 Structure du projet

```
EDL/
│
├── 📄 index.php                    # Page de connexion (point d'entrée)
├── 📄 dashboard.php                # Dashboard principal (après connexion)
├── 📄 dashboard_formateur.php      # Dashboard spécifique formateur
│
├── 🔒 CONFIGURATION
│   ├── config.php                  # Constantes de configuration
│   ├── connexionbdd.php            # Connexion PDO à la base de données
│   ├── email_config.php            # Configuration SMTP (emails)
│   └── upload_config.php           # Configuration uploads (tailles, types)
│
├── 👤 GESTION UTILISATEURS
│   ├── inscription.php             # Inscription des stagiaires
│   ├── profil.php                  # Affichage du profil
│   ├── modifier_profil.php         # Modification du profil
│   ├── oubli_mdp.php               # Demande de réinitialisation MDP
│   ├── reset_password.php          # Réinitialisation avec token
│   ├── gestion_utilisateurs.php    # CRUD utilisateurs (admin)
│   └── delete_accounts.php         # Suppression de comptes
│
├── 📚 GESTION DES RESSOURCES
│   ├── deposer_ressource.php       # Formulaire de dépôt (formateurs)
│   ├── upload_ressource.php        # Traitement upload
│   ├── mes_ressources.php          # Liste des ressources du formateur
│   ├── mes_documents.php           # Documents perso (stagiaires FPC)
│   ├── gestion_ressources.php      # Gestion admin des ressources
│   ├── viewer.php                  # Lecteur de ressources (PDF, vidéo, audio)
│   ├── viewer_simple.php           # Version simplifiée du viewer
│   └── test_viewer.php             # Tests du viewer
│
├── 📖 RÉFÉRENTIEL & LIAISONS
│   ├── referentiel.php             # Référentiel de compétences (CECRL)
│   ├── gestion_categories.php      # Gestion des catégories
│   └── gestion_liaisons.php        # Liaisons formateur-stagiaire
│
├── 📧 EMAILS
│   ├── email_config.php            # Configuration SMTP
│   └── email_functions.php         # Fonctions d'envoi d'emails
│
├── 🎨 ASSETS
│   ├── styles.css                  # Styles personnalisés (documenté)
│   ├── img/                        # Images (logos, icônes)
│   ├── pp/                         # Photos de profil
│   └── fournisseurs/               # Logos partenaires
│
├── 📤 UPLOADS (ressources déposées)
│   ├── pdf/                        # Documents PDF
│   ├── video/                      # Vidéos (MP4, AVI, etc.)
│   ├── audio/                      # Fichiers audio (MP3, WAV, etc.)
│   ├── images/                     # Images (JPG, PNG, etc.)
│   └── autres/                     # Autres types de fichiers
│
├── 🗃️ SQL
│   ├── script_creation.sql         # Création table utilisateurs
│   ├── script_referentiel_table.sql # Création table référentiel
│   ├── create_ressources_table.sql  # Création table ressources
│   ├── create_stagiaire_formateur.sql # Liaisons stagiaire/formateur
│   └── (autres scripts SQL)
│
├── 🔧 SCRIPTS
│   ├── check_ressources_table.php
│   ├── create_ressources_table.php
│   └── apply_stagiaire_formateur_fks.php
│
├── 📦 COMPOSER
│   ├── composer.json               # Dépendances du projet
│   ├── composer.lock               # Versions figées
│   └── vendor/                     # Bibliothèques tierces (PHPMailer)
│
└── 📄 footer.php                   # Pied de page commun
```

---

## 🗄️ Architecture de la base de données

### Schéma de la base : `EDL`

#### Table `utilisateurs` 👥
Stocke tous les utilisateurs de la plateforme.

```sql
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    numlogin VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,            -- SHA2-256
    role ENUM('admin', 'formateur', 'stagiaire OP', 'stagiaire FPC'),
    sexe ENUM('masculin', 'feminin', 'autre'),
    photo VARCHAR(255),                         -- Chemin vers pp/
    distanciel TINYINT(1) DEFAULT 0,           -- Accès distanciel (FPC)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Rôles possibles :**
- `admin` : Administrateur (accès total)
- `formateur` : Formateur (gestion stagiaires + ressources)
- `stagiaire OP` : Stagiaire Objectif Professionnel
- `stagiaire FPC` : Stagiaire Formation Professionnelle Continue

#### Table `referentiel` 📖
Référentiel de compétences linguistiques (CECRL : A1 à C2).

```sql
CREATE TABLE referentiel (
    module ENUM('Bases', 'Conjugaison', 'Grammaire', 'Prononciation', 
                'Methodologie', 'Vocabulaire', 'Au Quotidien'),
    code VARCHAR(10) PRIMARY KEY,              -- Ex: B_01, C_02
    contenu TEXT NOT NULL,                     -- Description de la compétence
    niveaux SET('A1', 'A2', 'B1', 'B2', 'C1', 'C2'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Table `ressources` 📚
Ressources pédagogiques déposées par les formateurs.

```sql
CREATE TABLE ressources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    type_fichier VARCHAR(50),                   -- pdf, video, audio, image
    chemin_fichier VARCHAR(255) NOT NULL,       -- uploads/pdf/xxx.pdf
    formateur_id INT,                           -- Qui a déposé
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
```

#### Table `stagiaire_formateur` 🔗
Liaisons entre formateurs et stagiaires (relation many-to-many).

```sql
CREATE TABLE stagiaire_formateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stagiaire_id INT NOT NULL,
    formateur_id INT NOT NULL,
    date_liaison TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stagiaire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_liaison (stagiaire_id, formateur_id)
);
```

#### Table `password_resets` 🔑
Tokens de réinitialisation de mot de passe (expire après 1h).

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiration DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expiration (expiration)
);
```

### Diagramme de relations

```
┌─────────────┐         ┌──────────────────────┐         ┌─────────────┐
│utilisateurs │◄────────│stagiaire_formateur   │────────►│utilisateurs │
│(stagiaire)  │         │                      │         │(formateur)  │
└─────────────┘         └──────────────────────┘         └──────┬──────┘
                                                                  │
                                                                  │ formateur_id
                                                                  │
                                                         ┌────────▼────────┐
                                                         │   ressources    │
                                                         │                 │
                                                         └─────────────────┘
```

---

## ⚙️ Installation et configuration

### Prérequis

- ✅ WAMP Server (ou XAMPP/LAMP) installé
- ✅ PHP 8.0 ou supérieur
- ✅ MySQL 8.0 ou supérieur
- ✅ Composer installé globalement
- ✅ Navigateur moderne (Chrome, Firefox, Edge)

### Étape 1 : Cloner le projet

```bash
cd c:\wamp64\www
git clone https://github.com/baudenzo/extranetEDL.git EDL
cd EDL
```

### Étape 2 : Installer les dépendances

```bash
composer install
```
Cela va installer PHPMailer dans le dossier `vendor/`.

### Étape 3 : Créer la base de données

1. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
2. Créer une base de données nommée `EDL`
3. Importer les scripts SQL dans cet ordre :

```sql
-- 1. Créer la base
CREATE DATABASE IF NOT EXISTS EDL;
USE EDL;

-- 2. Table utilisateurs
SOURCE sql/script_creation.sql;

-- 3. Table référentiel
SOURCE sql/script_referentiel_table.sql;
SOURCE sql/script_referentiel_insertion.sql;

-- 4. Table ressources
SOURCE sql/create_ressources_table.sql;

-- 5. Table liaisons stagiaire-formateur
SOURCE sql/create_stagiaire_formateur.sql;

-- 6. Table password_resets
SOURCE sql/script_password_reset.sql;
```

### Étape 4 : Configurer les paramètres

#### A. Base de données (`config.php`)

```php
// Adapter selon votre environnement
define('DB_HOST', 'localhost');
define('DB_NAME', 'EDL');
define('DB_USER', 'root');          // En prod : utilisateur dédié
define('DB_PASSWORD', '');          // En prod : mot de passe fort
define('DB_CHARSET', 'utf8mb4');
```

⚠️ **IMPORTANT** : 
- Ne **jamais** commiter `config.php` sur GitHub avec vos vrais identifiants
- Ajouter `config.php` au `.gitignore`
- En production, utiliser un utilisateur MySQL dédié avec droits limités

#### B. Configuration email (`email_config.php`)

Pour la récupération de mot de passe :

```php
define('SMTP_HOST', 'smtp.gmail.com');     // Serveur SMTP
define('SMTP_PORT', 587);                  // Port TLS
define('SMTP_USER', 'votre@email.com');    // Votre adresse email
define('SMTP_PASSWORD', 'mot_de_passe_application'); // Mot de passe app
define('SMTP_FROM_EMAIL', 'votre@email.com');
define('SMTP_FROM_NAME', 'EDL+');
```

**Note Gmail** : Vous devez générer un "mot de passe d'application" :
1. Compte Google → Sécurité → Validation en deux étapes
2. Mots de passe des applications → Générer

#### C. Configuration uploads (`upload_config.php`)

Définit les types de fichiers acceptés et tailles maximales :

```php
// Types MIME autorisés par catégorie
define('ALLOWED_TYPES', [
    'pdf' => ['application/pdf'],
    'video' => ['video/mp4', 'video/x-msvideo', 'video/quicktime'],
    'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
    'images' => ['image/jpeg', 'image/png', 'image/gif']
]);

// Tailles max (en octets)
define('MAX_FILE_SIZE', 50 * 1024 * 1024);  // 50 MB
```

### Étape 5 : Créer les dossiers nécessaires

```bash
# Dossiers uploads (si non présents)
mkdir -p uploads/pdf uploads/video uploads/audio uploads/images uploads/autres

# Dossiers photos de profil et logos
mkdir -p pp img fournisseurs

# Donner les droits d'écriture (Linux/Mac)
chmod -R 777 uploads pp
```

### Étape 6 : Créer un compte admin

Insérer un admin directement en SQL :

```sql
INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe) 
VALUES (
    'admin@edl.com',
    'Admin',
    'Principal',
    'admin',
    SHA2('MotDePasse123', 256),  -- Remplacer par votre mot de passe
    'admin',
    'autre'
);
```

### Étape 7 : Démarrer WAMP et tester

1. Démarrer WAMP Server
2. Ouvrir : `http://localhost/EDL/`
3. Se connecter avec le compte admin créé

✅ **Installation terminée !**

---

## 🚀 Fonctionnalités principales

### 1. 🔐 Authentification

**Fichiers concernés :**
- `index.php` : Page de connexion
- `inscription.php` : Inscription des stagiaires
- `oubli_mdp.php` / `reset_password.php` : Récupération de mot de passe

**Fonctionnement :**
- Mot de passe hashé avec SHA2-256
- Session PHP pour maintenir la connexion
- Token unique (32 caractères) pour reset de mot de passe (expire 1h)
- Email automatique avec lien de réinitialisation

**Sécurité :**
- Requêtes préparées PDO (anti-injection SQL)
- Vérification de session sur chaque page
- Redirection automatique si non connecté

### 2. 👥 Gestion des utilisateurs (Admin)

**Fichier principal :** `gestion_utilisateurs.php`

**Actions possibles :**
- ✅ **Créer** un utilisateur (formateur ou stagiaire)
- ✏️ **Modifier** les informations (nom, email, rôle, photo)
- 🗑️ **Supprimer** un compte
- 🔄 **Changer le rôle** d'un utilisateur
- 📸 **Upload de photo** de profil

**Upload de photos :**
```php
// Extensions autorisées : jpg, jpeg, png, gif
// Taille max : 5 MB
// Stockage : pp/photo_[id].[extension]
```

### 3. 📚 Système de ressources pédagogiques

#### A. Dépôt de ressources (Formateurs)

**Fichiers :** `deposer_ressource.php`, `upload_ressource.php`

**Types de fichiers acceptés :**
- 📄 PDF (documents, cours, exercices)
- 🎥 Vidéos (MP4, AVI, MOV, WebM)
- 🔊 Audio (MP3, WAV, OGG)
- 🖼️ Images (JPG, PNG, GIF)

**Processus d'upload :**
1. Formateur remplit le formulaire (titre, description, fichier)
2. Vérification du type MIME et taille
3. Renommage sécurisé : `timestamp_nomfichier.ext`
4. Stockage dans `uploads/[type]/`
5. Enregistrement en BDD avec lien vers le formateur

**Exemple de fichier uploadé :**
```
uploads/pdf/1770388670_liste-verbes-irreguliers.pdf
         ↑         ↑
    timestamp   nom original
```

#### B. Consultation de ressources

**Fichiers :** `mes_ressources.php`, `viewer.php`

**Viewer universel :**
- **PDF** : Intégré avec `<embed>` ou iframe
- **Vidéo** : Lecteur HTML5 `<video>` avec contrôles
- **Audio** : Lecteur HTML5 `<audio>`
- **Images** : Affichage direct avec `<img>`

**Fonctionnalités du viewer :**
- Lecteur adapté au type de fichier
- Bouton de téléchargement
- Informations sur la ressource (titre, description, date)
- Design responsive (mobile-friendly)

#### C. Documents personnels (Stagiaires FPC)

**Fichier :** `mes_documents.php`

Les stagiaires FPC peuvent déposer leurs propres documents (devoirs, projets personnels, etc.). Chaque stagiaire ne voit que ses documents.

### 4. 📖 Référentiel de compétences

**Fichier :** `referentiel.php`

**Description :**
Référentiel basé sur le **CECRL** (Cadre Européen Commun de Référence pour les Langues) avec 7 modules linguistiques et 6 niveaux (A1 à C2).

**Modules :**
1. **Bases** : Alphabet, chiffres, salutations
2. **Conjugaison** : Temps verbaux, modes
3. **Grammaire** : Articles, pronoms, syntaxe
4. **Prononciation** : Phonétique, intonation
5. **Méthodologie** : Techniques d'apprentissage
6. **Vocabulaire** : Thématiques variées
7. **Au Quotidien** : Situations pratiques

**Fonctionnalités admin :**
- Ajouter/Modifier/Supprimer des compétences
- Recherche par module ou niveau
- Code unique pour chaque compétence (ex: B_01, C_02)

### 5. 🔗 Liaisons formateur-stagiaire

**Fichier :** `gestion_liaisons.php`

**Principe :**
- Un formateur peut suivre plusieurs stagiaires
- Un stagiaire peut avoir plusieurs formateurs
- Relation **many-to-many** via table `stagiaire_formateur`

**Cas d'usage :**
- Afficher les stagiaires d'un formateur dans son dashboard
- Afficher le(s) formateur(s) d'un stagiaire
- Filtrer les ressources accessibles aux stagiaires

**Exemple de liaison :**
```sql
INSERT INTO stagiaire_formateur (stagiaire_id, formateur_id) 
VALUES (5, 2);  -- Stagiaire #5 ← Formateur #2
```

### 6. 📊 Dashboards spécifiques

#### Dashboard Admin
**Fichier :** `dashboard.php` (rôle = admin)

**Affichage :**
- Statistiques globales (nombre d'utilisateurs par rôle)
- Accès rapide aux menus de gestion
- Liste des dernières ressources déposées

#### Dashboard Formateur
**Fichier :** `dashboard_formateur.php`

**Affichage :**
- Liste de ses stagiaires (OP et FPC)
- Bouton pour déposer une ressource
- Ressources récemment ajoutées
- Accès au référentiel

#### Dashboard Stagiaire OP
**Fichier :** `dashboard.php` (rôle = stagiaire OP)

**Affichage :**
- Calendrier des séances (si implémenté)
- Ressources disponibles
- Informations sur le formateur

#### Dashboard Stagiaire FPC
**Fichier :** `dashboard.php` (rôle = stagiaire FPC)

**Affichage :**
- Mes Documents (dépôt perso)
- Ressources du formateur
- Menu "Distanciel" si activé dans la BDD
- Progression personnelle

---

## 💻 Guide développeur

### Comment ajouter une nouvelle page ?

#### 1. Créer le fichier PHP

```php
<?php
/**
 * NOUVELLE FONCTIONNALITÉ
 * Description de ce que fait cette page
 */

// Vérifier la session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Connexion BDD
include 'connexionbdd.php';
$pdo = ConnexionBDD();

// Récupérer l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Votre code ici...
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Titre de la page - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Votre contenu -->
</body>
</html>
```

#### 2. Ajouter un lien dans la navbar

Modifier le fichier approprié (`dashboard.php`, `dashboard_formateur.php`, etc.) :

```php
<li class="nav-item">
    <a class="nav-link" href="ma_nouvelle_page.php">
        🆕 Titre du menu
    </a>
</li>
```

#### 3. Ajouter une route conditionnelle (selon le rôle)

```php
<?php if ($user['role'] === 'admin' || $user['role'] === 'formateur'): ?>
    <a href="page_specifique.php">Accès réservé</a>
<?php endif; ?>
```

### Comment ajouter un nouveau type de ressource ?

#### 1. Modifier `upload_config.php`

```php
define('ALLOWED_TYPES', [
    'pdf' => ['application/pdf'],
    'video' => ['video/mp4', 'video/x-msvideo'],
    'audio' => ['audio/mpeg', 'audio/wav'],
    'images' => ['image/jpeg', 'image/png'],
    'nouveau_type' => ['mime/type']  // ← Ajouter ici
]);
```

#### 2. Créer le dossier de stockage

```bash
mkdir uploads/nouveau_type
chmod 777 uploads/nouveau_type  # Linux/Mac
```

#### 3. Adapter le viewer (`viewer.php`)

```php
} elseif ($type_fichier === 'nouveau_type') {
    // Code d'affichage pour le nouveau type
    echo '<div class="nouveau-viewer">';
    // ...
    echo '</div>';
}
```

### Comment créer une nouvelle table ?

#### 1. Écrire le script SQL

Créer `sql/create_ma_table.sql` :

```sql
CREATE TABLE IF NOT EXISTS ma_nouvelle_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    champ1 VARCHAR(100) NOT NULL,
    champ2 TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2. Exécuter le script

```bash
# Via phpMyAdmin ou CLI MySQL
mysql -u root -p EDL < sql/create_ma_table.sql
```

#### 3. Créer les fonctions CRUD

```php
<?php
// CREATE
function creerEnregistrement($pdo, $champ1, $champ2, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO ma_nouvelle_table (champ1, champ2, user_id) VALUES (?, ?, ?)");
    return $stmt->execute([$champ1, $champ2, $user_id]);
}

// READ
function lireEnregistrements($pdo) {
    $stmt = $pdo->query("SELECT * FROM ma_nouvelle_table ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// UPDATE
function modifierEnregistrement($pdo, $id, $champ1, $champ2) {
    $stmt = $pdo->prepare("UPDATE ma_nouvelle_table SET champ1 = ?, champ2 = ? WHERE id = ?");
    return $stmt->execute([$champ1, $champ2, $id]);
}

// DELETE
function supprimerEnregistrement($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM ma_nouvelle_table WHERE id = ?");
    return $stmt->execute([$id]);
}
?>
```

### Bonnes pratiques de code

#### ✅ Toujours utiliser des requêtes préparées

```php
// ✅ BON (sécurisé)
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);

// ❌ MAUVAIS (injection SQL possible)
$query = "SELECT * FROM utilisateurs WHERE email = '$email'";
$result = $pdo->query($query);
```

#### ✅ Valider les données côté serveur

```php
// Vérifier que les champs obligatoires sont remplis
if (empty($_POST['email']) || empty($_POST['password'])) {
    $error = "Tous les champs sont obligatoires";
}

// Valider le format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email invalide";
}
```

#### ✅ Échapper les sorties HTML

```php
// Éviter les failles XSS
echo htmlspecialchars($user['nom'], ENT_QUOTES, 'UTF-8');
```

#### ✅ Commenter votre code

Voir les fichiers existants comme `dashboard.php` ou `gestion_utilisateurs.php` pour des exemples de commentaires bien structurés.

---

## 👤 Gestion des utilisateurs

### Rôles et permissions

| Fonctionnalité | Admin | Formateur | Stagiaire OP | Stagiaire FPC |
|----------------|-------|-----------|--------------|---------------|
| Gérer utilisateurs | ✅ | ❌ | ❌ | ❌ |
| Gérer référentiel | ✅ | ❌ | ❌ | ❌ |
| Gérer liaisons | ✅ | ❌ | ❌ | ❌ |
| Gérer ressources (toutes) | ✅ | ❌ | ❌ | ❌ |
| Déposer ressources | ✅ | ✅ | ❌ | ❌ |
| Voir ressources | ✅ | ✅ | ✅ | ✅ |
| Déposer documents perso | ❌ | ❌ | ❌ | ✅ |
| Modifier son profil | ✅ | ✅ | ✅ | ✅ |

### Comment changer le rôle d'un utilisateur ?

#### Via l'interface (Admin)

1. Se connecter en tant qu'admin
2. Menu **Gestion Utilisateurs**
3. Cliquer sur "Modifier" à côté de l'utilisateur
4. Changer le rôle dans le menu déroulant
5. Enregistrer

#### Via SQL

```sql
UPDATE utilisateurs 
SET role = 'formateur' 
WHERE id = 5;
```

### Photo de profil par défaut

**Logique :**
```php
// Si l'utilisateur a une photo personnalisée
if (!empty($user['photo']) && file_exists($user['photo'])) {
    $photo_url = $user['photo'];
} else {
    // Sinon, utiliser une photo par défaut selon le sexe
    if ($user['sexe'] === 'feminin') {
        $photo_url = 'pp/femme.png';
    } else {
        $photo_url = 'pp/homme.png';
    }
}
```

**Photos par défaut à placer dans `pp/` :**
- `homme.png`
- `femme.png`
- `autre.png` (optionnel)

---

## 📦 Système de ressources

### Limites d'upload

**Fichier de configuration :** `upload_config.php`

| Type | Taille max | Extensions |
|------|------------|------------|
| PDF | 50 MB | .pdf |
| Vidéo | 50 MB | .mp4, .avi, .mov, .webm |
| Audio | 50 MB | .mp3, .wav, .ogg |
| Images | 50 MB | .jpg, .jpeg, .png, .gif |

⚠️ **Configurer `php.ini` si nécessaire :**

```ini
upload_max_filesize = 50M
post_max_size = 52M
max_execution_time = 300
```

Redémarrer WAMP après modification.

### Sécurité des uploads

**Vérifications effectuées :**

1. ✅ Vérification du type MIME réel (pas juste l'extension)
2. ✅ Limitation de taille
3. ✅ Renommage des fichiers (éviter écrasement)
4. ✅ Stockage hors de la racine web (dans `uploads/`)
5. ✅ Vérification de l'utilisateur (seuls formateurs peuvent uploader)

**Exemple de code de sécurité :**

```php
// Vérifier le type MIME réel
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['fichier']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, ALLOWED_TYPES['pdf'])) {
    die("Type de fichier non autorisé");
}

// Renommer de façon sécurisée
$nouveau_nom = time() . '_' . basename($_FILES['fichier']['name']);
$chemin = "uploads/pdf/" . $nouveau_nom;
```

### Nettoyage des fichiers orphelins

Si des fichiers sont en BDD mais plus sur le disque (ou inverse), utiliser ce script :

```php
<?php
// Script de nettoyage (créer cleanup.php)
include 'connexionbdd.php';
$pdo = ConnexionBDD();

// Supprimer les entrées BDD sans fichier
$ressources = $pdo->query("SELECT * FROM ressources")->fetchAll();
foreach ($ressources as $ressource) {
    if (!file_exists($ressource['chemin_fichier'])) {
        $pdo->prepare("DELETE FROM ressources WHERE id = ?")->execute([$ressource['id']]);
        echo "Supprimé : {$ressource['titre']}\n";
    }
}

echo "Nettoyage terminé.";
?>
```

---

## 🔒 Sécurité

### Checklist de sécurité

#### ✅ Protection des mots de passe
- Hash SHA2-256 (ou mieux : `password_hash()` PHP)
- Jamais de mots de passe en clair
- Token de reset expire après 1h

#### ✅ Protection contre les injections SQL
- Toujours utiliser des requêtes préparées PDO
- Ne jamais concaténer directement les entrées utilisateur

#### ✅ Protection XSS (Cross-Site Scripting)
- Échapper toutes les sorties : `htmlspecialchars()`
- Valider les entrées côté serveur

#### ✅ Gestion de session
- `session_start()` sur chaque page protégée
- Vérifier `$_SESSION['user_id']` avant action sensible
- Détruire la session à la déconnexion : `session_destroy()`

#### ✅ Protection CSRF (Cross-Site Request Forgery)
- Implémenter des tokens CSRF pour les formulaires sensibles

Exemple :
```php
// Génération du token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Dans le formulaire
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Vérification
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Token CSRF invalide");
}
```

#### ✅ Protection des fichiers sensibles
- Ajouter au `.gitignore` :
  - `config.php`
  - `email_config.php`
  - `uploads/`
  - `pp/`
  - `vendor/`

#### ✅ HTTPS en production
- Toujours utiliser HTTPS pour chiffrer les communications
- Forcer HTTPS :
```php
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}
```

### Recommandations pour la mise en production

1. **Changer les identifiants BDD**
   - Créer un utilisateur MySQL dédié
   - Lui donner uniquement les droits nécessaires (SELECT, INSERT, UPDATE, DELETE)
   - Pas de droits DROP, CREATE, ALTER en production

   ```sql
   CREATE USER 'edl_user'@'localhost' IDENTIFIED BY 'mot_de_passe_fort_123!';
   GRANT SELECT, INSERT, UPDATE, DELETE ON EDL.* TO 'edl_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Désactiver les erreurs PHP en production**
   ```php
   // En début de config.php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

3. **Configurer un fichier `.htaccess`**
   ```apache
   # Bloquer l'accès direct aux fichiers de config
   <FilesMatch "^(config|email_config|upload_config|connexionbdd)\.php$">
       Order Allow,Deny
       Deny from all
   </FilesMatch>
   
   # Bloquer les injections courantes
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
       RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
       RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2})
       RewriteRule ^(.*)$ index.php [F,L]
   </IfModule>
   ```

4. **Sauvegardes régulières**
   - Base de données : `mysqldump -u root -p EDL > backup_EDL_$(date +%F).sql`
   - Fichiers uploads : copie du dossier `uploads/`

---

## 🐛 Résolution de problèmes

### Problème : "Cannot connect to database"

**Causes possibles :**
1. WAMP/MySQL non démarré
2. Identifiants incorrects dans `config.php`
3. Base de données `EDL` non créée

**Solutions :**
```bash
# 1. Vérifier que WAMP est démarré (icône verte)
# 2. Ouvrir phpMyAdmin : http://localhost/phpmyadmin
# 3. Vérifier que la base "EDL" existe
# 4. Tester la connexion :
```

```php
<?php
// Fichier test_connexion.php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=EDL;charset=utf8mb4', 'root', '');
    echo "✅ Connexion réussie !";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
```

### Problème : "Session already started"

**Cause :** Appel multiple de `session_start()`.

**Solution :**
```php
// Ajouter au début de chaque page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Problème : Upload de fichier échoue

**Causes possibles :**
1. Taille du fichier > limite PHP
2. Dossier `uploads/` non accessible en écriture
3. Type MIME non autorisé

**Solutions :**
```bash
# 1. Vérifier php.ini
upload_max_filesize = 50M
post_max_size = 52M

# 2. Donner les droits (Linux/Mac)
chmod -R 777 uploads/

# 3. Vérifier upload_config.php
```

### Problème : Images/CSS ne se chargent pas

**Cause :** Chemins relatifs incorrects.

**Solution :**
```html
<!-- Utiliser des chemins absolus depuis la racine -->
<link rel="stylesheet" href="/EDL/styles.css">
<img src="/EDL/img/logo.png" alt="Logo">

<!-- Ou en PHP -->
<link rel="stylesheet" href="<?= $_SERVER['REQUEST_URI'] ?>styles.css">
```

### Problème : Email de reset non envoyé

**Causes possibles :**
1. Configuration SMTP incorrecte dans `email_config.php`
2. Mot de passe d'application Gmail non généré
3. Ports bloqués par le firewall

**Solutions :**
```php
// Activer le mode debug dans email_functions.php
$mail->SMTPDebug = 2;  // 0 = off, 1 = client, 2 = server

// Vérifier que le port 587 (TLS) est ouvert
// Ou essayer le port 465 (SSL)
define('SMTP_PORT', 465);
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
```

### Problème : Warning "Undefined array key"

**Cause :** Accès à une clé de tableau non définie.

**Solution :**
```php
// ❌ AVANT
$email = $_POST['email'];

// ✅ APRÈS
$email = $_POST['email'] ?? '';
// ou
$email = isset($_POST['email']) ? $_POST['email'] : '';
```

### Problème : Page blanche (erreur 500)

**Cause :** Erreur PHP fatale.

**Solutions :**
```php
// 1. Activer temporairement l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Vérifier les logs Apache/PHP
// WAMP : c:\wamp64\logs\php_error.log

// 3. Vérifier la syntaxe PHP
php -l mon_fichier.php
```

---

## 📞 Support et contact

### Ressources utiles

- 📖 **Documentation PHP** : https://www.php.net/manual/fr/
- 🗄️ **Documentation MySQL** : https://dev.mysql.com/doc/
- 🎨 **Documentation Bootstrap** : https://getbootstrap.com/docs/5.3/
- 📧 **Documentation PHPMailer** : https://github.com/PHPMailer/PHPMailer

### Contribution

Pour contribuer au projet :

1. Fork le repository GitHub
2. Créer une branche : `git checkout -b feature/ma-fonctionnalite`
3. Commiter les changements : `git commit -m "Ajout de ma fonctionnalité"`
4. Push vers la branche : `git push origin feature/ma-fonctionnalite`
5. Ouvrir une Pull Request

### Notes pour le prochain stagiaire

#### ✨ Améliorations possibles

1. **Système de notifications**
   - Alerter les stagiaires quand une nouvelle ressource est ajoutée
   - Notifications email ou push

2. **Tableau de bord analytics**
   - Statistiques d'utilisation des ressources
   - Taux de consultation par stagiaire
   - Graphiques de progression

3. **Système de quiz**
   - Créer des quiz basés sur le référentiel
   - Suivre les scores des stagiaires
   - Génération automatique de rapports

4. **Messagerie interne**
   - Chat entre formateur et stagiaire
   - Système de forums ou discussions

5. **Calendrier intégré**
   - Planification des séances
   - Rappels automatiques par email
   - Synchronisation avec Google Calendar

6. **API REST**
   - Exposer des endpoints pour une future app mobile
   - JSON Web Token (JWT) pour l'authentification

7. **Migration vers des mots de passe plus sécurisés**
   - Remplacer SHA2-256 par `password_hash()` / `password_verify()`
   - Exemple :
   ```php
   // Hashage
   $hash = password_hash($password, PASSWORD_BCRYPT);
   
   // Vérification
   if (password_verify($password, $hash)) {
       // OK
   }
   ```

#### 🛠️ Code technique à nettoyer

- [ ] Factoriser les navbars (actuellement dupliquées dans chaque fichier)
- [ ] Créer un système de templates (header.php, navbar.php, footer.php)
- [ ] Uniformiser la gestion des erreurs (try-catch global)
- [ ] Ajouter des logs d'activité (qui fait quoi et quand)
- [ ] Optimiser les requêtes SQL (éviter les N+1, ajouter des index)

#### 📝 Documentation à compléter

- [ ] Documenter les fonctions dans `email_functions.php`
- [ ] Ajouter un diagramme de flux d'authentification
- [ ] Créer un guide utilisateur (non technique) pour les formateurs

---

## 🎓 Conclusion

Ce projet est une base solide pour une plateforme d'apprentissage. Le code est bien structuré, commenté, et sécurisé pour un environnement de développement.

**Prochaines étapes recommandées :**
1. ✅ Lire ce README en entier
2. ✅ Tester toutes les fonctionnalités localement
3. ✅ Créer des comptes de test (admin, formateur, stagiaires)
4. ✅ Explorer la base de données avec phpMyAdmin
5. ✅ Lire les commentaires dans les fichiers PHP principaux
6. 🚀 Commencer les améliorations !

**Bon courage pour la suite du développement ! 💪**

---

*Documentation rédigée le 13 février 2026*  
*Projet EDL+ - Plateforme d'apprentissage du français*  
*Auteurs : Équipe de développement EDL*

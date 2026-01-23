<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

include 'connexionbdd.php';
$pdo = ConnexionBDD();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

$feedback = '';
$q = trim($_GET['q'] ?? '');
$filtre_module = $_GET['filtre_module'] ?? '';
$filtre_niveau = $_GET['filtre_niveau'] ?? '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $module = trim($_POST['module'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $contenu = trim($_POST['contenu'] ?? '');
            $niveaux_array = $_POST['niveaux'] ?? [];
            
            if (!$module || !$code || !$contenu) {
                throw new Exception('Le module, le code et le contenu sont requis.');
            }
            
            // Vérifier que le code n'existe pas déjà
            $check = $pdo->prepare('SELECT code FROM referentiel WHERE code = :code');
            $check->execute(['code' => $code]);
            if ($check->fetch()) {
                throw new Exception('Ce code existe déjà dans le référentiel.');
            }
            
            $niveaux = !empty($niveaux_array) ? implode(',', $niveaux_array) : null;
            
            $stmt_insert = $pdo->prepare('INSERT INTO referentiel (module, code, contenu, niveaux) VALUES (:module, :code, :contenu, :niveaux)');
            $stmt_insert->execute([
                'module' => $module,
                'code' => $code,
                'contenu' => $contenu,
                'niveaux' => $niveaux
            ]);
            
            $feedback = 'Contenu créé avec succès !';
            
        } elseif ($action === 'update_all') {
            // Mise à jour multiple
            $codes = $_POST['codes'] ?? [];
            $modules = $_POST['modules'] ?? [];
            $contenus = $_POST['contenus'] ?? [];
            $niveaux_sets = $_POST['niveaux_sets'] ?? [];
            
            $updateCount = 0;
            foreach ($codes as $code) {
                $code = trim($code);
                $module = trim($modules[$code] ?? '');
                $contenu = trim($contenus[$code] ?? '');
                $niveaux_array = $niveaux_sets[$code] ?? [];
                
                if ($code && $module && $contenu) {
                    // Récupérer les valeurs actuelles
                    $stmt_current = $pdo->prepare('SELECT module, contenu, niveaux FROM referentiel WHERE code = :code');
                    $stmt_current->execute(['code' => $code]);
                    $current = $stmt_current->fetch(PDO::FETCH_ASSOC);
                    
                    if ($current) {
                        $niveaux = !empty($niveaux_array) ? implode(',', $niveaux_array) : null;
                        
                        // Vérifier si quelque chose a vraiment changé
                        if ($current['module'] !== $module || $current['contenu'] !== $contenu || $current['niveaux'] !== $niveaux) {
                            $stmt = $pdo->prepare('UPDATE referentiel SET module = :module, contenu = :contenu, niveaux = :niveaux WHERE code = :code');
                            $stmt->execute([
                                'module' => $module,
                                'contenu' => $contenu,
                                'niveaux' => $niveaux,
                                'code' => $code
                            ]);
                            $updateCount++;
                        }
                    }
                }
            }
            
            $feedback = $updateCount > 0 ? "$updateCount contenu(s) mis à jour." : 'Aucune modification effectuée.';
            
        } elseif ($action === 'delete') {
            $code = trim($_POST['code'] ?? '');
            if (!$code) {
                throw new Exception('Code invalide pour suppression.');
            }
            
            $stmt = $pdo->prepare('DELETE FROM referentiel WHERE code = :code');
            $stmt->execute(['code' => $code]);
            $feedback = 'Contenu supprimé.';
        }
    } catch (Exception $e) {
        $feedback = 'Erreur: ' . htmlspecialchars($e->getMessage());
    }
}

// Requête de recherche dans le référentiel avec filtres multiples
$whereClauses = [];
$params = [];

// Filtre par recherche textuelle
if ($q !== '') {
    $like = '%' . $q . '%';
    $whereClauses[] = '(code LIKE :q1 OR contenu LIKE :q2 OR module LIKE :q3)';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}

// Filtre par module
if ($filtre_module !== '') {
    $whereClauses[] = 'module = :module';
    $params['module'] = $filtre_module;
}

// Filtre par niveau
if ($filtre_niveau !== '') {
    $whereClauses[] = 'FIND_IN_SET(:niveau, niveaux) > 0';
    $params['niveau'] = $filtre_niveau;
}

if (!empty($whereClauses)) {
    $whereClause = implode(' AND ', $whereClauses);
    $stmt_ref = $pdo->prepare('SELECT * FROM referentiel 
                                WHERE ' . $whereClause . ' 
                                ORDER BY module, CAST(SUBSTRING(code, LOCATE("-C", code) + 2) AS UNSIGNED)');
    $stmt_ref->execute($params);
    $referentiels = $stmt_ref->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Récupérer tous les éléments du référentiel
    $stmt_ref = $pdo->prepare('SELECT * FROM referentiel ORDER BY module, CAST(SUBSTRING(code, LOCATE("-C", code) + 2) AS UNSIGNED)');
    $stmt_ref->execute();
    $referentiels = $stmt_ref->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer le dernier numéro de code pour chaque module
$dernier_codes = [];
$modules = ['Bases' => 'B', 'Conjugaison' => 'C', 'Grammaire' => 'G', 'Prononciation' => 'P', 'Methodologie' => 'M', 'Vocabulaire' => 'V', 'Au Quotidien' => 'A'];
foreach ($modules as $module_name => $letter) {
    $stmt_max = $pdo->prepare('SELECT code FROM referentiel WHERE module = :module ORDER BY CAST(SUBSTRING(code, LOCATE("-C", code) + 2) AS UNSIGNED) DESC LIMIT 1');
    $stmt_max->execute(['module' => $module_name]);
    $last = $stmt_max->fetch(PDO::FETCH_ASSOC);
    
    if ($last) {
        // Extraire le numéro du dernier code
        preg_match('/-C(\d+)$/', $last['code'], $matches);
        $dernier_codes[$module_name] = isset($matches[1]) ? (int)$matches[1] : 0;
    } else {
        $dernier_codes[$module_name] = 0;
    }
}

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Référentiel Pédagogique - EDL</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Accueil</a>
                    </li>
                    <?php if ($user['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdownGestion" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Gestion
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestion">
                                <li><a class="dropdown-item" href="gestion_utilisateurs.php">Gestion des utilisateurs</a></li>
                                <li><a class="dropdown-item" href="referentiel.php">Gestion référentiel</a></li>
                            </ul>
                        </li>
                    <?php elseif ($user['role'] == 'formateur'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes formations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Stagiaires</a>
                        </li>
                    <?php elseif ($user['role'] == 'stagiaire OP'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Calendrier des séances</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Ressources</a>
                        </li>
                    <?php elseif ($user['role'] == 'stagiaire FPC'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Documents</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Mes Ressources</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Émargement</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-md-5 mt-4">
        <h2>Référentiel Pédagogique</h2>
        
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Créer un nouveau contenu</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-3">
                        <label class="form-label">Module</label>
                        <select name="module" id="moduleSelect" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="Bases">Bases</option>
                            <option value="Conjugaison">Conjugaison</option>
                            <option value="Grammaire">Grammaire</option>
                            <option value="Prononciation">Prononciation</option>
                            <option value="Methodologie">Méthodologie</option>
                            <option value="Vocabulaire">Vocabulaire</option>
                            <option value="Au Quotidien">Au Quotidien</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="codeInput" class="form-control bg-light" placeholder="Sélectionnez d'abord un module">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contenu</label>
                        <input type="text" name="contenu" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Niveaux</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="A1" id="niveauA1">
                                <label class="form-check-label" for="niveauA1">A1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="A2" id="niveauA2">
                                <label class="form-check-label" for="niveauA2">A2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="B1" id="niveauB1">
                                <label class="form-check-label" for="niveauB1">B1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="B2" id="niveauB2">
                                <label class="form-check-label" for="niveauB2">B2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="C1" id="niveauC1">
                                <label class="form-check-label" for="niveauC1">C1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="niveaux[]" value="C2" id="niveauC2">
                                <label class="form-check-label" for="niveauC2">C2</label>
                            </div>
                        </div>
                        <small class="text-muted">Laisser vide si aucun niveau ne s'applique</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Liste des compétences du référentiel</span>
                <div>
                    <?php 
                    $nb_filtres_actifs = 0;
                    if ($q !== '') $nb_filtres_actifs++;
                    if ($filtre_module !== '') $nb_filtres_actifs++;
                    if ($filtre_niveau !== '') $nb_filtres_actifs++;
                    ?>
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtresPanel" aria-expanded="false">
                        Filtrer
                        <?php if ($nb_filtres_actifs > 0): ?>
                            <span class="badge bg-danger"><?php echo $nb_filtres_actifs; ?></span>
                        <?php endif; ?>
                    </button>
                    <?php if ($nb_filtres_actifs > 0): ?>
                        <a href="referentiel.php" class="btn btn-sm btn-link">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="collapse mb-3" id="filtresPanel">
                <div class="card">
                    <div class="card-body" style="background-color: #f8f9fa; border-left: 4px solid #199ea3;">
                        <h6 class="mb-3"><i class="bi bi-funnel"></i> Filtres de recherche</h6>
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Recherche textuelle</label>
                                <input type="text" name="q" class="form-control form-control-sm" placeholder="Code, contenu ou module..." value="<?php echo htmlspecialchars($q); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Module</label>
                                <select name="filtre_module" class="form-select form-select-sm">
                                    <option value="">-- Tous --</option>
                                    <option value="Bases" <?php echo $filtre_module === 'Bases' ? 'selected' : ''; ?>>Bases</option>
                                    <option value="Conjugaison" <?php echo $filtre_module === 'Conjugaison' ? 'selected' : ''; ?>>Conjugaison</option>
                                    <option value="Grammaire" <?php echo $filtre_module === 'Grammaire' ? 'selected' : ''; ?>>Grammaire</option>
                                    <option value="Prononciation" <?php echo $filtre_module === 'Prononciation' ? 'selected' : ''; ?>>Prononciation</option>
                                    <option value="Methodologie" <?php echo $filtre_module === 'Methodologie' ? 'selected' : ''; ?>>Méthodologie</option>
                                    <option value="Vocabulaire" <?php echo $filtre_module === 'Vocabulaire' ? 'selected' : ''; ?>>Vocabulaire</option>
                                    <option value="Au Quotidien" <?php echo $filtre_module === 'Au Quotidien' ? 'selected' : ''; ?>>Au Quotidien</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Niveau</label>
                                <select name="filtre_niveau" class="form-select form-select-sm">
                                <option value="">-- Tous --</option>
                                <option value="A1" <?php echo $filtre_niveau === 'A1' ? 'selected' : ''; ?>>A1</option>
                                <option value="A2" <?php echo $filtre_niveau === 'A2' ? 'selected' : ''; ?>>A2</option>
                                <option value="B1" <?php echo $filtre_niveau === 'B1' ? 'selected' : ''; ?>>B1</option>
                                <option value="B2" <?php echo $filtre_niveau === 'B2' ? 'selected' : ''; ?>>B2</option>
                                <option value="C1" <?php echo $filtre_niveau === 'C1' ? 'selected' : ''; ?>>C1</option>
                                <option value="C2" <?php echo $filtre_niveau === 'C2' ? 'selected' : ''; ?>>C2</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Appliquer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
            <div class="card-body">
                <?php if (count($referentiels) > 0): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="update_all">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Module</th>
                                        <th style="width: 10%;">Code</th>
                                        <th style="width: 45%;">Contenu</th>
                                        <th style="width: 20%;">Niveaux</th>
                                        <th style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_module = '';
                                    foreach ($referentiels as $ref): 
                                        $show_module = ($current_module !== $ref['module']);
                                        $current_module = $ref['module'];
                                        $niveaux_array = $ref['niveaux'] ? explode(',', $ref['niveaux']) : [];
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="hidden" name="codes[]" value="<?php echo htmlspecialchars($ref['code']); ?>">
                                                <select name="modules[<?php echo htmlspecialchars($ref['code']); ?>]" class="form-select" required>
                                                    <option value="Bases" <?php echo $ref['module']==='Bases'?'selected':''; ?>>Bases</option>
                                                    <option value="Conjugaison" <?php echo $ref['module']==='Conjugaison'?'selected':''; ?>>Conjugaison</option>
                                                    <option value="Grammaire" <?php echo $ref['module']==='Grammaire'?'selected':''; ?>>Grammaire</option>
                                                    <option value="Prononciation" <?php echo $ref['module']==='Prononciation'?'selected':''; ?>>Prononciation</option>
                                                    <option value="Methodologie" <?php echo $ref['module']==='Methodologie'?'selected':''; ?>>Méthodologie</option>
                                                    <option value="Vocabulaire" <?php echo $ref['module']==='Vocabulaire'?'selected':''; ?>>Vocabulaire</option>
                                                    <option value="Au Quotidien" <?php echo $ref['module']==='Au Quotidien'?'selected':''; ?>>Au Quotidien</option>
                                                </select>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($ref['code']); ?></strong></td>
                                            <td>
                                                <input type="text" name="contenus[<?php echo htmlspecialchars($ref['code']); ?>]" class="form-control" value="<?php echo htmlspecialchars($ref['contenu']); ?>" required>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach(['A1','A2','B1','B2','C1','C2'] as $niveau): ?>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox" name="niveaux_sets[<?php echo htmlspecialchars($ref['code']); ?>][]" value="<?php echo $niveau; ?>" id="niveau_<?php echo htmlspecialchars($ref['code']).'_'.$niveau; ?>" <?php echo in_array($niveau, $niveaux_array) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label small" for="niveau_<?php echo htmlspecialchars($ref['code']).'_'.$niveau; ?>"><?php echo $niveau; ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm delete-ref-btn" data-code="<?php echo htmlspecialchars($ref['code']); ?>">Supprimer</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success" style="min-width: 200px;">Sauvegarder toutes les modifications</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>Aucun élément dans le référentiel pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données des derniers codes par module
        const derniersNumerosParModule = <?php echo json_encode($dernier_codes); ?>;
        
        // Auto-génération du code basé sur le module sélectionné
        document.getElementById('moduleSelect').addEventListener('change', function() {
            const module = this.value;
            const codeInput = document.getElementById('codeInput');
            
            const moduleLetters = {
                'Bases': 'B',
                'Conjugaison': 'C',
                'Grammaire': 'G',
                'Prononciation': 'P',
                'Methodologie': 'M',
                'Vocabulaire': 'V',
                'Au Quotidien': 'A'
            };
            
            if (module && moduleLetters[module]) {
                const letter = moduleLetters[module];
                const dernierNumero = derniersNumerosParModule[module] || 0;
                const prochainNumero = dernierNumero + 1;
                const nouveauCode = `${letter}-C${prochainNumero}`;
                
                codeInput.value = nouveauCode;
                codeInput.placeholder = nouveauCode;
            }
        });
        
        // Gestion des boutons de suppression
        document.querySelectorAll('.delete-ref-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (confirm('Supprimer ce contenu du référentiel ?')) {
                    var code = this.getAttribute('data-code');
                    var form = document.createElement('form');
                    form.method = 'post';
                    form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="code" value="' + code + '">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>

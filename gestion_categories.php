<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EDL - Gestion des catégories</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

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

function getDefaultPhoto($sexe) {
    if ($sexe === 'feminin') return 'pp/defaultf.png';
    if ($sexe === 'masculin') return 'pp/defaulth.jpg';
    return 'pp/default.jpg';
}

$error = '';
$success = '';

// Créer une nouvelle catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $niveau = !empty($_POST['niveau']) ? $_POST['niveau'] : null;
        $mots_cles = trim($_POST['mots_cles'] ?? '');
        
        if (empty($nom)) {
            throw new Exception('Le nom de la catégorie est obligatoire.');
        }
        
        $stmt = $pdo->prepare('INSERT INTO categories (nom, description, parent_id, mots_cles) VALUES (:nom, :description, :parent_id, :mots_cles)');
        $stmt->execute([
            'nom' => $nom,
            'description' => $description,
            'parent_id' => $parent_id,
            'mots_cles' => $mots_cles
        ]);
        
        $success = 'Catégorie créée avec succès !';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Modifier une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $id = (int)$_POST['id'];
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $mots_cles = trim($_POST['mots_cles'] ?? '');
        
        if (empty($nom)) {
            throw new Exception('Le nom de la catégorie est obligatoire.');
        }
        
        // Vérifier qu'on ne crée pas une boucle (parent = enfant)
        if ($parent_id == $id) {
            throw new Exception('Une catégorie ne peut pas être son propre parent.');
        }
        
        $stmt = $pdo->prepare('UPDATE categories SET nom = :nom, description = :description, parent_id = :parent_id, mots_cles = :mots_cles WHERE id = :id');
        $stmt->execute([
            'nom' => $nom,
            'description' => $description,
            'parent_id' => $parent_id,
            'mots_cles' => $mots_cles,
            'id' => $id
        ]);
        
        $success = 'Catégorie modifiée avec succès !';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Supprimer une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $id = (int)$_POST['id'];
        
        // Vérifier si des ressources sont liées
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ressources_categories WHERE categorie_id = :id');
        $stmt->execute(['id' => $id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception('Impossible de supprimer cette catégorie : ' . $count . ' ressource(s) y sont liées.');
        }
        
        // Vérifier si des catégories enfants existent
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = :id');
        $stmt->execute(['id' => $id]);
        $countChildren = $stmt->fetchColumn();
        
        if ($countChildren > 0) {
            throw new Exception('Impossible de supprimer cette catégorie : ' . $countChildren . ' sous-catégorie(s) en dépendent.');
        }
        
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        
        $success = 'Catégorie supprimée avec succès !';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer toutes les catégories
$categories = $pdo->query('SELECT c.*, p.nom as parent_nom FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.parent_id, c.nom')->fetchAll(PDO::FETCH_ASSOC);

// Organiser les catégories par hiérarchie
function buildCategoryTree($categories, $parentId = null) {
    $branch = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $children = buildCategoryTree($categories, $category['id']);
            if ($children) {
                $category['children'] = $children;
            }
            $branch[] = $category;
        }
    }
    return $branch;
}

$categoryTree = buildCategoryTree($categories);

// Fonction pour afficher l'arbre de catégories
function displayCategoryTree($tree, $level = 0) {
    $html = '';
    foreach ($tree as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $prefix = $level > 0 ? '└─ ' : '';
        
        $html .= '<tr>';
        $html .= '<td>' . $indent . $prefix . htmlspecialchars($category['nom']) . '</td>';
        $html .= '<td>' . htmlspecialchars($category['description'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($category['parent_nom'] ?? '-') . '</td>';

        $html .= '<td>' . (empty($category['mots_cles']) ? '-' : '<small>' . htmlspecialchars($category['mots_cles']) . '</small>') . '</td>';
        $html .= '<td>';
        $html .= '<button class="btn btn-sm btn-warning me-1" onclick="editCategory(' . $category['id'] . ')">Modifier</button>';
        $html .= '<button class="btn btn-sm btn-danger" onclick="deleteCategory(' . $category['id'] . ', \'' . htmlspecialchars(addslashes($category['nom'])) . '\')">Supprimer</button>';
        $html .= '</td>';
        $html .= '</tr>';
        
        if (isset($category['children'])) {
            $html .= displayCategoryTree($category['children'], $level + 1);
        }
    }
    return $html;
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
                    <li class="nav-item"><a class="nav-link" href="gestion_utilisateurs.php">Gestion utilisateurs</a></li>
                    <li class="nav-item"><a class="nav-link" href="upload_ressource.php">Uploader une ressource</a></li>
                    <li class="nav-item"><a class="nav-link active" href="gestion_categories.php">Gestion catégories</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        <h1 class="mb-4">Gestion des catégories</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Erreur :</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <strong>Succès :</strong> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulaire de création -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Créer une catégorie</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="createForm">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom" required maxlength="100">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Catégorie parente</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">Aucune (catégorie racine)</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Pour créer une sous-catégorie</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mots_cles" class="form-label">Mots-clés</label>
                                <input type="text" class="form-control" id="mots_cles" name="mots_cles">
                                <small class="text-muted">Séparez par des virgules</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Créer</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Liste des catégories -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Liste des catégories (<?php echo count($categories); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="alert alert-info">
                                Aucune catégorie créée pour le moment.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Description</th>
                                            <th>Parent</th>
                                            <th>Mots-clés</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo displayCategoryTree($categoryTree); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required maxlength="100">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_parent_id" class="form-label">Catégorie parente</label>
                            <select class="form-select" id="edit_parent_id" name="parent_id">
                                <option value="">Aucune (catégorie racine)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_mots_cles" class="form-label">Mots-clés</label>
                            <input type="text" class="form-control" id="edit_mots_cles" name="mots_cles" placeholder="grammaire, conjugaison, vocabulaire">
                            <small class="text-muted">Séparez par des virgules</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="deleteForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_name"></strong> ?</p>
                        <div class="alert alert-warning">
                            <strong>Attention :</strong> Cette action est irréversible.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const categoriesData = <?php echo json_encode($categories); ?>;
        
        function editCategory(id) {
            const category = categoriesData.find(c => c.id == id);
            if (!category) return;
            
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_nom').value = category.nom;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_parent_id').value = category.parent_id || '';
            document.getElementById('edit_mots_cles').value = category.mots_cles || '';
            
            // Désactiver l'option de se sélectionner comme parent
            const parentSelect = document.getElementById('edit_parent_id');
            Array.from(parentSelect.options).forEach(option => {
                option.disabled = option.value == id;
            });
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteCategory(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
    <?php include 'footer.php'; ?>

</body>
</html>

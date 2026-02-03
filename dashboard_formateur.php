<?php
session_start();
include 'connexionbdd.php';

// accès réservé aux formateurs
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] !== 'formateur' && $_SESSION['role'] !== 'admin')) {
    header('Location: index.php');
    exit;
}

$pdo = ConnexionBDD();
$current = null;
if (isset($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT prenom, nom, photo FROM utilisateurs WHERE id = :id');
    $st->execute(['id' => $_SESSION['user_id']]);
    $current = $st->fetch(PDO::FETCH_ASSOC);
}

$formateur_id = (int)($_SESSION['user_id'] ?? 0);

// récupérer les stagiaires liés à ce formateur
$stmt = $pdo->prepare('SELECT u.id, u.prenom, u.nom, u.role FROM utilisateurs u JOIN stagiaire_formateur sf ON u.id = sf.stagiaire_id WHERE sf.formateur_id = :fid ORDER BY u.prenom, u.nom');
$stmt->execute(['fid' => $formateur_id]);
$stagiaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasFPC = false;
$hasOP = false;
foreach ($stagiaires as $s) {
    if ($s['role'] === 'stagiaire FPC') $hasFPC = true;
    if ($s['role'] === 'stagiaire OP') $hasOP = true;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Formateur - EDL+</title>
    <link rel="icon" type="image/png" href="img/logo.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .placeholder-card { min-height: 120px; }
    </style>
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard_formateur.php">Espace formateur</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Mon espace formateur</h2>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_stagiaires">Stagiaires liés</div>
                    <div class="card-body">
                        <?php if (empty($stagiaires)): ?>
                            <p>Aucun stagiaire lié pour le moment.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($stagiaires as $s): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($s['role']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                    <!-- Modal: Stagiaires liés -->
                    <div class="modal fade" id="modal_stagiaires" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Stagiaires liés</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (empty($stagiaires)): ?>
                                            <p>Aucun stagiaire lié.</p>
                                    <?php else: ?>
                                            <ul class="list-group">
                                            <?php foreach ($stagiaires as $s): ?>
                                                    <li class="list-group-item d-flex align-items-center">
                                                            <div class="flex-grow-1"><?php echo htmlspecialchars($s['prenom'] . ' ' . $s['nom']); ?></div>
                                                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($s['role']); ?></span>
                                                    </li>
                                            <?php endforeach; ?>
                                            </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: FPC overview (links to FPC tools) -->
                    <div class="modal fade" id="modal_fpc_overview" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Formateur FPC — Outils</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Accès rapide aux outils FPC :</p>
                                    <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_checklist" data-bs-toggle="modal" data-bs-dismiss="modal">Checklist formateurs</button>
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_satisfaction" data-bs-toggle="modal" data-bs-dismiss="modal">Questionnaire de satisfaction</button>
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_evaluation" data-bs-toggle="modal" data-bs-dismiss="modal">Évaluation des acquis</button>
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_emargement" data-bs-toggle="modal" data-bs-dismiss="modal">Feuille d'émargement</button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: OP overview (links to OP tools) -->
                    <div class="modal fade" id="modal_op_overview" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Formateur OP — Outils</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Accès rapide aux outils OP :</p>
                                    <div class="d-grid gap-2">
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_sessions" data-bs-toggle="modal" data-bs-dismiss="modal">Liste des sessions</button>
                                            <button class="btn btn-outline-primary" data-bs-target="#modal_dossier" data-bs-toggle="modal" data-bs-dismiss="modal">Dossier de séance</button>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>

            <div class="col-md-8">
                <?php if ($hasFPC): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_fpc_overview">Formateur FPC — Tableaux et outils</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_checklist">
                                        <div class="card-body">
                                            <h6 class="card-title">Checklist formateurs</h6>
                                            <p class="card-text small text-muted">Outils et vérifications rapides</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_satisfaction">
                                        <div class="card-body">
                                            <h6 class="card-title">Questionnaire de satisfaction</h6>
                                            <p class="card-text small text-muted">Remplissable en ligne</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_evaluation">
                                        <div class="card-body">
                                            <h6 class="card-title">Évaluation des acquis</h6>
                                            <p class="card-text small text-muted">Remplissable en ligne</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-clickable h-100" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_emargement">
                                        <div class="card-body">
                                            <h6 class="card-title">Feuille d'émargement</h6>
                                            <p class="card-text small text-muted">Émargement / signature</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($hasOP): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_op_overview">Formateur OP — Sessions</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_sessions">
                                        <div class="card-body">
                                            <h6 class="card-title">Liste des sessions</h6>
                                            <p class="card-text small text-muted">Sessions OP cliquables</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card card-clickable" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#modal_dossier">
                                        <div class="card-body">
                                            <h6 class="card-title">Dossier de séance</h6>
                                            <p class="card-text small text-muted">Boîtes thématiques et documents</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$hasFPC && !$hasOP): ?>
                    <div class="card placeholder-card">
                        <div class="card-body">
                            <p>Aucune activité détectée pour ce formateur. Lorsque des stagiaires seront liés, les outils apparaîtront ici.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Modals for placeholders -->
        <div class="modal fade" id="modal_checklist" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Checklist formateurs</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Placeholder : checklist et actions rapides pour le formateur FPC.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_satisfaction" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Questionnaire de satisfaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Placeholder : formulaire de satisfaction (intégration future de Google Forms ou formulaire interne).</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_evaluation" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Évaluation des acquis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Placeholder : évaluation des acquis (à implémenter).</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_emargement" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Feuille d'émargement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Placeholder : feuille d'émargement et options de génération (PDF / émargement en distanciel).</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_sessions" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Liste des sessions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Placeholder : liste des sessions OP (clickable). Ici on affichera le planning et liens vers dossiers de séance.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_dossier" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Dossier de séance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <!-- modal content cleaned: nested modals moved outside to avoid display issues -->
                    <div class="modal-body">
                        <p>Placeholder : dossier de séance avec boîtes thématiques et ressources.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>

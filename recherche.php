<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Connexion à la base de données
function connectDB() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=l2info', 'l2info', 'l2info');
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

// Récupérer la liste des contacts avec leur première image
function getContacts() {
    $userId = $_SESSION['user_id'];
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT u.id, u.login, u.nom, u.prenom, 
               (SELECT i.id FROM images i WHERE i.id_utilisateur = u.id ORDER BY i.date_enregistrement DESC LIMIT 1) as first_image_id
        FROM utilisateurs u 
        WHERE u.id != ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction de recherche améliorée avec calcul de pertinence
function searchImagesByRelevance($keyword) {
    $pdo = connectDB();
    
    if (empty(trim($keyword))) {
        return [];
    }
    
    // Récupérer toutes les images avec leurs descriptifs
    $query = "SELECT i.*, u.prenom, u.nom FROM images i 
              JOIN utilisateurs u ON i.id_utilisateur = u.id
              WHERE i.descriptif IS NOT NULL AND i.descriptif != ''
              ORDER BY i.date_enregistrement DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $allImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traiter les mots-clés de recherche
    $searchWords = array_filter(array_map('trim', explode(' ', strtolower($keyword))));
    
    $resultsWithRelevance = [];
    
    foreach ($allImages as $image) {
        $descriptif = strtolower($image['descriptif']);
        $relevanceScore = 0;
        
        // Compter le nombre de mots-clés présents dans le descriptif
        foreach ($searchWords as $word) {
            if (!empty($word)) {
                // Compter les occurrences de chaque mot
                $occurrences = substr_count($descriptif, $word);
                $relevanceScore += $occurrences;
            }
        }
        
        // Si au moins un mot-clé est trouvé, ajouter l'image aux résultats
        if ($relevanceScore > 0) {
            $image['relevance_score'] = $relevanceScore;
            $resultsWithRelevance[] = $image;
        }
    }
    
    // Trier par pertinence décroissante
    usort($resultsWithRelevance, function($a, $b) {
        return $b['relevance_score'] - $a['relevance_score'];
    });
    
    return $resultsWithRelevance;
}

// Initialiser les variables de recherche
$searchKeyword = '';
$searchResults = [];
$isSearched = false;

// Traiter la recherche si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $isSearched = true;
    $searchKeyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    
    if (!empty($searchKeyword)) {
        $searchResults = searchImagesByRelevance($searchKeyword);
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banque d'Images - Recherche</title>
    <link rel="stylesheet" href="image.css">
    <!-- Ajout des icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar / Menu gauche -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">BANQUE D'IMAGES</h1>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="images.php" class="sidebar-link">
                    <i class="fas fa-images"></i> Mes Images
                </a>
                <a href="upload.php" class="sidebar-link">
                    <i class="fas fa-upload"></i> Déposer une image
                </a>
                <a href="recherche.php" class="sidebar-link active">
                    <i class="fas fa-search"></i> Rechercher
                </a>
                <a href="?logout=1" class="sidebar-link btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
            
            <div class="contacts-section">
                <h3 class="contacts-title">Mes Contacts</h3>
                <ul class="contacts-list">
                    <?php 
                    $contacts = getContacts();
                    foreach ($contacts as $contact): 
                    ?>
                    <li class="contact-item">
                        <div class="contact-info">
                            <?php echo htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']); ?>
                        </div>
                        <div class="contact-actions">
                            <a href="images.php?contact_id=<?php echo $contact['id']; ?>" title="Voir les images">
                                <i class="fas fa-image"></i>
                            </a>
                            <?php if ($contact['first_image_id']): ?>
                            <a href="view_image.php?id=<?php echo $contact['first_image_id']; ?>" title="Voir les commentaires">
                                <i class="fas fa-comments"></i>
                            </a>
                            <?php else: ?>
                            <span class="disabled-icon" title="Aucune image disponible">
                                <i class="fas fa-comments"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header class="page-header">
                <h2>Recherche d'images</h2>
                <p class="search-description">Recherchez des images par mots-clés dans leur description. Les résultats sont triés par pertinence.</p>
            </header>

            <div class="search-container">
                <form method="POST" action="recherche.php" class="search-form">
                    <div class="form-group">
                        <label for="keyword">Mots-clés de recherche:</label>
                        <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($searchKeyword); ?>" placeholder="Saisissez vos mots-clés (ex: paysage montagne nature)" required>
                        <small class="form-help">Séparez plusieurs mots-clés par des espaces</small>
                    </div>
                    
                    <button type="submit" name="search" class="btn-search">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </form>
            </div>

            <?php if ($isSearched): ?>
            <div class="search-results">
                <h3>Résultats de la recherche 
                    <?php if (!empty($searchKeyword)): ?>
                    pour "<?php echo htmlspecialchars($searchKeyword); ?>"
                    <?php endif; ?>
                    (<?php echo count($searchResults); ?> image<?php echo count($searchResults) > 1 ? 's' : ''; ?>)
                </h3>
                
                <?php if (empty($searchResults)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Aucune image ne correspond à vos mots-clés de recherche.</p>
                    <p class="help-text">Essayez avec d'autres mots-clés ou vérifiez l'orthographe.</p>
                </div>
                <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($searchResults as $image): ?>
                    <div class="image-card">
                        <a href="view_image.php?id=<?php echo $image['id']; ?>">
                            <img src="<?php echo htmlspecialchars($image['chemin_image']); ?>" alt="Image" class="image-thumbnail">
                        </a>
                        <div class="image-info">
                            <div class="image-relevance">
                                <span class="relevance-badge">
                                    <i class="fas fa-star"></i> Pertinence: <?php echo $image['relevance_score']; ?>
                                </span>
                            </div>
                            <p class="image-owner">
                                <i class="fas fa-user"></i> 
                                <a href="images.php?contact_id=<?php echo $image['id_utilisateur']; ?>" class="owner-link">
                                    <?php echo htmlspecialchars($image['prenom'] . ' ' . $image['nom']); ?>
                                </a>
                            </p>
                            <p class="image-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php 
                                $date = new DateTime($image['date_enregistrement']);
                                echo $date->format('d/m/Y H:i'); 
                                ?>
                            </p>
                            <?php if (!empty($image['descriptif'])): ?>
                            <p class="image-description">
                                <?php 
                                // Mettre en évidence les mots-clés trouvés dans le descriptif
                                $descriptif = htmlspecialchars($image['descriptif']);
                                if (!empty($searchKeyword)) {
                                    $searchWords = array_filter(array_map('trim', explode(' ', $searchKeyword)));
                                    foreach ($searchWords as $word) {
                                        if (!empty($word)) {
                                            $descriptif = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<mark>$1</mark>', $descriptif);
                                        }
                                    }
                                }
                                echo $descriptif;
                                ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
    .search-description {
        color: #666;
        font-size: 0.9em;
        margin-top: 0.5em;
    }

    .form-help {
        display: block;
        color: #666;
        font-size: 0.8em;
        margin-top: 0.3em;
    }

    .no-results {
        text-align: center;
        padding: 3em 1em;
        color: #666;
    }

    .no-results i {
        font-size: 3em;
        margin-bottom: 1em;
        opacity: 0.5;
    }

    .help-text {
        color: #999;
        font-size: 0.9em;
        margin-top: 0.5em;
    }

    .relevance-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 0.2em 0.5em;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 0.5em;
    }

    .relevance-badge i {
        color: #ffc107;
    }

    .owner-link {
        color: #1976d2;
        text-decoration: none;
    }

    .owner-link:hover {
        text-decoration: underline;
    }

    .image-description mark {
        background-color: #ffeb3b;
        padding: 0.1em 0.2em;
        border-radius: 2px;
        font-weight: bold;
    }

    .disabled-icon {
        opacity: 0.3;
        cursor: not-allowed;
    }
    </style>
</body>
</html>
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

// Récupérer les images de l'utilisateur connecté, triées par date décroissante
function getUserImages($userId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id_utilisateur = ? ORDER BY date_enregistrement DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer la liste des contacts avec leur première image (pour cet exemple, tous les autres utilisateurs sont considérés comme contacts)
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

// Récupérer les images d'un contact spécifique si demandé
$contactId = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : null;
$currentImages = [];

if ($contactId) {
    // Vérifier si le contact existe
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT id, login, nom, prenom FROM utilisateurs WHERE id = ?");
    $stmt->execute([$contactId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        $currentImages = getUserImages($contactId);
        $pageTitle = "Images de " . htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']);
    } else {
        header('Location: images.php');
        exit;
    }
} else {
    // Afficher les images de l'utilisateur connecté
    $currentImages = getUserImages($_SESSION['user_id']);
    $pageTitle = "Mes Images";
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
    <title>Banque d'Images - <?php echo $pageTitle; ?></title>
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
                <a href="images.php" class="sidebar-link active">
                    <i class="fas fa-images"></i> Mes Images
                </a>
                <a href="upload.php" class="sidebar-link">
                    <i class="fas fa-upload"></i> Déposer une image
                </a>
                <a href="recherche.php" class="sidebar-link">
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
                <h2><?php echo $pageTitle; ?></h2>
            </header>

            <div class="images-grid">
                <?php if (empty($currentImages)): ?>
                <p class="no-images">Aucune image n'a été trouvée.</p>
                <?php else: ?>
                <?php foreach ($currentImages as $image): ?>
                <div class="image-card">
                    <a href="view_image.php?id=<?php echo $image['id']; ?>">
                        <img src="<?php echo htmlspecialchars($image['chemin_image']); ?>" alt="Image" class="image-thumbnail">
                    </a>
                    <div class="image-info">
                        <p class="image-date">
                            <?php 
                            $date = new DateTime($image['date_enregistrement']);
                            echo $date->format('d/m/Y H:i'); 
                            ?>
                        </p>
                        <?php if (!empty($image['descriptif'])): ?>
                        <p class="image-description"><?php echo htmlspecialchars($image['descriptif']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
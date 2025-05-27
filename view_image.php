<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Vérifier si un ID d'image est spécifié
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: images.php');
    exit;
}

$imageId = (int)$_GET['id'];

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

// Récupérer les contacts
function getContacts() {
    $userId = $_SESSION['user_id'];
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT id, login, nom, prenom FROM utilisateurs WHERE id != ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les détails de l'image
function getImageDetails($imageId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT i.*, u.nom, u.prenom 
        FROM images i
        JOIN utilisateurs u ON i.id_utilisateur = u.id
        WHERE i.id = ?
    ");
    $stmt->execute([$imageId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les commentaires pour cette image
function getImageComments($imageId) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT c.*, u.nom, u.prenom 
        FROM commentaires c
        JOIN utilisateurs u ON c.id_utilisateur = u.id
        WHERE c.id_image = ?
        ORDER BY c.date_commentaire ASC
    ");
    $stmt->execute([$imageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ajouter un commentaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commentaire'])) {
    $commentaire = trim($_POST['commentaire']);
    
    if (!empty($commentaire)) {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("INSERT INTO commentaires (id_image, id_utilisateur, texte) VALUES (?, ?, ?)");
            $stmt->execute([$imageId, $_SESSION['user_id'], $commentaire]);
            
            // Rafraîchir la page pour voir le nouveau commentaire
            header("Location: view_image.php?id=$imageId");
            exit;
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du commentaire: " . $e->getMessage();
        }
    } else {
        $message = "Le commentaire ne peut pas être vide.";
    }
}

// Récupérer les informations de l'image
$image = getImageDetails($imageId);

// Si l'image n'existe pas, rediriger
if (!$image) {
    header('Location: images.php');
    exit;
}

// Récupérer les commentaires
$comments = getImageComments($imageId);

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
    <title>Banque d'Images - Voir l'image</title>
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
                            <a href="view_image.php?user_id=<?php echo $contact['id']; ?>" title="Voir les commentaires">
                                <i class="fas fa-comments"></i>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <header class="page-header">
                <h2>Image de <?php echo htmlspecialchars($image['prenom'] . ' ' . $image['nom']); ?></h2>
                <p class="image-date">
                    Publiée le <?php 
                    $date = new DateTime($image['date_enregistrement']);
                    echo $date->format('d/m/Y à H:i'); 
                    ?>
                </p>
            </header>

            <?php if ($message): ?>
            <div class="message error">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="image-view">
                <img src="<?php echo htmlspecialchars($image['chemin_image']); ?>" alt="Image en grand format" class="full-image">
                
                <?php if (!empty($image['descriptif'])): ?>
                <div class="image-description">
                    <p><?php echo htmlspecialchars($image['descriptif']); ?></p>
                </div>
                <?php endif; ?>

                <div class="comments-section">
                    <h3>Commentaires (<?php echo count($comments); ?>)</h3>
                    
                    <?php if (empty($comments)): ?>
                    <p class="no-comments">Aucun commentaire pour le moment.</p>
                    <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?></span>
                                <span class="comment-date">
                                    <?php 
                                    $commentDate = new DateTime($comment['date_commentaire']);
                                    echo $commentDate->format('d/m/Y H:i'); 
                                    ?>
                                </span>
                            </div>
                            <div class="comment-content">
                                <?php echo htmlspecialchars($comment['texte']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comment-form">
                        <h4>Ajouter un commentaire</h4>
                        <form method="post">
                            <div class="form-group">
                                <textarea name="commentaire" rows="3" placeholder="Votre commentaire..." required></textarea>
                            </div>
                            <button type="submit" class="btn">Publier</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
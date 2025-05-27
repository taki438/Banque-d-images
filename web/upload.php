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

// Récupérer les contacts avec leur première image
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

$message = '';
$uploadDir = 'uploads/';

// Créer le répertoire de téléchargement s'il n'existe pas
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Traitement du téléchargement d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tempFile = $_FILES['image']['tmp_name'];
        $fileType = $_FILES['image']['type'];
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        $descriptif = isset($_POST['descriptif']) ? trim($_POST['descriptif']) : '';
        
        // Vérifier que le fichier est bien une image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            $message = "Seuls les formats JPEG, PNG et GIF sont acceptés.";
        } else {
            // Déplacer le fichier temporaire vers le répertoire cible
            if (move_uploaded_file($tempFile, $targetFile)) {
                // Enregistrer les informations dans la base de données
                try {
                    $pdo = connectDB();
                    $stmt = $pdo->prepare("INSERT INTO images (id_utilisateur, chemin_image, descriptif) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $targetFile, $descriptif]);
                    
                    $message = "Image téléchargée avec succès !";
                    // Rediriger vers la page d'images après un court délai
                    header("Refresh: 2; URL=images.php");
                } catch (PDOException $e) {
                    $message = "Erreur lors de l'enregistrement dans la base de données: " . $e->getMessage();
                }
            } else {
                $message = "Erreur lors du téléchargement de l'image.";
            }
        }
    } else {
        $message = "Veuillez sélectionner une image.";
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
    <title>Banque d'Images - Déposer une image</title>
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
                <a href="upload.php" class="sidebar-link active">
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
                <h2>Déposer une nouvelle image</h2>
            </header>

            <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="upload-form">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="image">Sélectionner une image</label>
                        <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/gif" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descriptif">Description (optionnelle)</label>
                        <textarea id="descriptif" name="descriptif" rows="4" placeholder="Décrivez votre image..."></textarea>
                    </div>
                    
                    <div class="preview-container">
                        <img id="image-preview" src="#" alt="Aperçu de l'image" style="display: none; max-width: 100%; max-height: 300px;">
                    </div>
                    
                    <button type="submit" class="btn">Télécharger l'image</button>
                </form>
            </div>
        </main>
    </div>

    <script>
    // Aperçu de l'image avant téléchargement
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        const file = e.target.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        if (file) {
            reader.readAsDataURL(file);
        }
    });
    </script>

    <style>
    .disabled-icon {
        opacity: 0.3;
        cursor: not-allowed;
    }
    </style>
</body>
</html>
<?php
session_start();
$message = '';

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

// Traitement de l'inscription
if (isset($_POST['inscription'])) {
    $login = trim($_POST['login_inscription']);
    $mdp = trim($_POST['mdp_inscription']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);

    // Validation des données
    if (empty($login) || empty($mdp) || empty($nom) || empty($prenom)) {
        $message = 'Tous les champs sont obligatoires';
    } else {
        try {
            $pdo = connectDB();
            
            // Vérifier si le login existe déjà
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($stmt->rowCount() > 0) {
                $message = 'Ce login existe déjà';
            } else {
                // Hachage du mot de passe
                $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
                
                // Insertion dans la base de données
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (login, mot_de_passe, nom, prenom) VALUES (?, ?, ?, ?)");
                $stmt->execute([$login, $mdp_hash, $nom, $prenom]);
                
                $message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            }
        } catch (PDOException $e) {
            $message = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Traitement de la connexion
if (isset($_POST['connexion'])) {
    $login = trim($_POST['login_connexion']);
    $mdp = trim($_POST['mdp_connexion']);
    
    // Validation des données
    if (empty($login) || empty($mdp)) {
        $message = 'Veuillez remplir tous les champs';
    } else {
        try {
            $pdo = connectDB();
            
            // Vérifier si le login existe
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($mdp, $user['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                
                // Redirection vers la page principale
                header('Location: images.php');
                exit;
            } else {
                $message = 'Login ou mot de passe incorrect';
            }
        } catch (PDOException $e) {
            $message = 'Erreur: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banque d'Images - Connexion</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo-container">
                <h1>BANQUE<br>D'IMAGES</h1>
                <p class="subtitle">Inscription/Connexion</p>
            </div>
        </div>

        <div class="right-panel">
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="tabs">
                <button id="tab-inscription" class="tab active">Inscription</button>
                <button id="tab-connexion" class="tab">Connexion</button>
            </div>

            <div id="form-inscription" class="form active">
                <h2>Inscription</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="login_inscription">Login</label>
                        <input type="text" id="login_inscription" name="login_inscription" required>
                    </div>
                    <div class="form-group">
                        <label for="mdp_inscription">Mot de passe</label>
                        <input type="password" id="mdp_inscription" name="mdp_inscription" required>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>
                    <button type="submit" name="inscription" class="btn">S'inscrire</button>
                </form>
            </div>

            <div id="form-connexion" class="form">
                <h2>Connexion</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="login_connexion">Login</label>
                        <input type="text" id="login_connexion" name="login_connexion" required>
                    </div>
                    <div class="form-group">
                        <label for="mdp_connexion">Mot de passe</label>
                        <input type="password" id="mdp_connexion" name="mdp_connexion" required>
                    </div>
                    <button type="submit" name="connexion" class="btn">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
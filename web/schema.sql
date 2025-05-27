-- Création de la base de données
CREATE DATABASE IF NOT EXISTS l2info DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE l2info;

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
);

-- Table des images
CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    chemin_image VARCHAR(255) NOT NULL,
    descriptif TEXT,
    date_enregistrement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table des commentaires
CREATE TABLE commentaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_image INT NOT NULL,
    texte TEXT NOT NULL,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_image) REFERENCES images(id) ON DELETE CASCADE
);

-- Table des contacts (relations entre utilisateurs)
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_contact INT NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_contact) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE (id_utilisateur, id_contact)
);

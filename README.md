# Banque d'Images - Site Web

## Description

Ce projet est un site web de **banque d'images** développé en **PHP**, permettant aux utilisateurs de s'inscrire, de se connecter, de déposer des images et de les commenter.

##  Fonctionnalités principales

### Inscription / Connexion
- Formulaire d’inscription : login, mot de passe, nom, prénom, email.
- Connexion par login et mot de passe.
- Gestion sécurisée des sessions.

### Page d’images
- Affichage des images de l’utilisateur, triées par date décroissante.
- Barre latérale contenant :
  - Mes image
  - Déposer une image 
  - Déconnexion
  - Liens vers la recherche et le dépôt d’image
  - Liste des contacts avec :
    -  Une icon pour voir les images d’un contact avec leurs commentaire

- Visualisation en grand d’une image avec :
  - Commentaires des utilisateurs (ordre chronologique)
  - Formulaire d’ajout de commentaire

### Dépôt d’image
- Téléversement d’image avec description
- Sauvegarde de la date d’enregistrement

### Recherche
- Saisie de mots-clés
- Résultats classés par pertinence (nombre de mots présents dans le descriptif)
- Visualisation en grand 

## Technologies utilisées

- **Langage principal :** PHP
- **Base de données :** MySQL
- **Frontend :** HTML, CSS, JavaScript
- **Stockage des images :** Répertoire local (`uploads/`)
- **Sessions PHP** pour la gestion des connexions

## Structure du projet

```bash
/
├── index.php               # Page principale (inscription / connexion) 
├── images.php              # Page des images personnelles
├── upload.php              # Formulaire et traitement du dépôt d'image
├── recherche.php           # Page de recherche
├── view_image.php          # Affichage d’une image avec ses commentaires
├── includes/               # Fichiers réutilisables (connexion BDD, fonctions)
├── styles.css              # Fichier du style de l'index
├── image.css               # Fichier contenant le style du image , upload, recherche, view image
├── uploads/                # Répertoire des images uploadées
├── schema.sql              # Script de création et de gestion de la base
└── README.md               # Ce fichier


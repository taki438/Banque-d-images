document.addEventListener('DOMContentLoaded', function() {
    // Gestion des onglets
    const tabInscription = document.getElementById('tab-inscription');
    const tabConnexion = document.getElementById('tab-connexion');
    const formInscription = document.getElementById('form-inscription');
    const formConnexion = document.getElementById('form-connexion');

    // Affichage de l'onglet Inscription
    tabInscription.addEventListener('click', function() {
        tabInscription.classList.add('active');
        tabConnexion.classList.remove('active');
        formInscription.classList.add('active');
        formConnexion.classList.remove('active');
    });

    // Affichage de l'onglet Connexion
    tabConnexion.addEventListener('click', function() {
        tabConnexion.classList.add('active');
        tabInscription.classList.remove('active');
        formConnexion.classList.add('active');
        formInscription.classList.remove('active');
    });

    // Validation des formulaires
    const formInscriptionEl = formInscription.querySelector('form');
    const formConnexionEl = formConnexion.querySelector('form');

    if (formInscriptionEl) {
        formInscriptionEl.addEventListener('submit', function(e) {
            const login = document.getElementById('login_inscription').value.trim();
            const mdp = document.getElementById('mdp_inscription').value.trim();
            const nom = document.getElementById('nom').value.trim();
            const prenom = document.getElementById('prenom').value.trim();

            if (!login || !mdp || !nom || !prenom) {
                e.preventDefault();
                alert('Tous les champs sont obligatoires');
            }
        });
    }

    if (formConnexionEl) {
        formConnexionEl.addEventListener('submit', function(e) {
            const login = document.getElementById('login_connexion').value.trim();
            const mdp = document.getElementById('mdp_connexion').value.trim();

            if (!login || !mdp) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
            }
        });
    }
    
    // Animer les messages d'erreur/succès
    const message = document.querySelector('.message');
    if (message) {
        // Ajouter une légère animation
        message.style.animation = 'fadeIn 0.5s';
        
        // Faire disparaître le message après 5 secondes
        setTimeout(() => {
            message.style.animation = 'fadeOut 0.5s forwards';
        }, 5000);
    }
});

// Animation pour les messages
document.head.insertAdjacentHTML('beforeend', `
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
    </style>
`);
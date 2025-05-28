document.addEventListener('DOMContentLoaded', () => {
    const logoutLink = document.querySelector('.btn-logout');

    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                e.preventDefault();
            }
        });
    }
});

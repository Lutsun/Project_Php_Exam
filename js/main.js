// assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // 1. Effets d'interaction modernes
    const interactiveElements = document.querySelectorAll(
        '.command-button, .nav-link, .auth-button, .custom-select'
    );

    interactiveElements.forEach(el => {
        // Effet au survol
        el.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(156, 39, 176, 0.3)';
        });

        // Réinitialisation
        el.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });

        // Feedback au clic
        el.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(1px)';
        });

        el.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-2px)';
        });
    });

    // 2. Animation des cartes du dashboard
    const animateCards = () => {
        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `all 0.4s ease ${index * 0.1}s`;
            
            // Déclenche l'animation après un léger délai
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    };

    // 3. Gestion améliorée des selects
    const customSelects = document.querySelectorAll('.custom-select');
    customSelects.forEach(select => {
        const selectEl = select.querySelector('select');
        
        selectEl.addEventListener('focus', () => {
            select.classList.add('focused');
        });

        selectEl.addEventListener('blur', () => {
            select.classList.remove('focused');
        });

        // Mise à jour visuelle quand une option est sélectionnée
        selectEl.addEventListener('change', function() {
            if (this.value) {
                select.classList.add('has-value');
            } else {
                select.classList.remove('has-value');
            }
        });
    });

    // Exécute les animations
    animateCards();

    // 4. Optimisation des performances
    let timeout;
    window.addEventListener('resize', function() {
        clearTimeout(timeout);
        timeout = setTimeout(animateCards, 100);
    });
});
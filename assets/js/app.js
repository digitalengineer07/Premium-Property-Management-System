/**
 * Global App Logic for Rent Manager
 * Handles theme toggling and common UI interactions
 */
(function () {
    // 1. Theme Toggling
    const themeToggle = document.getElementById('themeToggle');

    // Sync UI with state
    if (localStorage.getItem('theme') !== 'light') {
        document.documentElement.classList.add('dark-theme');
        if (themeToggle) {
            themeToggle.classList.replace('bx-moon', 'bx-sun');
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark-theme');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            if (isDark) {
                themeToggle.classList.replace('bx-moon', 'bx-sun');
            } else {
                themeToggle.classList.replace('bx-sun', 'bx-moon');
            }
        });
    }

    // 2. Animate elements on scroll if needed (placeholder for future use)
    const animates = document.querySelectorAll('.animate-up');
    animates.forEach((el, i) => {
        el.style.animationDelay = (i * 0.05) + 's';
    });
})();

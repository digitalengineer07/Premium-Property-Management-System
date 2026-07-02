/**
 * Global App Logic for Rent Manager
 * Handles theme toggling and common UI interactions
 */
(function () {
if (typeof window.toggleTheme !== 'function') {
        window.toggleTheme = function() {
            const isDark = !document.documentElement.classList.contains('dark-theme');
            document.documentElement.classList.toggle('dark-theme', isDark);
            if (document.body) document.body.classList.toggle('dark-theme', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            document.querySelectorAll('.bx-moon, .bx-sun').forEach(icon => {
                if (icon.closest('#themeToggle') || icon.closest('.icon-btn') || icon.id === 'themeToggle') {
                    icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
                }
            });
        };
    }

    const isDark = localStorage.getItem('theme') === 'dark';
    document.documentElement.classList.toggle('dark-theme', isDark);
    if (document.body) document.body.classList.toggle('dark-theme', isDark);

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle && !themeToggle.getAttribute('onclick')) {
        themeToggle.addEventListener('click', window.toggleTheme);
    }

    // 2. Animate elements on scroll if needed (placeholder for future use)
    const animates = document.querySelectorAll('.animate-up');
    animates.forEach((el, i) => {
        el.style.animationDelay = (i * 0.05) + 's';
    });
})();

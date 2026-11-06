/**
 * ASLAB REPORT - THEME MANAGER
 * Menangani Mode Gelap (Dark Mode) & Mode Terang (Light Mode)
 */

(function initThemeImmediately() {
    const savedTheme = localStorage.getItem('aslab_theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', initialTheme);
})();

document.addEventListener('DOMContentLoaded', function () {
    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');

    function updateIcons(theme) {
        document.querySelectorAll('.theme-icon').forEach(icon => {
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    }

    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    updateIcons(currentTheme);

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const now = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const next = now === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('aslab_theme', next);
            updateIcons(next);

            // Kirim event kustom agar Three.js 3D Avatar & Canvas bisa menyesuaikan warna pencahayaan
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next } }));
        });
    });
});


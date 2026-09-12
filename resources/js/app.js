import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.toggleTheme = function () {
    const dark = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
};

Alpine.start();

import Alpine from 'alpinejs';
import 'plyr/dist/plyr.css';
import { mountPlayers } from './player';

window.Alpine = Alpine;

window.toggleTheme = function () {
    const dark = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
};

// Each code line becomes its own bidi paragraph (LTR), so a Persian comment at the
// end of a line renders right-to-left inside the line without shuffling the line itself.
function isolateCodeLines() {
    document.querySelectorAll('.prose-fa pre code').forEach((code) => {
        if (code.dataset.isolated) return;
        const lines = code.textContent.replace(/\n$/, '').split('\n');
        code.textContent = '';
        lines.forEach((line) => {
            const span = document.createElement('span');
            span.className = 'code-line';
            span.textContent = line === '' ? ' ' : line;
            code.appendChild(span);
        });
        code.dataset.isolated = '1';
    });
}
document.addEventListener('DOMContentLoaded', () => { isolateCodeLines(); mountPlayers(); });

Alpine.start();

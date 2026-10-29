import './bootstrap';
import { initTheme, toggleTheme, currentTheme } from './theme-manager';
import { initNav } from './nav';

// Garante o tema aplicado (o <head> já fez isso antes do paint; aqui reforça)
initTheme();

document.addEventListener('DOMContentLoaded', () => {
    initNav();

    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    // Sol (visível no tema escuro, p/ voltar ao claro) e lua (visível no claro)
    const SOL =
        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>';
    const LUA =
        '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>';

    const paint = () => {
        const dark = currentTheme() === 'dark';
        toggle.innerHTML = dark ? SOL : LUA;
        toggle.title = dark ? 'Usar tema claro' : 'Usar tema escuro';
    };
    paint();

    toggle.addEventListener('click', () => {
        toggleTheme();
        paint();
    });
});

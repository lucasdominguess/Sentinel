import './bootstrap';
import { initTheme, toggleTheme, currentTheme } from './theme-manager';
import { initNav } from './nav';

// Garante o tema aplicado (o <head> já fez isso antes do paint; aqui reforça)
initTheme();

document.addEventListener('DOMContentLoaded', () => {
    initNav();

    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    const paint = () => {
        toggle.textContent = currentTheme() === 'dark' ? '☀️' : '🌙';
    };
    paint();

    toggle.addEventListener('click', () => {
        toggleTheme();
        paint();
    });
});

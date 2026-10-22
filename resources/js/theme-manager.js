// theme-manager.js — alternância e persistência de tema (dark/light)
const STORAGE_KEY = 'sentinel-theme';

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function stored() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
}

export function currentTheme() {
    return document.documentElement.getAttribute('data-theme') || stored() || systemTheme();
}

// Aplica o tema salvo (ou o do sistema) no carregamento
export function initTheme() {
    apply(stored() || systemTheme());
}

// Alterna e persiste; retorna o novo tema
export function toggleTheme() {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    apply(next);
    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch {
        /* localStorage indisponível (ex.: aba anônima) — segue sem persistir */
    }
    return next;
}

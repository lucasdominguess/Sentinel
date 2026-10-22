// nav.js — abre/fecha o menu lateral (drawer) do layout
export function initNav() {
    const toggle = document.getElementById('nav-toggle');
    const fechar = document.getElementById('nav-close');
    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('overlay');

    if (!toggle || !drawer || !overlay) return;

    const abrir = () => {
        drawer.classList.add('is-open');
        overlay.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    };

    const fechand = () => {
        drawer.classList.remove('is-open');
        overlay.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    toggle.addEventListener('click', abrir);
    fechar?.addEventListener('click', fechand);
    overlay.addEventListener('click', fechand);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fechand();
    });
}

// Sentinel — JS mínimo, sem dependências. Progressive enhancement sobre Blade.
(() => {
  const root = document.documentElement;

  // Tema
  document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      localStorage.setItem('sentinel-theme', next);
      btn.setAttribute('aria-label', next === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
    });
  });

  // Drawer
  const drawer = document.getElementById('drawer');
  let lastFocus = null;
  const open = () => {
    lastFocus = document.activeElement;
    drawer.classList.add('is-open'); drawer.removeAttribute('inert');
    document.body.classList.add('is-locked');
    drawer.querySelector('[data-drawer-close]')?.focus();
  };
  const close = () => {
    drawer.classList.remove('is-open'); drawer.setAttribute('inert', '');
    document.body.classList.remove('is-locked');
    lastFocus?.focus();
  };
  if (drawer) {
    document.querySelectorAll('[data-drawer-open]').forEach(b => b.addEventListener('click', open));
    drawer.querySelectorAll('[data-drawer-close]').forEach(b => b.addEventListener('click', close));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && drawer.classList.contains('is-open')) close(); });
    matchMedia('(min-width: 1024px)').addEventListener('change', e => e.matches && close());
  }

  // Busca: filtro ao vivo (o form GET continua funcionando sem JS)
  const input = document.querySelector('[data-filter]');
  if (input) {
    const items = [...document.querySelectorAll('[data-search]')];
    const empty = document.querySelector('[data-empty]');
    const term = document.querySelector('[data-empty-term]');
    const meta = document.querySelector('[data-count]');
    const run = () => {
      const q = input.value.trim().toLowerCase();
      let n = 0;
      items.forEach(el => { const hit = !q || el.dataset.search.includes(q); el.hidden = !hit; if (hit && el.closest('.asset-grid')) n++; });
      if (empty) { empty.hidden = n > 0; if (term) term.textContent = input.value.trim(); }
      if (meta) meta.textContent = `${n} de ${meta.dataset.total} ativos`;
    };
    input.addEventListener('input', run);
    document.querySelector('[data-filter-clear]')?.addEventListener('click', () => { input.value = ''; run(); input.focus(); });
    document.addEventListener('keydown', e => {
      if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) { e.preventDefault(); input.focus(); }
    });
  }

  // Flash ao atualizar preço: chame window.sentinelFlash(ticker, 'up'|'down') após um refresh parcial
  window.sentinelFlash = (ticker, dir) => {
    document.querySelectorAll(`[data-ticker="${ticker}"] .price`).forEach(el => {
      el.classList.remove('flash-up', 'flash-down'); void el.offsetWidth; el.classList.add(`flash-${dir}`);
    });
  };
})();

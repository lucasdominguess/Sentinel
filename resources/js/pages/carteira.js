// Meus ativos: filtro por classe (abas), ordenação (select) da grade e da lista densa,
// e agrupamento do donut (Ativo / Classe). Sem JS, a grade continua visível na ordem
// padrão (maior posição). O alternador grade/lista fica no sistema (sentinel.js).
(() => {
  const grid = document.getElementById('grid');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('.holding-card'));
  const table = document.querySelector('.asset-table');
  const rows = table ? Array.from(table.querySelectorAll('.holding-row:not(.asset-row--head)')) : [];
  const tabs = document.querySelectorAll('[data-classe]');
  const order = document.querySelector('[data-order]');
  const meta = document.querySelector('[data-count-pos]');

  const num = (el, attr) => {
    const v = parseFloat(el.dataset[attr]);
    return Number.isNaN(v) ? null : v;
  };
  const comparadores = {
    posicao: (a, b) => (num(b, 'posicao')     ?? -Infinity) - (num(a, 'posicao')     ?? -Infinity),
    pior:    (a, b) => (num(a, 'valorizacao') ??  Infinity) - (num(b, 'valorizacao') ??  Infinity),
    melhor:  (a, b) => (num(b, 'valorizacao') ?? -Infinity) - (num(a, 'valorizacao') ?? -Infinity),
    az:      (a, b) => (a.dataset.ticker || '').localeCompare(b.dataset.ticker || ''),
  };
  const ordenar = (chave) => {
    const cmp = comparadores[chave] || comparadores.posicao;
    cards.slice().sort(cmp).forEach((card) => grid.appendChild(card));
    rows.slice().sort(cmp).forEach((row) => table.appendChild(row));
  };
  const filtrar = (classe) => {
    let n = 0;
    [...cards, ...rows].forEach((el) => {
      const hit = !classe || el.dataset.tipo === classe;
      el.hidden = !hit;
      if (hit && el.classList.contains('holding-card')) n++;
    });
    if (meta) meta.textContent = `${n} ${n === 1 ? 'posição' : 'posições'}`;
  };

  order?.addEventListener('change', () => ordenar(order.value));
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.setAttribute('aria-pressed', 'false'));
      tab.setAttribute('aria-pressed', 'true');
      filtrar(tab.dataset.classe);
    });
  });

  // Donut: alterna entre alocação por ativo e por classe
  const groups = document.querySelectorAll('[data-group]');
  groups.forEach((btn) => {
    btn.addEventListener('click', () => {
      groups.forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
      document.querySelectorAll('[data-group-pane]').forEach((pane) => {
        pane.hidden = pane.dataset.groupPane !== btn.dataset.group;
      });
    });
  });
})();

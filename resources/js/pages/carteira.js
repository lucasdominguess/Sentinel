// Meus ativos: ordenação client-side das posições (pills). Sem JS, a grade continua
// visível na ordem padrão (maior posição). O realce do donut fica no próprio componente.
(() => {
  const grid = document.getElementById('grid');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('.holding-card'));
  const pills = document.querySelectorAll('[data-sort]');

  const num = (card, attr) => {
    const v = parseFloat(card.dataset[attr]);
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
  };

  pills.forEach((pill) => {
    pill.addEventListener('click', () => {
      pills.forEach((p) => p.setAttribute('aria-pressed', 'false'));
      pill.setAttribute('aria-pressed', 'true');
      ordenar(pill.dataset.sort);
    });
  });
})();

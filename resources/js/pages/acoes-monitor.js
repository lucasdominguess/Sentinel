// Lista de ativos monitorados: ordenação client-side (pills) + tooltip de mín/máx do mês.
// A busca ao vivo fica no sistema (sentinel.js). Progressive enhancement: sem JS,
// a grade continua visível e navegável.
(() => {
  const grid = document.getElementById('grid');
  if (!grid) return;

  const cards = Array.from(grid.querySelectorAll('.asset-card'));
  const pills = document.querySelectorAll('[data-sort]');

  // --- Ordenação ---
  const num = (card, attr) => {
    const v = parseFloat(card.dataset[attr]);
    return Number.isNaN(v) ? null : v;
  };
  const comparadores = {
    alta:  (a, b) => (num(b, 'variacao') ?? -Infinity) - (num(a, 'variacao') ?? -Infinity),
    queda: (a, b) => (num(a, 'variacao') ??  Infinity) - (num(b, 'variacao') ??  Infinity),
    valor: (a, b) => (num(b, 'preco')    ?? -Infinity) - (num(a, 'preco')    ?? -Infinity),
    menor: (a, b) => (num(a, 'preco')    ??  Infinity) - (num(b, 'preco')    ??  Infinity),
  };
  const ordenar = (chave) => {
    const cmp = comparadores[chave] || comparadores.alta;
    // reanexa na nova ordem; o estado hidden (busca) é preservado
    cards.slice().sort(cmp).forEach((card) => grid.appendChild(card));
  };
  pills.forEach((pill) => {
    pill.addEventListener('click', () => {
      pills.forEach((p) => p.setAttribute('aria-pressed', 'false'));
      pill.setAttribute('aria-pressed', 'true');
      ordenar(pill.dataset.sort);
    });
  });

  // --- Tooltip de mín/máx do mês (hover ~1s) ---
  const tip = document.createElement('div');
  tip.className = 'card-tooltip';
  document.body.appendChild(tip);

  const brl = (v) => {
    const n = parseFloat(v);
    return Number.isNaN(n) ? '—' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  };
  const fmtData = (iso) => {
    if (!iso) return '';
    const [, m, d] = iso.split('-');
    return d && m ? `${d}/${m}` : '';
  };

  let timer = null;
  let last = null;
  const posicionar = (e) => {
    const pad = 14;
    const r = tip.getBoundingClientRect();
    let x = e.clientX + pad;
    let y = e.clientY + pad;
    if (x + r.width > window.innerWidth) x = e.clientX - r.width - pad;
    if (y + r.height > window.innerHeight) y = e.clientY - r.height - pad;
    tip.style.left = `${x}px`;
    tip.style.top = `${y}px`;
  };
  const mostrar = (card) => {
    const min = card.dataset.minMes;
    const max = card.dataset.maxMes;
    const dMin = fmtData(card.dataset.minMesData);
    const dMax = fmtData(card.dataset.maxMesData);
    tip.innerHTML = (!min && !max)
      ? '<span class="tt-empty">Sem histórico do mês</span>'
      : '<div class="tt-title">No mês</div>'
        + `<div class="tt-row"><span>Mínima</span><strong>${brl(min)}${dMin ? ` <em>${dMin}</em>` : ''}</strong></div>`
        + `<div class="tt-row"><span>Máxima</span><strong>${brl(max)}${dMax ? ` <em>${dMax}</em>` : ''}</strong></div>`;
    tip.classList.add('show');
    if (last) posicionar(last);
  };
  const esconder = () => { clearTimeout(timer); tip.classList.remove('show'); };

  cards.forEach((card) => {
    card.addEventListener('mouseenter', (e) => { last = e; timer = setTimeout(() => mostrar(card), 1000); });
    card.addEventListener('mousemove', (e) => { last = e; if (tip.classList.contains('show')) posicionar(e); });
    card.addEventListener('mouseleave', esconder);
  });
})();

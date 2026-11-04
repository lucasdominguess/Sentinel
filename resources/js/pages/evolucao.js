// Evolução do patrimônio: crosshair + tooltip do gráfico. O ponteiro escolhe o pregão
// mais próximo no eixo X (não precisa acertar a linha); as setas fazem o mesmo pelo teclado.
// Sem JS, o gráfico e a tabela "Ver valores por pregão" continuam completos.
(() => {
  const chart = document.querySelector('[data-evo-chart]');
  if (!chart) return;

  const pontos = JSON.parse(chart.dataset.points || '[]');
  if (pontos.length < 2) return;

  const canvas = chart.querySelector('.evo-chart__canvas');
  const hit = chart.querySelector('[data-evo-hit]');
  const cross = chart.querySelector('[data-evo-cross]');
  const dot = chart.querySelector('[data-evo-dot]');
  const tip = chart.querySelector('[data-evo-tip]');

  const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const sinal = (v) => (v > 0.004 ? '+' : v < -0.004 ? '−' : '');
  const rs = (v) => `${sinal(v)}${brl(Math.abs(v))}`;
  const pct = (v) => `${sinal(v)}${Math.abs(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
  const dataBr = (s) => s.split('-').reverse().join('/');

  // Posição de cada pregão (em % do plot) lida do próprio path desenhado pelo servidor
  const path = canvas.querySelector('.evo-chart__valor');
  const svg = canvas.querySelector('svg');
  const vb = svg.viewBox.baseVal;
  const coords = (path.getAttribute('d') || '').match(/[ML][\d.\-]+ [\d.\-]+/g)?.map((seg) => {
    const [x, y] = seg.slice(1).split(' ').map(Number);
    return { x: x / vb.width * 100, y: y / vb.height * 100 };
  }) || [];

  let atual = -1;

  function linha(rotulo, valor, chave) {
    const row = document.createElement('div');
    row.className = 'evo-tip__row';
    const l = document.createElement('span');
    if (chave) {
      const k = document.createElement('span');
      k.className = `evo-legend__key evo-legend__key--${chave}`;
      l.append(k);
    }
    l.append(document.createTextNode(rotulo));
    const v = document.createElement('strong');
    v.textContent = valor;
    row.append(l, v);
    return row;
  }

  function mostrar(i) {
    if (i === atual) return;
    atual = i;
    const p = pontos[i];
    const c = coords[i];
    cross.hidden = dot.hidden = tip.hidden = false;
    cross.style.left = `${c.x}%`;
    dot.style.left = `${c.x}%`;
    dot.style.top = `${c.y}%`;

    tip.replaceChildren();
    const d = document.createElement('div');
    d.className = 'evo-tip__date';
    d.textContent = p.base ? `${dataBr(p.data)} · ponto de partida` : dataBr(p.data);
    tip.append(d, linha('Valor de mercado', brl(p.valor), 'valor'), linha('Custo da posição', brl(p.custo), 'custo'));
    const res = linha('Resultado', rs(p.valor - p.custo));
    res.classList.add('evo-tip__sep');
    tip.append(res);
    if (!p.base) tip.append(linha('Rentab. acumulada', pct(p.rentabilidade)));
    if (p.aportes > 0) tip.append(linha('Compras no dia', brl(p.aportes)));
    if (p.resgates > 0) tip.append(linha('Vendas no dia', brl(p.resgates)));

    // tooltip do lado oposto ao ponteiro para não cobrir a linha
    const w = canvas.clientWidth;
    const px = c.x / 100 * w;
    const tw = tip.offsetWidth;
    tip.style.left = `${px + 14 + tw > w ? Math.max(px - 14 - tw, 0) : px + 14}px`;
  }

  function esconder() {
    atual = -1;
    cross.hidden = dot.hidden = tip.hidden = true;
  }

  const maisProximo = (clientX) => {
    const r = canvas.getBoundingClientRect();
    const x = (clientX - r.left) / r.width * 100;
    let melhor = 0;
    coords.forEach((c, i) => { if (Math.abs(c.x - x) < Math.abs(coords[melhor].x - x)) melhor = i; });
    return melhor;
  };

  hit.addEventListener('pointermove', (e) => mostrar(maisProximo(e.clientX)));
  hit.addEventListener('pointerleave', esconder);
  hit.addEventListener('focus', () => mostrar(pontos.length - 1));
  hit.addEventListener('blur', esconder);
  hit.addEventListener('keydown', (e) => {
    const passo = { ArrowLeft: -1, ArrowRight: 1, Home: -Infinity, End: Infinity }[e.key];
    if (passo === undefined) return;
    e.preventDefault();
    mostrar(Math.min(pontos.length - 1, Math.max(0, (atual < 0 ? pontos.length - 1 : atual) + passo)));
  });
})();

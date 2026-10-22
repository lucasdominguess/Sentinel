// acoes-index.js — busca (por ticker/nome) e ordenação (alta / queda / valor) client-side
const busca = document.getElementById('busca');
const grid = document.getElementById('grid');
const semResultados = document.getElementById('no-results');
const pills = document.querySelectorAll('.pill[data-sort]');

if (grid) {
    const cards = Array.from(grid.querySelectorAll('.acao-card'));

    // lê um data-attribute numérico; vazio/NaN vira null
    const num = (card, attr) => {
        const v = parseFloat(card.dataset[attr]);
        return Number.isNaN(v) ? null : v;
    };

    const comparadores = {
        // maior variação primeiro (sem dado vai para o fim)
        alta: (a, b) => (num(b, 'variacao') ?? -Infinity) - (num(a, 'variacao') ?? -Infinity),
        // menor variação primeiro (quedas no topo)
        queda: (a, b) => (num(a, 'variacao') ?? Infinity) - (num(b, 'variacao') ?? Infinity),
        // maior preço primeiro
        valor: (a, b) => (num(b, 'preco') ?? -Infinity) - (num(a, 'preco') ?? -Infinity),
    };

    const ordenar = (chave) => {
        const cmp = comparadores[chave] || comparadores.alta;
        // reanexa na nova ordem; o estado hidden (busca) é preservado
        cards.slice().sort(cmp).forEach((card) => grid.appendChild(card));
    };

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            pills.forEach((p) => p.classList.remove('is-active'));
            pill.classList.add('is-active');
            ordenar(pill.dataset.sort);
        });
    });

    if (busca) {
        busca.addEventListener('input', () => {
            const termo = busca.value.trim().toLowerCase();
            let visiveis = 0;

            cards.forEach((card) => {
                const match = card.dataset.search.includes(termo);
                card.hidden = !match;
                if (match) visiveis += 1;
            });

            if (semResultados) semResultados.hidden = visiveis !== 0;
        });
    }

    // --- Tooltip de mín/máx do mês: aparece após 1s de hover sobre o card ---
    const tooltip = document.createElement('div');
    tooltip.className = 'card-tooltip';
    document.body.appendChild(tooltip);

    const brl = (v) => {
        const n = parseFloat(v);
        return Number.isNaN(n) ? '—' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const fmtData = (iso) => {
        if (!iso) return '';
        const [, m, d] = iso.split('-');
        return d && m ? `${d}/${m}` : '';
    };

    let hoverTimer = null;
    let lastEvent = null;

    const posicionar = (e) => {
        const pad = 14;
        const r = tooltip.getBoundingClientRect();
        let x = e.clientX + pad;
        let y = e.clientY + pad;
        if (x + r.width > window.innerWidth) x = e.clientX - r.width - pad;
        if (y + r.height > window.innerHeight) y = e.clientY - r.height - pad;
        tooltip.style.left = `${x}px`;
        tooltip.style.top = `${y}px`;
    };

    const mostrar = (card) => {
        const min = card.dataset.minMes;
        const max = card.dataset.maxMes;
        const dMin = fmtData(card.dataset.minMesData);
        const dMax = fmtData(card.dataset.maxMesData);
        if (!min && !max) {
            tooltip.innerHTML = '<span class="tt-empty">Sem histórico do mês</span>';
        } else {
            tooltip.innerHTML =
                '<div class="tt-title">No mês</div>' +
                `<div class="tt-row"><span>Mínima</span><strong>${brl(min)}${dMin ? ` <em>${dMin}</em>` : ''}</strong></div>` +
                `<div class="tt-row"><span>Máxima</span><strong>${brl(max)}${dMax ? ` <em>${dMax}</em>` : ''}</strong></div>`;
        }
        tooltip.classList.add('show');
        if (lastEvent) posicionar(lastEvent);
    };

    const esconder = () => {
        clearTimeout(hoverTimer);
        tooltip.classList.remove('show');
    };

    cards.forEach((card) => {
        card.addEventListener('mouseenter', (e) => {
            lastEvent = e;
            hoverTimer = setTimeout(() => mostrar(card), 1000);
        });
        card.addEventListener('mousemove', (e) => {
            lastEvent = e;
            if (tooltip.classList.contains('show')) posicionar(e);
        });
        card.addEventListener('mouseleave', esconder);
    });
}

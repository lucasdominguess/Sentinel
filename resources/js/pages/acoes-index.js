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
}

// acoes-porcentagem.js — 5 ferramentas de cálculo de porcentagem (client-side)

const num = (x) => x.toLocaleString('pt-BR', { maximumFractionDigits: 2 });

function calcular(tipo, a, b) {
    if (Number.isNaN(a) || Number.isNaN(b)) return null;

    switch (tipo) {
        case 'quanto': // quanto é a% de b
            return num((a / 100) * b);
        case 'qualPct': // a é qual porcentagem de b
            return b === 0 ? null : num((a / b) * 100) + '%';
        case 'aumento': // aumento percentual de a para b
            return a === 0 ? null : num(((b - a) / a) * 100) + '%';
        case 'reducao': // diminuição percentual de a para b
            return a === 0 ? null : num(((a - b) / a) * 100) + '%';
        case 'sobre': // a sobre b é quantos por cento
            return b === 0 ? null : num((a / b) * 100) + '%';
        default:
            return null;
    }
}

document.querySelectorAll('.pct-tool').forEach((tool) => {
    const btn = tool.querySelector('.pct-calc');
    const out = tool.querySelector('.pct-result');
    const inputs = tool.querySelectorAll('.pct-input');

    const run = () => {
        const a = parseFloat(String(inputs[0].value).replace(',', '.'));
        const b = parseFloat(String(inputs[1].value).replace(',', '.'));
        const r = calcular(tool.dataset.tool, a, b);
        out.textContent = r === null ? '—' : r;
    };

    btn.addEventListener('click', run);
    // Enter em qualquer campo também calcula
    inputs.forEach((input) => {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') run();
        });
    });
});

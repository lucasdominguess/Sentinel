// aportes-registrar.js — auto-preenche o preço com a última cotação do ativo
// selecionado (editável) e mostra o total investido (preço x quantidade) em tempo real.

const ativo = document.getElementById('aporte-ativo');
const preco = document.getElementById('aporte-preco');
const qtd = document.getElementById('aporte-qtd');
const totalEl = document.getElementById('aporte-total-valor');

const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function atualizarTotal() {
    const p = parseFloat(preco.value) || 0;
    const q = parseInt(qtd.value, 10) || 0;
    totalEl.textContent = brl(p * q);
}

if (ativo && preco && qtd && totalEl) {
    ativo.addEventListener('change', () => {
        const opt = ativo.selectedOptions[0];
        const p = parseFloat(opt?.dataset.preco);

        // só sobrescreve o preço se o ativo tem cotação e o campo ainda está vazio/zerado
        if (!Number.isNaN(p) && (!preco.value || parseFloat(preco.value) === 0)) {
            preco.value = p.toFixed(2);
        }
        atualizarTotal();
    });

    preco.addEventListener('input', atualizarTotal);
    qtd.addEventListener('input', atualizarTotal);

    atualizarTotal();
}

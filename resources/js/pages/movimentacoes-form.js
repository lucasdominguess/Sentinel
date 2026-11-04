// movimentacoes-form.js — auto-preenche o preço com a última cotação do ativo
// selecionado (editável), mostra o total (preço x quantidade) em tempo real e,
// em vendas, o saldo de cotas do ativo como referência (a validação é no servidor).

const ativo = document.getElementById('mov-ativo');
const preco = document.getElementById('mov-preco');
const qtd = document.getElementById('mov-qtd');
const totalEl = document.getElementById('mov-total-valor');
const totalLabel = document.getElementById('mov-total-label');
const saldoEl = document.getElementById('mov-saldo');
const tipos = document.querySelectorAll('input[name="tipo"]');

const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const tipoAtual = () => document.querySelector('input[name="tipo"]:checked')?.value || 'compra';

function atualizar() {
    const p = parseFloat(preco.value) || 0;
    const q = parseInt(qtd.value, 10) || 0;
    const venda = tipoAtual() === 'venda';

    totalEl.textContent = brl(p * q);
    totalLabel.textContent = venda ? 'Total da venda' : 'Total da compra';

    const cotas = parseInt(ativo.selectedOptions[0]?.dataset.cotas, 10);
    const mostrarSaldo = venda && ativo.value !== '' && !Number.isNaN(cotas);
    saldoEl.hidden = !mostrarSaldo;
    if (mostrarSaldo) {
        const excede = q > cotas;
        saldoEl.textContent = `Saldo atual: ${cotas.toLocaleString('pt-BR')} cota(s)` + (excede ? ' — a venda excede o saldo' : '');
        saldoEl.classList.toggle('is-warn', excede);
    }
}

if (ativo && preco && qtd && totalEl) {
    ativo.addEventListener('change', () => {
        const p = parseFloat(ativo.selectedOptions[0]?.dataset.preco);

        // só sobrescreve o preço se o ativo tem cotação e o campo ainda está vazio/zerado
        if (!Number.isNaN(p) && (!preco.value || parseFloat(preco.value) === 0)) {
            preco.value = p.toFixed(2);
        }
        atualizar();
    });

    preco.addEventListener('input', atualizar);
    qtd.addEventListener('input', atualizar);
    tipos.forEach((t) => t.addEventListener('change', atualizar));

    atualizar();
}

// acoes-calculo.js — simulador de aportes (distribui o valor entre os ativos)
// Lógica baseada no script calculo_qtd_cota; a seleção do ativo vem de um
// select dos ativos cadastrados e auto-preenche o preço (editável).

const container = document.getElementById('calc-rows');
const template = document.getElementById('calc-row-template');
const addBtn = document.getElementById('calc-add');
const runBtn = document.getElementById('calc-run');
const erroEl = document.getElementById('calc-erro');
const resultado = document.getElementById('calc-resultado');
const tbody = document.getElementById('calc-tbody');
const trocoTotalEl = document.getElementById('calc-troco-total');

const brl = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

function novaLinha() {
    const row = template.content.firstElementChild.cloneNode(true);
    container.appendChild(row);
    ligarLinha(row);
}

function ligarLinha(row) {
    const ativo = row.querySelector('.calc-ativo');
    const preco = row.querySelector('.calc-preco');
    const dividendo = row.querySelector('.calc-dividendo');
    const remover = row.querySelector('.calc-remove');

    ativo.addEventListener('change', () => {
        const opt = ativo.selectedOptions[0];
        const p = parseFloat(opt?.dataset.preco);
        const div = parseFloat(opt?.dataset.dividendo);

        // auto-preenche o preço com a última cotação (continua editável)
        if (!Number.isNaN(p)) preco.value = p.toFixed(2);

        // dividendo/cota = último provento pago; em branco se não houver
        dividendo.value = !Number.isNaN(div) && div > 0 ? div.toFixed(2) : '';

        atualizarPaga(row);
    });

    preco.addEventListener('input', () => atualizarPaga(row));
    dividendo.addEventListener('input', () => atualizarPaga(row));
    remover.addEventListener('click', () => row.remove());
}

// % Paga = dividendo/cota ÷ preço × 100 (rendimento daquele provento sobre o preço)
function atualizarPaga(row) {
    const preco = parseFloat(row.querySelector('.calc-preco').value) || 0;
    const dividendo = parseFloat(row.querySelector('.calc-dividendo').value) || 0;
    const paga = row.querySelector('.calc-paga');
    paga.value = preco > 0 ? ((dividendo / preco) * 100).toFixed(2) : '';
}

function mostrarErro(msg) {
    erroEl.textContent = msg;
    erroEl.hidden = false;
    resultado.hidden = true;
}

function calcular() {
    const valorTotal = parseFloat(document.getElementById('calc-valor').value) || 0;
    const rows = Array.from(container.querySelectorAll('.calc-row'));

    const somaPorc = rows.reduce((s, r) => s + (parseFloat(r.querySelector('.calc-porc').value) || 0), 0);

    erroEl.hidden = true;
    if (valorTotal <= 0) return mostrarErro('Informe o valor total do aporte.');
    if (rows.length === 0) return mostrarErro('Adicione ao menos um ativo.');
    if (Math.round(somaPorc) !== 100) return mostrarErro(`A soma das % Alvo deve ser 100% (atual: ${somaPorc}%).`);

    tbody.innerHTML = '';
    let trocoGeral = 0;

    rows.forEach((r) => {
        const ticker = r.querySelector('.calc-ativo').value || '—';
        const preco = parseFloat(r.querySelector('.calc-preco').value) || 0;
        const porc = parseFloat(r.querySelector('.calc-porc').value) || 0;
        const divCota = parseFloat(r.querySelector('.calc-dividendo').value) || 0;

        if (preco <= 0) return;

        const valorAlvo = valorTotal * (porc / 100);
        const qtd = Math.floor(valorAlvo / preco); // não existe fração de cota
        const investido = qtd * preco;
        const troco = valorAlvo - investido;
        const dividendos = qtd * divCota;
        trocoGeral += troco;

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${ticker}</td>
                <td class="num">${porc}%</td>
                <td class="num forte">${qtd}</td>
                <td class="num">${brl(investido)}</td>
                <td class="num">${brl(troco)}</td>
                <td class="num">${brl(dividendos)}</td>
            </tr>`);
    });

    trocoTotalEl.textContent = brl(trocoGeral);
    resultado.hidden = false;
    resultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

if (container && template) {
    addBtn?.addEventListener('click', novaLinha);
    runBtn?.addEventListener('click', calcular);
    // começa com 2 linhas (como o script original)
    novaLinha();
    novaLinha();
}

// acoes-cadastro.js — filtro client-side da tabela de ativos por ticker, nome ou setor.

const busca = document.getElementById('busca-ativo');
const tabela = document.getElementById('tabela-ativos');
const semResultados = document.getElementById('sem-resultados');

if (busca && tabela) {
    const linhas = Array.from(tabela.tBodies[0].rows);

    busca.addEventListener('input', () => {
        const termo = busca.value.trim().toLowerCase();
        let visiveis = 0;

        linhas.forEach((linha) => {
            const alvo = linha.dataset.search || '';
            const mostra = alvo.includes(termo);
            linha.hidden = !mostra;
            if (mostra) visiveis++;
        });

        if (semResultados) semResultados.hidden = visiveis > 0;
    });
}

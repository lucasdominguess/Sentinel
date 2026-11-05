# Auditoria dos cálculos da carteira e das telas — 2026-11-05

Escopo: `LivroCarteira`, `MovimentacaoService::posicoes`, `PosicaoResponseDTO`, `CarteiraService::fotoDoDia|evolucao`,
`CotacaoDiariaRepository`, views `carteira/*`, componentes `holding-card`, `holding-row`, `cost-bar`, `badge-var`, `donut`,
`asset-card`, `asset-row`, `range-bar`, JS `carteira`, `movimentacoes-form`, `acoes-calculo`, `acoes-porcentagem` e coletor (`stock_service`, `fetch_stocks`).

## Status

Os 9 itens foram aplicados em 2026-11-05 e verificados no banco de dev.
Item 4: arredonda só o ruído (diferença menor que 0,0001), porque cerca de 12,8 mil fechamentos têm casas a mais de verdade (ajuste por desdobramento ou bonificação).
Produção: o `migrate` do deploy corrige os `total`; depois rodar `normalizar_precos.py` uma vez.

## Conferência do print (banco de dev, 12 posições)

Os 4 cards batem com o código e com o banco: qtd × preço = valor; investido ÷ qtd = PM; valorização = valor − investido;
% = preço ÷ PM − 1 (VGIR11 −9,83%, GGRC11 −16,60%, VGRI11 −34,03%, VALE3 −4,17%). Peso = valor ÷ R$ 18.305,44.
Nos 12 ativos, "valor − investido" exibido é igual à valorização exibida. Nenhuma venda registrada, então lucro realizado
não foi exercitado pelos dados.

## Itens

### 1. [Alta · dado] Movimentação #11 (VVEO3) com `total` ≠ preço × qtd
- Compra de 1 × R$ 0,56 gravada com `total` = 0,57 (criada em 30/10, na época de `aportes`/import, sem passar por `calcularTotal`).
- Efeito: PM R$ 0,57 em vez de 0,56; valorização +R$ 0,13 / +22,81% em vez de +R$ 0,14 / +25,00%.
- Correção: conferir também em produção e corrigir o dado (`total = round(preco * quantidade, 2)`).
  Opcional: CHECK no banco ou comando de verificação, para isso não voltar a passar pelo import.
  ```sql
  SELECT id, acao_id, preco, quantidade, total, round(preco * quantidade, 2) AS esperado
  FROM movimentacoes WHERE total <> round(preco * quantidade, 2);
  ```

### 2. [Alta] "Meus ativos" e "Evolução" divergem no dia corrente
- Hoje: Meus ativos R$ 18.305,44 × Evolução R$ 18.305,24 (VALE3: `cotacoes.preco` 62,79 × `cotacoes_diarias.fechamento` 62,77).
- Causa: a linha de hoje em `cotacoes_diarias` vem da barra do `history` do yfinance, que fica defasada em relação ao `currentPrice`.
- Correção (escolher uma): no coletor, gravar a linha do dia corrente com o mesmo preço da cotação; ou, na evolução,
  usar a última cotação no ponto de hoje.

### 3. [Alta · regra] Variação "Hoje" aplica a variação à quantidade inteira, inclusive cotas compradas hoje
- `PosicaoResponseDTO`: `(preco − fechamento_anterior) × quantidade`. Uma compra feita hoje entra como se fosse de ontem
  (comprou 100 a 8,50, ontem fechou 8,40, agora 8,60 → mostra +R$ 20, o ganho real é +R$ 10). Vale também para a foto do dia.
- A evolução já trata isso certo (fluxo do dia). Correção: mesma fórmula da evolução —
  `variação = valor hoje − (qtd de ontem × fech. anterior + compras do dia − vendas do dia)`,
  `% = variação ÷ (valor de ontem + compras do dia)`.

### 4. [Média · coletor] `fechamento` com resíduo de float
- Ex.: BOVA11 `165.449997` (deveria ser 165,45). O tick da B3 é R$ 0,01.
- Correção: `round(close, 2)` ao gravar `abertura/maxima/minima/fechamento` em `cotacoes_diarias` (não o `fechamento_ajustado`)
  e correção pontual dos dados existentes (decidir antes: o coletor é o dono da tabela).

### 5. [Média] Foto do dia mostra variação de outro pregão
- `fotoDoDia` usa os 2 últimos fechamentos de cada ativo. Se o ativo não teve fechamento no pregão escolhido, a variação
  "No pregão" mostrada é a de um dia anterior.
- Correção: só calcular a variação quando `rows[0]->data` for o pregão da foto; senão, variação 0.

### 6. [Média] Lucro realizado não fecha com o `total` da venda
- `LivroCarteira::aplicar`: `realizado = (preco − PM) × qtd` (sem arredondar), enquanto a evolução usa `total` (arredondado)
  como resgate → até R$ 0,005 de diferença por venda entre realizado e fluxo.
- Correção: `realizado = total − PM × qtd`.

### 7. [Média] Arredondamento independente entre investido, valor e valorização
- O DTO arredonda `total_investido`, mas não `valor_atual` nem `valorizacao_reais`; a view arredonda cada um separadamente.
  Hoje fecha, mas com preços de 6 casas (item 4) a tela pode mostrar `valor − investido ≠ valorização` em R$ 0,01,
  e a soma dos cards pode não bater com o resumo.
- Correção: no DTO, `valor_atual = round(preco × qtd, 2)` e `valorizacao_reais = valor_atual − total_investido`;
  o resumo soma os valores já arredondados. O % continua sendo calculado sem arredondar.

### 8. [Baixa · UI] Sinal do badge e do delta usam critérios diferentes
- O delta em R$ usa o limiar ±0,004; o `badge-var` usa `pct ≠ 0` → pode aparecer "▼ 0,00%" ao lado de um delta neutro.
- Correção: `badge-var` decide a tendência por `round(pct, 2)`.

### 9. [Baixa · ferramenta] Calculadora de cotas perde 1 cota por erro de float
- `Math.floor(valorAlvo / preco)`: aporte R$ 1.850, 30%, preço 2,22 → 555 / 2,22 = 249,99999… → 249 (o certo é 250).
- `Math.round(somaPorc) !== 100` aceita de 99,5% a 100,49% (com 100,4% aloca mais que o aporte e o troco fica negativo).
- Correção: fazer a conta em centavos inteiros e validar a soma com tolerância de 0,01.

### Já conhecido (CLAUDE.md)
- Fuso do Laravel em UTC: entre 21h e 0h, `today()` já é amanhã e "hoje" no calendário vira foto do dia.
- Proventos e desdobramentos fora do livro; `fechamento` sem ajuste distorce a evolução em datas de desdobramento.

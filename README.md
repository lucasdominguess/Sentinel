# Sentinel

Painel pessoal de **acompanhamento de renda variável da B3** (ações, FIIs e ETFs) e da
**carteira de investimentos**. Um coletor em Python busca as cotações no Yahoo Finance
ao longo do pregão e grava no PostgreSQL. A aplicação Laravel lê esse banco para
monitorar os ativos, registrar compras e vendas, mostrar a posição consolidada e
analisar a evolução do patrimônio no tempo.

> **Objetivo:** responder com dados, sem planilha, três perguntas: *como estão os ativos
> que eu acompanho hoje?*, *quanto vale a minha carteira e quanto ela rendeu?* e
> *como ela estava (e se saiu) em qualquer dia do passado?*

---

## Sumário

1. [Visão geral da arquitetura](#1-visão-geral-da-arquitetura)
2. [Funcionalidades](#2-funcionalidades)
3. [Regras de negócio e cálculos](#3-regras-de-negócio-e-cálculos)
4. [Banco de dados](#4-banco-de-dados)
5. [Coletor Python](#5-coletor-python)
6. [Rotas](#6-rotas)
7. [Estrutura do código](#7-estrutura-do-código)
8. [Front-end e design system](#8-front-end-e-design-system)
9. [Setup, execução e deploy](#9-setup-execução-e-deploy)
10. [Limitações conhecidas](#10-limitações-conhecidas)
11. [Ideias de evolução](#11-ideias-de-evolução)
12. [Herança do template](#12-herança-do-template)

---

## 1. Visão geral da arquitetura

```
                 ┌──────────────────────────── servidor Linux (produção) ────────────────────────────┐
                 │                                                                                   │
  Yahoo Finance  │   cron (a cada 15 min, dias úteis, fuso da B3)                                    │
  (yfinance) ◄───┼── scripts/python/fetch_stocks.py ──┐                                              │
                 │   scripts/python/backfill_history.py (carga histórica pontual)                    │
                 │                                    ▼                                              │
                 │                        ┌──────────────────────┐                                   │
                 │                        │  PostgreSQL (SENTINEL) │  acoes · cotacoes ·             │
                 │                        │                        │  cotacoes_diarias · coletas ·   │
                 │                        └──────────▲───────────┘  movimentacoes                   │
                 │                                   │                                               │
                 │                       Laravel 12 (mesmo banco)                                    │
                 │             ┌─────────────────────┴─────────────────────┐                         │
                 │      Painel web (Blade, server-rendered)        API REST JSON (/api)              │
                 └───────────────────────────────────────────────────────────────────────────────────┘
```

- **Coletor (Python)** — vive em `scripts/python/`, separado do Laravel mas no mesmo
  repositório e no mesmo banco. É o **único que escreve cotações**. Não usa o scheduler do
  Laravel: roda direto no cron do servidor.
- **Aplicação (Laravel)** — painel web renderizado no servidor (Blade + CSS/JS por tela,
  sem framework JS) e uma API REST. Lê as cotações e é dona das **movimentações** da carteira.
- **Dados derivados não são gravados.** Posição, preço médio, valorização, foto de um dia
  passado e evolução do patrimônio são **calculados na hora**, a partir das movimentações
  (cotas por data) e dos fechamentos diários (preço por data). Corrigir uma movimentação
  antiga corrige todo o histórico.

---

## 2. Funcionalidades

### 2.1 Monitores — Ações, FIIs e ETFs

Rotas `/`, `/fiis`, `/etfs` (mesma tela, filtrada pelo `tipo` do ativo).

- **Cabeçalho com resumo do dia:** total de ativos, quantos estão em alta/queda (com barra
  proporcional) e horário da última coleta, com o status dela (`53 ok`, `N falha(s)` ou
  `interrompida`).
- **Alerta de falha** quando a última coleta não terminou ou não atualizou nenhum ativo
  ("exibindo os últimos valores de…").
- **Card por ativo:** ticker (sufixo `.SA` esmaecido), nome, badge de variação %, preço,
  variação em R$, **sparkline intradia** (abertura → coletas do dia → preço atual, com linha
  tracejada no fechamento anterior), **faixa do dia** (mín → máx, com marca de abertura e
  posição do preço atual), fechamento anterior e abertura.
- **Tooltip de mês** (após ~1s de hover): mínima e máxima do mês, com as datas.
- **Ordenação:** maior alta %, maior queda %, maior valor, menor valor.
- **Busca ao vivo** por ticker ou nome (atalho `/` foca a busca).
- **Grade ou lista densa** (≥1024px): tabela com intradia, preço, variações, faixa do dia,
  fechamento anterior e abertura. A escolha fica salva no navegador.
- **Tema claro/escuro** (claro: branco/azul; escuro: preto/roxo), também salvo no navegador.

### 2.2 Detalhe do ativo

Rota `/acoes/{acao}`. Gráfico de evolução do preço a partir dos **fechamentos diários**
(os últimos 500 pregões, cerca de 2 anos, lidos de `/api/acao/{acao}/historico`) e quadro
com a última cotação (preço, variações, mín/máx do dia e de 52 semanas, volume, volume
médio, valor de mercado, dividend yield, P/L, último provento e data).

### 2.3 Carteira

#### Meus ativos — `/carteira`
Posição consolidada da carteira.

- **Resumo:** valor atual, total investido (custo da posição aberta), valorização (R$ e %),
  **variação do dia** da carteira e barra "valor atual × custo".
- **Alocação** em donut, agrupável por **ativo** (top 5 + "Outros") ou por **classe**
  (FIIs / Ações / ETFs).
- **Filtro por classe** (com contagem), ordenação (maior posição, pior/melhor valorização,
  ticker A–Z) e visão em **grade ou lista** (com barra de peso de cada ativo).
- **Card por posição:** ticker, classe, badge de valorização, valor atual, ganho/perda em
  R$, barra valor × custo, investido, peso na carteira, quantidade, preço médio e preço atual.
- **Calendário "Posição em" (foto do dia):** ao escolher uma data, a tela inteira mostra a
  carteira **no fechamento daquele dia**. As cotas e o preço médio contam só as
  movimentações até a data, e o preço é o fechamento do pregão. Em fim de semana ou
  feriado, usa o último pregão anterior e avisa. A data vai na URL (`?data=2026-10-29`).

#### Evolução do patrimônio — `/carteira/evolucao`
Quanto a carteira rendeu num período, separando o dinheiro aplicado do resultado do mercado.

- **Períodos:** Semana, Mês, Mês anterior, No ano, 12 meses ou **intervalo personalizado**
  (calendário de início e fim). A URL guarda o período (`?periodo=mes` ou `?de=…&ate=…`).
- **Indicadores:** patrimônio (final e inicial), **aportes líquidos** (compras − vendas),
  **ganho de mercado** (R$ e rentabilidade %) e **lucro realizado** nas vendas.
- **Gráfico dia a dia:** valor de mercado × custo da posição, com os dias de compra/venda
  marcados; crosshair e tooltip por pregão; navegação por teclado (setas, Home/End).
- **Tabela "Ver valores por pregão"** (visão acessível dos pontos do gráfico).
- **Contribuição por ativo:** quanto cada ativo ganhou ou perdeu no período, com barra
  divergente. A soma das linhas fecha com o ganho total.

#### Movimentações — `/movimentacoes`
Livro de registros da carteira (compras e vendas).

- **Registrar / editar** com seletor **Compra | Venda**, preço unitário (preenchido com a
  última cotação), quantidade, data e total calculado em tempo real. Na venda, o formulário
  mostra o **saldo de cotas** do ativo e avisa quando a quantidade passa dele.
- **Listagem paginada** com tipo, preço, quantidade (negativa nas vendas) e total; editar e
  excluir por linha.
- **Validação de saldo no servidor:** nenhuma operação (registrar, editar ou excluir) pode
  deixar o saldo de um ativo negativo **em data nenhuma**.

### 2.4 Ferramentas

- **Cálculo de cotas** (`/calculo-cotas`): distribui um valor de aporte entre os ativos
  escolhidos e mostra quantas cotas comprar de cada, com base na última cotação.
- **Cálculo de porcentagem** (`/porcentagem`): cinco calculadoras rápidas de porcentagem
  (client-side).

### 2.5 Cadastro de ativos

- **Adicionar ticket** (`/adicionar`): cadastra um ticker (ex.: `VALE3.SA`) e o tipo
  (ação/FII/ETF). O coletor o busca na próxima execução.
- **Gerenciar ativos** (`/cadastro/ativos`): CRUD completo — listagem com busca, detalhes,
  edição (nome, setor, segmento, tipo, monitorado ou não) e exclusão (remove também as
  cotações e movimentações do ativo).

### 2.6 API REST

JSON padronizado via `App\Helpers\ApiResponse` (`success`, `created`, `paginated`).
O L5-Swagger está instalado, mas as rotas do Sentinel ainda não têm anotações OpenAPI.

| Método | Rota | O que faz |
| --- | --- | --- |
| GET | `/api/acao` | Lista ativos (filtros: `ticker`, `tipo`, `setor`, `ativo`; `perPage`, `page`) |
| GET | `/api/acao/{acao}` | Detalhe do ativo com a última cotação |
| POST / PUT / DELETE | `/api/acao`, `/api/acao/{acao}` | Cria, atualiza e exclui ativo |
| GET | `/api/acao/{acao}/cotacoes` | Série intradia (snapshots do coletor), paginada |
| GET | `/api/acao/{acao}/historico` | Série de fechamentos diários, paginada |
| GET | `/api/movimentacao` | Lista movimentações (filtros: `acao_id`, `tipo`) |
| GET / POST / PUT / DELETE | `/api/movimentacao`, `/api/movimentacao/{movimentacao}` | CRUD de movimentações (com a mesma validação de saldo da tela) |

---

## 3. Regras de negócio e cálculos

### 3.1 Posição e preço médio — custo médio ponderado

Implementado em `app/Services/Carteira/LivroCarteira.php` (regra da Receita Federal):

- **Compra:** soma as cotas e soma o total pago ao custo da posição.
- **Venda:** baixa as cotas **ao preço médio vigente**, então o preço médio **não muda**.
  A diferença para o preço de venda é **lucro (ou prejuízo) realizado**:
  `(preço de venda − preço médio) × cotas vendidas`.
- **Ordem do livro:** data; no mesmo dia, compras antes de vendas; depois ordem de registro.
- **Posição em uma data D** = movimentações com `data ≤ D`. Comprar mais hoje não altera o
  passado.
- Ativo zerado por venda sai de Meus ativos.

### 3.2 Validação de saldo

Em `MovimentacaoService::validarSaldo()`. Antes de gravar, o serviço simula a operação e
percorre as movimentações do ativo em ordem. Se alguma venda consumir mais cotas do que
havia naquela data, a operação é recusada com a data e o saldo. Vale para registrar,
editar (inclusive trocar o ativo) e excluir uma compra que sustentava uma venda posterior.

### 3.3 Preço de referência

| Contexto | Preço usado |
| --- | --- |
| Monitores e Meus ativos "ao vivo" | última coleta (`cotacoes`, mais recente por `capturado_em`) |
| Foto de um dia passado | `fechamento` em `cotacoes_diarias` do dia (ou do último pregão anterior) |
| Evolução do patrimônio | `fechamento` diário com *carry-forward*: dia sem fechamento de um ativo repete o anterior; ativo ainda sem nenhum fechamento vale o custo |
| Variação do dia (carteira) | preço de referência × fechamento do pregão anterior |

Usa-se o `fechamento` (preço de tela), não o `fechamento_ajustado`, porque a quantidade dos
seus aportes é a real do dia da compra.

### 3.4 Evolução do patrimônio

Em `app/Services/CarteiraService.php::evolucao()`:

- **Ponto de partida:** fechamento do último pregão **antes** do início do período (a
  carteira como estava ao entrar nele). Se o período começa antes da primeira movimentação,
  o início é ajustado para ela.
- **Fluxo de caixa:** movimentações dentro do período. Compras são aportes; vendas, resgates.
- **Ganho de mercado = valor final − valor inicial − (aportes − resgates).** Um aporte não
  aparece como rendimento.
- **Rentabilidade ponderada no tempo (TWR):** o retorno de cada dia desconta o dinheiro que
  entrou ou saiu nele. Compras contam desde o início do dia (a diferença entre o preço pago e
  o fechamento já é resultado) e vendas até o fim:
  `retorno do dia = (valor + resgates) / (valor anterior + aportes) − 1`.
- **Contribuição por ativo:** `ganho = valor final − valor inicial − (compras − vendas)` do
  ativo. A soma das contribuições fecha com o ganho total.

### 3.5 Atalhos de período

Em `app/Http/Requests/EvolucaoRequest.php::intervalo()`: **Semana** (segunda-feira → hoje),
**Mês** (dia 1 → hoje, padrão), **Mês anterior** (mês fechado), **No ano** (1º de janeiro →
hoje), **12 meses** (hoje − 1 ano + 1 dia → hoje). `de`/`ate` explícitos vencem o atalho.

---

## 4. Banco de dados

PostgreSQL, database `SENTINEL` (credenciais no `.env` do Laravel, também lidas pelo
coletor). As tabelas de domínio usam nomes em português.

### 4.1 Tabelas de domínio

#### `acoes` — cadastro dos ativos
| Coluna | Descrição |
| --- | --- |
| `ticker` (único) | Código no Yahoo Finance, ex.: `VALE3.SA` |
| `nome`, `setor`, `segmento`, `moeda` | Metadados (preenchidos pelo coletor) |
| `tipo` | `acao` \| `fii` \| `etf` (`App\Enums\TipoAtivo`) |
| `ativo` | Se o ativo é monitorado pelo coletor |

**Quem grava:** cadastro (Laravel) e o coletor (upsert por ticker, sem sobrescrever
`tipo`/`ativo`). **Quem lê:** todas as telas.

#### `cotacoes` — snapshots intradia (série temporal)
Uma linha **por ativo por execução do coletor** (a cada 15 min em produção).

| Grupo | Colunas |
| --- | --- |
| Preço do momento | `preco`, `fechamento_anterior`, `abertura`, `minima_dia`, `maxima_dia`, `variacao_dia` (R$), `variacao_dia_pct` |
| Faixas | `minima_52s`, `maxima_52s`, `minima_mes` + `minima_mes_data`, `maxima_mes` + `maxima_mes_data` |
| Mercado | `volume`, `volume_medio`, `valor_mercado`, `dividend_yield`, `preco_lucro` |
| Proventos | `ultimo_dividendo`, `ultimo_dividendo_data` |
| Contexto | `estado_mercado` (REGULAR, PRE, CLOSED…), `capturado_em` |

Índice `(acao_id, capturado_em)`. **Quem grava:** `fetch_stocks.py`. **Quem lê:** última
cotação (cards, carteira ao vivo), sparkline intradia (snapshots do último dia com coleta
de cada ativo) e `/api/acao/{acao}/cotacoes`.

#### `cotacoes_diarias` — fechamento diário consolidado
Uma linha **por ativo por dia de pregão** (único em `acao_id + data`), vinda do
`history()` do yfinance: `abertura`, `maxima`, `minima`, `fechamento` (preço de tela),
`fechamento_ajustado` (ajustado por desdobramentos e proventos) e `volume`.

**Quem grava:** `backfill_history.py` (carga de até 5+ anos) e `fetch_stocks.py` (regrava o
mês corrente a cada execução, o que fecha buracos sozinho). **Quem lê:** gráfico do detalhe
do ativo, foto do dia, evolução do patrimônio, dias de pregão do calendário e
`/api/acao/{acao}/historico`.

> A linha do dia corrente é provisória durante o pregão. A última execução do dia (após o
> fechamento da B3) grava o valor definitivo.

#### `coletas` — log de execução do coletor
| Coluna | Descrição |
| --- | --- |
| `origem` | `coleta` (rotina) ou `backfill` (carga histórica) |
| `inicio`, `fim` | Início e fim; **`fim` nulo = execução que não terminou** |
| `ativos_ok`, `ativos_falha`, `registros` | Contadores |
| `erro` | Mensagem do erro que abortou a execução |

**Quem grava:** os dois scripts de entrada do coletor. **Quem lê:** cabeçalho e alerta dos
monitores.

#### `movimentacoes` — livro da carteira (compra e venda)
| Coluna | Descrição |
| --- | --- |
| `acao_id` | Ativo negociado (FK, `cascadeOnDelete`) |
| `tipo` | `compra` \| `venda` (`App\Enums\TipoMovimentacao`) |
| `preco` | Preço unitário da operação |
| `quantidade` | Cotas negociadas (sempre positiva; o `tipo` dá o sentido) |
| `total` | `preco × quantidade` (derivado no Service) |
| `data` | Data da operação (padrão: hoje; nunca futura) |

Índice `(acao_id, data)`. Substituiu a antiga tabela `aportes`: a migration
`2026_11_04_000001_create_movimentacoes_table` copiou os aportes como compras e removeu
`aportes` (o `down()` recria `aportes` só com as compras). **Quem grava:** telas e API de
movimentações, e o `import_sync.py`. **Quem lê:** Meus ativos, Evolução, formulários.

### 4.2 Relações

```
acoes 1 ──< cotacoes            (snapshots intradia)
acoes 1 ──< cotacoes_diarias    (um fechamento por pregão)
acoes 1 ──< movimentacoes       (compras e vendas)
coletas                         (independente: log de execução)
```

No model `Acao`: `cotacoes()`, `cotacoesDiarias()`, `movimentacoes()`, `ultimaCotacao()`
(`latestOfMany('capturado_em')`) e `ultimoFechamento()` (`latestOfMany('data')`).

### 4.3 Tabelas do framework e do template

`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs` — padrão do Laravel/template, não usadas pelas
funcionalidades do Sentinel.

---

## 5. Coletor Python

Em `scripts/python/`. Referência completa (função por função, setup e cron) em
[`scripts/python/README.md`](scripts/python/README.md).

### 5.1 Scripts de entrada

| Script | Onde roda | O que faz |
| --- | --- | --- |
| `fetch_stocks.py` | cron (produção) ou manual | Coleta do momento para todos os tickers `ativo = true`: grava um snapshot em `cotacoes`, faz upsert do histórico do mês em `cotacoes_diarias` e registra a execução em `coletas`. Aceita tickers na linha de comando, `--tipo=fii` (para ticker inédito) e `--no-save` (só imprime). |
| `backfill_history.py` | manual, uma vez por ativo novo | Carga histórica de `cotacoes_diarias` (`--period=1y\|5y\|10y\|max`, padrão 5 anos). Idempotente (upsert por `acao_id + data`). |
| `export_sync.py` | desenvolvimento | Exporta `acoes.csv` e `movimentacoes.csv` para `scripts/python/sync/`, referenciando o ativo pelo **ticker** (nunca pelo id). |
| `import_sync.py` | servidor | Importa os CSV: upsert de ações por ticker e insert de movimentações com id novo, pulando duplicadas (`--permitir-duplicados` força). Aceita um `aportes.csv` antigo, importado como compras. |

### 5.2 Módulos de apoio

| Módulo | Responsabilidade |
| --- | --- |
| `config/settings.py` | Carrega o `.env` do Laravel (banco) e o `.env` local (Telegram); semente de tickers para a primeira carga |
| `config/logging_config.py` | Log diário em `scripts/python/logs/coletor-AAAA-MM-DD.log` + console |
| `database/connection.py` | Conexão `psycopg2` com o Postgres |
| `database/repository.py` | Todo o SQL do coletor (upserts de `acoes`/`cotacoes`/`cotacoes_diarias`, log em `coletas`) |
| `services/stock_service.py` | Foto do momento (`yfinance .info`) + histórico do mês |
| `services/history_service.py` | Série diária (`yfinance .history`, `auto_adjust=False` para ter `Close` e `Adj Close`) |
| `services/telegram_bot.py` | Envio de mensagens no Telegram — **ainda não usado** |
| `Docs/REFERENCIA_YFINANCE_INFO.md` | Referência dos campos do `.info` do yfinance |

Dependências (`requirements.txt`): `yfinance`, `python-dotenv`, `psycopg2-binary`,
`pyTelegramBotAPI`.

### 5.3 Agendamento

Em produção, o `fetch_stocks.py` roda no **cron do servidor** a cada 15 minutos nos dias
úteis, no fuso `America/Sao_Paulo`, com `flock` para evitar duas coletas ao mesmo tempo.
A última execução do dia precisa ser **depois das 18h** para gravar o fechamento definitivo.
Exemplo de crontab em `scripts/python/README.md`.

### 5.4 Sincronização dev → produção

Ativos e movimentações cadastrados no desenvolvimento vão para o servidor por CSV:

```bash
# no dev
.venv/bin/python export_sync.py          # gera sync/acoes.csv e sync/movimentacoes.csv
# copie a pasta sync/ para o servidor; lá:
php artisan migrate                      # sempre antes do import
.venv/bin/python import_sync.py
```

Os CSV ficam em `scripts/python/sync/` (gitignored, contêm dados da carteira). Apague-os
depois de importar.

---

## 6. Rotas

### 6.1 Web (`routes/web.php`, controller `App\Http\Controllers\Web\PainelController`)

| Rota | Nome | Tela |
| --- | --- | --- |
| `GET /` | `painel.index` | Monitor de ações |
| `GET /fiis` | `painel.fiis` | Monitor de FIIs |
| `GET /etfs` | `painel.etfs` | Monitor de ETFs |
| `GET /acoes/{acao}` | `painel.show` | Detalhe do ativo |
| `GET /carteira` | `painel.carteira` | Meus ativos (`?data=Y-m-d` para a foto do dia) |
| `GET /carteira/evolucao` | `painel.carteira.evolucao` | Evolução do patrimônio (`?periodo=` ou `?de=&ate=`) |
| `GET /movimentacoes` | `painel.movimentacoes.index` | Lista de movimentações |
| `GET·POST /movimentacoes/registrar` | `painel.movimentacoes.create` · `.store` | Registrar compra/venda |
| `GET /movimentacoes/{movimentacao}/editar` | `painel.movimentacoes.edit` | Editar |
| `PUT·DELETE /movimentacoes/{movimentacao}` | `painel.movimentacoes.update` · `.destroy` | Salvar / excluir |
| `GET /calculo-cotas` | `painel.calculo` | Cálculo de cotas |
| `GET /porcentagem` | `painel.porcentagem` | Cálculo de porcentagem |
| `GET·POST /adicionar` | `painel.adicionar` · `.store` | Adicionar ticket |
| `GET /cadastro/ativos` | `painel.ativos.index` | Gerenciar ativos |
| `GET /cadastro/ativos/{acao}` | `painel.ativos.show` | Detalhes do cadastro |
| `GET /cadastro/ativos/{acao}/editar` · `PUT` · `DELETE` | `painel.ativos.edit` · `.update` · `.destroy` | Editar / excluir ativo |
| `/aportes/registrar`, `/aportes/meus-ativos`, `/aportes/registros` | — | Redirecionam para as URLs novas |

### 6.2 API (`routes/api.php`) — ver [2.6](#26-api-rest)

---

## 7. Estrutura do código

Fluxo canônico: **FormRequest → DTO → Service → Repository → ResponseDTO**. Interfaces de
Service e Repository em `app/Contracts`, vinculadas no `AppServiceProvider`.

```
app/
├── Contracts/
│   ├── Repositories/   AcaoRepositoryInterface, MovimentacaoRepositoryInterface,
│   │                   CotacaoDiariaRepositoryInterface, ColetaRepositoryInterface
│   └── Services/       AcaoServiceInterface, MovimentacaoServiceInterface,
│                       CarteiraServiceInterface, ColetaServiceInterface
├── DTOs/               AcaoDTO, AcaoResponseDTO, CotacaoResponseDTO, CotacaoDiariaResponseDTO,
│                       ColetaResponseDTO, MovimentacaoDTO, MovimentacaoResponseDTO,
│                       PosicaoResponseDTO
├── Enums/              TipoAtivo (acao/fii/etf), TipoMovimentacao (compra/venda)
├── Helpers/            ApiResponse (envelope JSON da API)
├── Http/
│   ├── Controllers/    AcaoController, MovimentacaoController (API)
│   │   └── Web/        PainelController (todas as telas)
│   └── Requests/       Acao*, Movimentacao*, Filter*IndexRequest, AdicionarTicketRequest,
│                       CarteiraRequest, EvolucaoRequest
├── Models/             Acao, Cotacao, CotacaoDiaria, Coleta, Movimentacao
├── Repositories/       AcaoRepository, MovimentacaoRepository, CotacaoDiariaRepository, ColetaRepository
└── Services/
    ├── AcaoService           listagem, CRUD, histórico intradia/diário, séries do sparkline
    ├── MovimentacaoService   CRUD, validação de saldo, posições (ao vivo ou numa data)
    ├── CarteiraService       calendário, foto do dia, evolução do patrimônio
    ├── ColetaService         última execução do coletor
    └── Carteira/LivroCarteira   aplica compras/vendas: cotas, custo e lucro realizado

resources/
├── views/
│   ├── layouts/        monitor.blade.php (layout novo), app.blade.php (layout legado)
│   ├── components/     asset-card, asset-row, holding-card, holding-row, sparkline, range-bar,
│   │                   badge-var, ticker, view-toggle, donut, cost-bar, date-picker, evolucao-chart
│   ├── acoes/          index (monitor), show, adicionar, cadastro, detalhes, editar, calculo, porcentagem
│   ├── carteira/       index (Meus ativos), evolucao
│   └── movimentacoes/  index, registrar, editar, _form
├── css/                tokens.css, components.css (design system), global.css (legado), pages/*.css
└── js/                 sentinel.js (sistema), components/date-picker.js, pages/*.js

scripts/python/         coletor (ver seção 5)
app/Docs/               handoffs de layout (Claude Design) e documentação dos comandos artisan
```

---

## 8. Front-end e design system

- **Sem framework JS.** Cada tela é Blade + um CSS e um JS próprios (`resources/css/pages`,
  `resources/js/pages`), carregados via Vite. JS só como *progressive enhancement*: sem JS,
  as telas continuam navegáveis (o calendário cai em `<input type="date">`, o gráfico e as
  tabelas são renderizados no servidor).
- **Design system** (redesign a partir do handoff em `app/Docs/layout/`):
  - `tokens.css` — cores, espaçamento (base 4), raios, tipografia e movimento, com tema
    claro (branco/azul) e escuro (preto/roxo) via `<html data-theme>`;
  - `components.css` — topbar com menus, drawer, cabeçalho de página, KPIs, chips/badges,
    toolbar, segmentado, busca, botões, cards, alertas, estados vazios, skeleton e dataviz
    (sparkline, faixa do dia, barra valor × custo, donut, calendário, lista densa).
- **Dois layouts:** `layouts.monitor` (novo: monitores, carteira, evolução) e `layouts.app`
  (legado: cadastro, ferramentas, formulários de movimentação).
- **Preferências salvas no navegador:** tema (`sentinel-theme`) e grade/lista
  (`sentinel-layout`), aplicadas antes do primeiro paint para não piscar.
- **Gráficos em SVG puro**, desenhados no servidor, com tooltip/crosshair em JS e tabela de
  dados equivalente (acessibilidade).
- Responsivo de 375px em diante; nav completa no topo a partir de 1024px.

---

## 9. Setup, execução e deploy

### Requisitos
PHP 8.2+, Composer, Node 20+, PostgreSQL e Python 3.10+.

### Desenvolvimento
```bash
composer install
cp .env.example .env && php artisan key:generate     # configure DB_* (pgsql)
php artisan migrate
npm install && npm run dev                           # ou npm run build
php artisan serve                                    # http://127.0.0.1:8000

# coletor
cd scripts/python
python -m venv .venv && .venv\Scripts\activate       # Linux: source .venv/bin/activate
pip install -r requirements.txt
python backfill_history.py                           # carga histórica (uma vez)
python fetch_stocks.py                               # coleta do momento
```

### Deploy (servidor Linux)
`bash deploy.sh` na raiz: `git pull` → `composer install` → `php artisan migrate --force` →
`npm install` → `npm run build` → `php artisan optimize:clear`. A coleta segue pelo cron.

### Comandos artisan do template
`cls` (limpa caches), `make:service`, `make:crud`, `make:abstract`, `make:bind`,
`sync:contract`, `postman:generate`. Detalhes em `app/Docs/Comandos/README.md`.

---

## 10. Limitações conhecidas

- **Proventos não entram na rentabilidade.** Ela mede só a variação de preço; numa
  carteira com muitos FIIs, o retorno real fica subestimado.
- **Desdobramentos e grupamentos.** O Yahoo corrige o preço para trás, mas a quantidade
  das movimentações não muda; num ativo que desdobrou depois da compra, valores ficam
  distorcidos. Ainda não há evento de desdobramento no livro.
- **Histórico da carteira começa na primeira movimentação.** Os anos de cotações só viram
  histórico de patrimônio se as compras antigas forem cadastradas com a data real.
- **Fuso horário do Laravel em UTC** (`config/app.php`), enquanto o coletor grava
  `capturado_em` no horário local da B3. Entre 21h e 0h (horário de Brasília), o "hoje" do
  Laravel já é o dia seguinte, o que afeta validações de data e o limite do calendário.
- **Sem autenticação nas telas.** O painel é pessoal; JWT/Sanctum do template não estão
  ligados às rotas do Sentinel.
- **Testes.** A suíte do template não sobe (`app/Console/Commands/MakeDTO.php` declara a
  classe `MakeService`, duplicada) e ainda não há testes do domínio do Sentinel; o
  `phpunit.xml` usa SQLite em memória, mas parte das consultas é específica do Postgres.
- **Ativo sem fechamento diário** (recém-cadastrado, antes do backfill) vale o custo na
  evolução e aparece "sem cotação" na foto do dia.

---

## 11. Ideias de evolução

Ponto de partida para priorizar melhorias:

**Carteira e análise**
- Resumo **mês a mês** (patrimônio no fim do mês, aportes, ganho, rentabilidade) em tabela.
- **Comparação com o Ibovespa** usando o BOVA11 (já cadastrado) como referência, no gráfico
  e na rentabilidade do período.
- **Proventos:** registrar dividendos/rendimentos recebidos e somar à rentabilidade
  (retorno total), ou usar o `fechamento_ajustado` como alternativa.
- **Eventos corporativos** no livro (desdobramento, grupamento, bonificação).
- **Taxas/corretagem** por movimentação (entram no custo e reduzem o lucro na venda).
- **Lucro realizado e IR:** apuração mensal por classe (isenção de R$ 20 mil em ações,
  20% em FIIs), base para o DARF.
- Curva **intradia do patrimônio** num dia escolhido, usando os snapshots de 15 minutos.
- **Metas de alocação** por ativo/classe e sugestão de rebalanceamento integrada ao
  cálculo de cotas.

**Monitoramento**
- **Alertas** (preço-alvo, queda/alta acima de X%, mínima do mês) pelo bot do Telegram
  que já existe no coletor.
- Indicadores fundamentalistas na listagem (DY, P/L, P/VP) com filtros.
- Watchlists além dos três monitores fixos.

**Plataforma**
- Ajustar o fuso do Laravel para `America/Sao_Paulo`.
- Autenticação nas telas, caso o painel fique acessível fora da rede local.
- Testes do domínio (livro da carteira, validação de saldo, evolução/TWR) em PHPUnit
  contra Postgres.
- Cache dos fechamentos para períodos longos (5 anos × muitos ativos), se a evolução ficar lenta.

---

## 12. Herança do template

O projeto nasceu do esqueleto `dtic/php-skeleton` (API Laravel com DTOs, Services e JWT).
Continuam no código, **sem uso pelo Sentinel**: integração SOAP com o SIGA
(`app/Clients`, `app/Adapters`, `app/Builders`, `Http/Controllers/Siga`, `Services/Siga`,
`DTOs/Siga`), LDAP, login Google (Socialite), exportação PDF (DomPDF), Excel
(maatwebsite/excel), JWT/Sanctum, middlewares de admin/XSS, regras de CPF/CNPJ/senha,
`config/telescope.php` (sem o pacote instalado), `TestController`, `DogRepository` e os
testes correspondentes. Podem ser removidos numa limpeza futura.

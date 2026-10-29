# Coletor Python — Sentinel

Coletor de dados de mercado (via [yfinance](https://pypi.org/project/yfinance/))
do projeto Sentinel. Vive **separado do Laravel**, mas no mesmo repositório, e
compartilha o **mesmo banco Postgres**.

> A partir desta versão o coletor roda **direto pelo Python** (sem comandos
> `php artisan`). O agendamento é feito no **cron do servidor**, chamando o
> interpretador do `venv`. Este README é a referência central: estrutura,
> execução, agendamento e o detalhamento de **cada função**.

---

## Estrutura

```
scripts/python/
├── config/
│   ├── settings.py          # carrega .env (Laravel + local) e a semente de tickers
│   └── logging_config.py    # logger com um arquivo por dia em logs/
├── database/
│   ├── connection.py        # conexão psycopg2 com o Postgres
│   └── repository.py        # todo o SQL: upserts de acoes/cotacoes/diarias + log de coleta
├── services/
│   ├── stock_service.py     # foto do momento (yfinance .info)
│   ├── history_service.py   # série diária (yfinance .history)
│   └── telegram_bot.py      # envio de mensagens no Telegram (ainda não usado)
├── fetch_stocks.py          # ENTRADA: coleta do dia (snapshot + diário + log)
├── backfill_history.py      # ENTRADA: carga histórica dos fechamentos diários
├── export_sync.py           # ENTRADA: exporta acoes/aportes p/ CSV (rodar no dev)
├── import_sync.py           # ENTRADA: importa os CSV no banco (rodar no servidor)
├── requirements.txt
├── .env.example
├── sync/                    # CSV de sincronização dev -> servidor (gitignored)
└── logs/                    # gerado em runtime (um arquivo por dia)
```

Separação de responsabilidades: **configuração** (`config/`), **banco**
(`database/`), **serviços** de dados externos (`services/`) e os dois
**scripts de entrada** que orquestram.

---

## Setup do ambiente

### Linux (produção)
```bash
cd scripts/python
python3 -m venv .venv          # pode exigir: apt install python3-venv
source .venv/bin/activate
pip install -r requirements.txt
deactivate
```

### Windows (desenvolvimento)
```bash
cd scripts/python
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

Dependências (`requirements.txt`): `yfinance`, `python-dotenv`,
`psycopg2-binary` (Postgres), `pyTelegramBotAPI` (Telegram, futuro).

### Variáveis de ambiente
- **Banco de dados:** lidas do **`.env` do Laravel** (raiz do projeto) — não repita aqui.
- **Específicas do coletor** (Telegram): copie `.env.example` para `.env` e preencha.
  ```bash
  cp .env.example .env
  ```
- O `settings.py` resolve os dois `.env` por caminho **absoluto** (via `__file__`),
  então o script funciona rodado de qualquer diretório (incluindo pelo cron).

---

## Execução

Sempre com o Python do `venv`. No Linux, chame o binário direto (não precisa
"ativar" o venv — ativar é só mexer no `PATH`):

```bash
# a partir de scripts/python/
.venv/bin/python fetch_stocks.py                 # todos os tickers ativos no banco
.venv/bin/python fetch_stocks.py PETR4.SA VALE3.SA   # só estes
.venv/bin/python fetch_stocks.py PETR4.SA --tipo=fii # tipo só vale p/ ticker inédito
.venv/bin/python fetch_stocks.py --no-save           # só imprime, não grava (dispensa o psycopg2)
```

### Carga histórica (backfill) — rodar **uma vez** após o deploy
Preenche `cotacoes_diarias` com a série diária completa. É **idempotente**
(upsert por `acao_id + data`), então pode repetir sem duplicar:

```bash
.venv/bin/python backfill_history.py                 # ativos do banco, 5 anos
.venv/bin/python backfill_history.py --period=10y    # 1y | 5y | 10y | max
.venv/bin/python backfill_history.py VALE3.SA --period=1y --pausa=0.5
```
Referência: 43 ativos com `--period=5y` → ~45s e ~52 mil dias gravados.

---

## Agendamento (cron do servidor)

A coleta recorrente roda **direto no cron**, apontando para o Python do `venv`.
Janela sugerida: dias úteis, de 30 em 30 min, cobrindo até depois do
fechamento da B3 (a última execução grava o candle definitivo do dia).

```bash
crontab -e
```
```cron
# Fuso da B3 (importante se o servidor estiver em UTC — confira com: timedatectl)
CRON_TZ=America/Sao_Paulo

# flock evita duas coletas concorrentes; o >> captura erros de arranque
*/30 10-19 * * 1-5 /usr/bin/flock -n /tmp/sentinel-collect.lock \
  /CAMINHO/scripts/python/.venv/bin/python /CAMINHO/scripts/python/fetch_stocks.py \
  >> /CAMINHO/scripts/python/logs/cron.log 2>&1
```

> O `backfill_history.py` **não** entra no cron — é uma carga pontual.

---

## Sincronização dev → servidor (tickets + aportes)

Cadastro de ativos e aportes feito no **dev** é levado ao **servidor** por CSV,
sem mexer no banco à mão. Dois scripts:

```bash
# 1) no DEV — gera scripts/python/sync/acoes.csv e aportes.csv
.venv/bin/python export_sync.py

# 2) copie a pasta sync/ para o servidor e rode lá
.venv/bin/python import_sync.py
#   .venv/bin/python import_sync.py --permitir-duplicados   # força aportes idênticos
```

Depois de importar, **limpe/apague os CSV** para não reimportar por engano.

Como os conflitos entre ambientes são tratados:

- **`acao_id` difere entre bancos** → o aporte é exportado/importado pelo
  **ticker** (chave natural, `acoes.ticker` é único); o `import` resolve o
  `acao_id` local. Nenhum id de dev é usado no servidor.
- **ids de aportes** → não são exportados; o servidor gera ids novos, então não
  colidem com aportes já registrados lá.
- **duplicidade** → `acoes` é upsert por ticker (idempotente). `aportes` não têm
  chave única, então o `import` **pula** um aporte idêntico já existente (mesmo
  `acao_id` + `preco_pago` + `quantidade` + `data`). Dois aportes reais iguais no
  mesmo dia só entram com `--permitir-duplicados`.
- **metadados de `acoes`** → no conflito, `tipo` e `ativo` vêm do dev (fonte do
  cadastro); `nome/setor/segmento` usam COALESCE para não apagar o que o coletor
  já preencheu no servidor.
- **pré-requisito** → as migrations precisam estar aplicadas nos dois ambientes.

Os CSV ficam em `scripts/python/sync/`, **gitignored** (o `aportes.csv` tem
dados da carteira).

## O que vai para o banco

| Tabela             | Conteúdo                                     | Quem grava                     |
| ------------------ | -------------------------------------------- | ------------------------------ |
| `acoes`            | metadado do ativo (upsert pelo ticker)       | `fetch_stocks.py`              |
| `cotacoes`         | snapshot intradiário (uma linha por coleta)  | `fetch_stocks.py`              |
| `cotacoes_diarias` | fechamento do dia (upsert por ativo + data)  | `fetch_stocks.py` e o backfill |
| `coletas`          | log de execução (início, fim, ok/falha)      | ambos os scripts               |

`cotacoes_diarias` é a série estável (vem do `history()`, ajustada por
desdobramentos/proventos). Como a coleta traz o histórico do **mês inteiro** na
mesma requisição que já calcula o mín/máx, um dia de pregão perdido é
recuperado na execução seguinte — o buraco se fecha sozinho.

---

## Referência das funções

### `fetch_stocks.py` — entrada da coleta do dia
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `main` | `() -> None` | Orquestra a coleta: abre o log em `coletas`, chama `_executar`, e no `finally` fecha o log (se morrer no meio, `fim` fica nulo = "não terminou"). |
| `_executar` | `(args) -> (ok, total, registros)` | Decide a fonte dos tickers: (1) os passados na linha de comando; (2) os ativos no banco; (3) a semente de `settings.py` quando o banco está vazio. |
| `_processar` | `(tickers, tipo, salvar) -> (ok, total, registros)` | Busca via `fetch_all`, imprime, e (se `salvar`) grava snapshot + histórico do mês. `registros` = snapshots + diários. |
| `print_quote` | `(quote) -> None` | Imprime a cotação formatada no console (uso manual/log). |
| `_fmt` | `(value) -> str` | Formata número/None no padrão pt-BR para exibição. |
| `_opt` | `(args, name, default)` | Extrai o valor de uma opção `--nome=valor`. |

### `backfill_history.py` — entrada da carga histórica
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `main` | `() -> None` | Lê `--period`/`--pausa`, resolve os tickers, e para cada um busca a série (`fetch_history`) e grava (`salvar_historico_diario`), com pausa entre ativos; registra tudo em `coletas` (origem `backfill`). |
| `_tickers` | `(args) -> list[str]` | Tickers informados na linha de comando ou, na ausência, os ativos do banco (`listar_acoes_ativas`). |
| `_opt` | `(args, name, default)` | Extrai o valor de uma opção `--nome=valor`. |

### `export_sync.py` — exporta acoes/aportes para CSV (dev)
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `main` | `() -> None` | Garante a pasta de saída e gera `acoes.csv` e `aportes.csv`. |
| `_exportar` | `(cur, sql, colunas, caminho) -> int` | Roda a query e grava cabeçalho + linhas no CSV; retorna a contagem. |
| `_opt` | `(args, name, default)` | Extrai o valor de uma opção `--nome=valor` (ex.: `--dir`). |

### `import_sync.py` — importa os CSV no banco (servidor)
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `main` | `() -> None` | Lê os CSV, importa `acoes` (antes) e `aportes` (depois) e imprime o resumo. |
| `importar_acoes` | `(conn, linhas) -> int` | Upsert por ticker (tipo/ativo do dev; metadados via COALESCE). |
| `importar_aportes` | `(conn, linhas, permitir_dup) -> (inseridos, pulados, sem_ticker)` | Resolve o `acao_id` pelo ticker, pula duplicados (salvo `--permitir-duplicados`) e insere. |
| `_ler_csv` | `(caminho) -> list[dict]\|None` | Lê o CSV como dicts; `None` se o arquivo não existir. |
| `_none` / `_bool` / `_dec` / `_date` / `_dt` | `(value)` | Normalizam células do CSV (vazio→None, texto→bool/Decimal/date/datetime). |
| `_opt` | `(args, name, default)` | Extrai o valor de uma opção `--nome=valor`. |

### `services/stock_service.py` — foto do momento (`.info`)
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `fetch_quote` | `(ticker) -> dict` | Busca um ativo no yfinance e devolve os campos-chave (preço, abertura, mín/máx dia e 52s, variação, dividendos, mín/máx do mês e o histórico do mês). |
| `fetch_all` | `(tickers) -> list[dict]` | Chama `fetch_quote` para vários, pulando e logando os que falharem. |
| `_historico_mes` | `(yf_ticker) -> list[dict]` | Candles diários do mês corrente (serve ao mín/máx do mês **e** alimenta `cotacoes_diarias`). |
| `_change_percent` | `(info, price, previous_close)` | Variação % do dia: usa `regularMarketChangePercent` ou calcula pelo fech. anterior. |
| `_change_abs` | `(info, price, previous_close)` | Variação em R$ do dia, com fallback `preço - fech. anterior`. |
| `_none_if_zero` | `(value)` | Converte `0.0` (open/dayLow/dayHigh fora do pregão) em `None`, para não gravar "0" enganoso. |

### `services/history_service.py` — série diária (`.history`)
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `fetch_history` | `(yf_ticker, period=None, start=None) -> list[dict]` | Busca o histórico diário (`auto_adjust=False` para ter `Close` e `Adj Close`) e devolve linhas prontas; erro vira lista vazia. |
| `rows_from_history` | `(hist) -> list[dict]` | Converte o DataFrame do yfinance em linhas (`data`, abertura, máxima, mínima, fechamento, fechamento_ajustado, volume); descarta dias sem fechamento. |
| `extremos` | `(linhas) -> (min, min_data, max, max_data)` | Mínima/máxima (não ajustadas) e suas datas dentro das linhas. |
| `_num` / `_vol` | `(value)` | Converte para float/int tratando NaN/None do pandas como ausente. |
| `_coluna` | `(row, *nomes)` | Primeira coluna existente entre os nomes (tolera variação do yfinance). |

### `database/repository.py` — todo o SQL
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `listar_tickers_ativos` | `() -> list[str]` | Tickers com `ativo = true` (fonte de verdade da coleta). |
| `listar_acoes_ativas` | `() -> list[dict]` | `id` + `ticker` dos ativos ativos (usado pelo backfill). |
| `salvar_cotacoes` | `(quotes, tipo="acao") -> int` | Upsert em `acoes` (preserva tipo/ativo no conflito) + insert em `cotacoes`; um ativo por transação. Retorna quantas gravou. |
| `salvar_historico_diario` | `(itens) -> int` | Upsert em `cotacoes_diarias` por `(acao_id, data)`; aceita o formato `{"ticker", "linhas"}` da coleta e do backfill. Retorna linhas gravadas. |
| `registrar_inicio_coleta` | `(origem="coleta") -> int\|None` | Abre a linha de log em `coletas` e devolve o id (erro aqui não aborta a coleta). |
| `finalizar_coleta` | `(id, ok, falha, registros, erro=None) -> None` | Fecha a linha de log (`fim`, contadores, erro). `fim` nulo = execução que não terminou. |
| `_to_int` / `_ts_to_date` | `(value)` | Helpers: int para volume; timestamp Unix → `date`. |

### `database/connection.py`
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `get_connection` | `() -> psycopg2.connection` | Abre a conexão com o Postgres usando as credenciais de `config.settings` (import do psycopg2 adiado: `--no-save` roda sem o driver). |

### `config/settings.py`
Carrega o `.env` do Laravel e depois o `.env` local (com `override=True`), e expõe:
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — conexão.
- `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` — notificações (futuro).
- `TICKERS_SEED` (por tipo: `acao`/`fii`/`etf`) e `TICKERS` (lista achatada) — semente usada **só** na primeira carga, quando não há ticker no banco.

### `config/logging_config.py`
| Função | Assinatura | O que faz |
| --- | --- | --- |
| `setup_logging` | `(name=__name__) -> Logger` | Logger em nível INFO com saída para arquivo (`logs/coletor-AAAA-MM-DD.log`) e console; evita handlers duplicados. |
| `_log_file` | `() -> Path` | Garante `logs/` e devolve o caminho do arquivo do dia. |

### `services/telegram_bot.py` (ainda não usado)
| Membro | Assinatura | O que faz |
| --- | --- | --- |
| `TelegramBot.__init__` | `(token=None, chat_id=None)` | Valida token/chat_id (de `settings` ou explícitos) e instancia o `telebot` (import adiado). |
| `TelegramBot.send_message` | `(msg) -> None` | Envia uma mensagem ao chat configurado; loga erro em vez de estourar. |

---

## Logs e observabilidade

- **Arquivo:** `scripts/python/logs/coletor-AAAA-MM-DD.log` (um por dia).
- **Banco:** cada execução deixa uma linha em `coletas`, exibida no cabeçalho do
  painel. Execução que morre no meio fica com `fim` nulo e aparece como
  **não terminou** — falha silenciosa era o pior defeito do coletor.

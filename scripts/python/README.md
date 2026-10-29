# Coletor Python — Sentinel

Script isolado que busca dados de ativos (via [yfinance](https://pypi.org/project/yfinance/))
para o projeto Sentinel. Fica **separado do Laravel**, mas dentro do mesmo
repositório, e compartilha o banco de dados.

## Estrutura (separação de responsabilidades)

```
scripts/python/
├── config/
│   ├── settings.py          # carrega .env + semente de tickers (config central)
│   └── logging_config.py    # setup do logger (um arquivo por dia em logs/)
├── database/
│   ├── connection.py        # conexão com o Postgres (isolada)
│   └── repository.py        # todo o SQL: upserts de acoes/cotacoes/diarias + log
├── services/
│   ├── stock_service.py     # foto do momento (yfinance .info)
│   ├── history_service.py   # série diária (yfinance .history)
│   └── telegram_bot.py      # envio de mensagens no Telegram (Fase 5)
├── fetch_stocks.py          # entrada: coleta do dia (snapshot + diário + log)
├── backfill_history.py      # entrada: carga histórica dos fechamentos
├── requirements.txt
├── .env.example
└── .gitignore
```

Cada responsabilidade em seu arquivo: **configuração**, **banco**, **serviços**
(yfinance, Telegram) e os **scripts de entrada** que orquestram.

## O que vai para o banco

| Tabela             | Conteúdo                                     | Quem grava                        |
| ------------------ | -------------------------------------------- | --------------------------------- |
| `acoes`            | metadado do ativo (upsert pelo ticker)       | `fetch_stocks.py`                 |
| `cotacoes`         | snapshot intradiário (uma linha por coleta)  | `fetch_stocks.py`                 |
| `cotacoes_diarias` | fechamento do dia (upsert por ativo + data)  | `fetch_stocks.py` e o backfill    |
| `coletas`          | log de execução (início, fim, ok/falha)      | ambos os scripts                  |

`cotacoes_diarias` é a série estável: alimentada pelo `history()`, que vem
consolidado e ajustado por desdobramentos e proventos. O snapshot continua
servindo o "preço agora". Como a coleta traz o histórico do **mês inteiro** na
mesma requisição que já calculava o mín/máx, um dia de pregão que a coleta
perdeu é recuperado na execução seguinte.

## Configuração do ambiente

### Windows (desenvolvimento)
```bash
cd scripts/python
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

### Linux (produção)
```bash
cd scripts/python
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## Variáveis de ambiente

- **Banco de dados:** lidas do `.env` do **Laravel** (raiz do projeto) — não repita aqui.
- **Específicas do coletor** (ex.: Telegram): copie `.env.example` para `.env` e preencha.
- **`SENTINEL_PYTHON`** (no `.env` do Laravel): interpretador usado pelos comandos
  artisan. O padrão é o venv acima; defina só se usar outro Python.

```bash
cp .env.example .env
```

## Execução

Pelo Laravel (preferido — resolve o interpretador e o diretório):

```bash
php artisan sentinel:collect                      # todos os ativos cadastrados
php artisan sentinel:collect PETR4.SA VALE3.SA    # só estes
php artisan sentinel:collect --no-save            # só imprime, não grava
```

Direto em Python, com o venv ativado:

```bash
python fetch_stocks.py
python fetch_stocks.py PETR4.SA --tipo=acao
```

## Carga histórica (backfill)

Puxa a série diária completa e preenche `cotacoes_diarias`. É **idempotente**
(upsert por ativo + data), então pode ser repetido sem duplicar nada:

```bash
php artisan sentinel:backfill                 # ativos cadastrados, 5 anos
php artisan sentinel:backfill --period=10y
php artisan sentinel:backfill VALE3.SA --period=1y
```

Referência: 43 ativos com `--period=5y` levaram ~45s e gravaram ~52 mil dias.

## Agendamento

O agendamento **não** mora em cron por script: mora em `routes/console.php`,
versionado com o código. Dias úteis, de 30 em 30 minutos entre 10h e 18h30
(fuso da B3), com a última execução depois do fechamento — é ela que grava o
candle definitivo do dia.

O sistema operacional precisa de **uma única** entrada, que chama o scheduler
do Laravel de minuto em minuto:

```bash
# Linux (crontab -e)
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

No Windows, crie uma tarefa no Agendador de Tarefas com repetição de 1 minuto
executando `php artisan schedule:run` na pasta do projeto. Para testar sem
esperar o relógio:

```bash
php artisan schedule:list   # mostra a janela e o próximo disparo
php artisan schedule:work   # roda o scheduler em primeiro plano
```

Toda execução deixa uma linha em `coletas`, exibida no cabeçalho do painel.
Execução que morre no meio fica com `fim` nulo e aparece como **não terminou**
— falha silenciosa era o pior defeito do coletor.

# Coletor Python — Sentinel

Script isolado que busca dados de ativos (via [yfinance](https://pypi.org/project/yfinance/))
para o projeto Sentinel. Fica **separado do Laravel**, mas dentro do mesmo
repositório, e compartilha o banco de dados.

## Estrutura (separação de responsabilidades)

```
scripts/python/
├── config/
│   ├── settings.py          # carrega .env + lista de tickers (config central)
│   └── logging_config.py    # setup do logger
├── database/
│   └── connection.py        # conexão com o Postgres (isolada; usada nas próximas etapas)
├── services/
│   ├── stock_service.py     # busca os dados via yfinance
│   └── telegram_bot.py      # envio de mensagens no Telegram (próximas etapas)
├── fetch_stocks.py          # ponto de entrada (v1: busca e imprime)
├── requirements.txt
├── .env.example
└── .gitignore
```

Cada responsabilidade em seu arquivo: **configuração**, **banco**, **serviços**
(yfinance, Telegram) e o **script de entrada** que orquestra.

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

```bash
cp .env.example .env
```

## Execução

```bash
# todos os tickers de config/settings.py
python fetch_stocks.py

# um ou mais tickers específicos
python fetch_stocks.py PETR4.SA VALE3.SA
```

> v1 apenas **busca e imprime**. Nas próximas etapas o mesmo script passará a
> gravar as cotações no banco (`database/connection.py`) e/ou notificar via
> Telegram (`services/telegram_bot.py`).

## Agendamento (futuro, no servidor Linux)

```bash
# a cada 30 min, seg–sex, das 10h às 18h
*/30 10-18 * * 1-5 /caminho/scripts/python/.venv/bin/python /caminho/scripts/python/fetch_stocks.py >> /var/log/sentinel-coletor.log 2>&1
```

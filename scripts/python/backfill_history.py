"""Carga histórica dos fechamentos diários (backfill).

Puxa a série diária completa de cada ativo pelo history() do yfinance e grava
em `cotacoes_diarias`. Diferente do coletor, que acompanha o presente, este
script existe para resolver o passado: em uma execução o projeto deixa de ter
7 dias de dados e passa a ter anos.

É idempotente (upsert por acao_id + data), então pode ser repetido à vontade.

Execução:
    python backfill_history.py                       # ativos do banco, 5 anos
    python backfill_history.py --period=10y
    python backfill_history.py VALE3.SA GGRC11.SA    # só estes
"""

import sys
import time

import yfinance as yf

from config.logging_config import setup_logging
from database.repository import (
    finalizar_coleta,
    listar_acoes_ativas,
    registrar_inicio_coleta,
    salvar_historico_diario,
)
from services.history_service import fetch_history

for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

logger = setup_logging("backfill_history")

PERIOD_PADRAO = "5y"
# Pausa entre ativos: 40+ requisições de histórico longo em sequência é o
# cenário clássico de throttle do Yahoo. Meio segundo resolve e não pesa.
PAUSA_PADRAO = 0.5


def _opt(args: list[str], name: str, default=None):
    """Extrai o valor de uma opção no formato --nome=valor."""
    prefix = name + "="
    for arg in args:
        if arg.startswith(prefix):
            return arg[len(prefix):]
    return default


def _tickers(args: list[str]) -> list[str]:
    """Tickers do argumento ou, na ausência deles, os ativos do banco."""
    informados = [a for a in args if not a.startswith("--")]
    if informados:
        return informados

    return [acao["ticker"] for acao in listar_acoes_ativas()]


def main() -> None:
    args = sys.argv[1:]
    period = _opt(args, "--period", PERIOD_PADRAO)
    pausa = float(_opt(args, "--pausa", PAUSA_PADRAO))
    tickers = _tickers(args)

    if not tickers:
        logger.warning("Nenhum ativo para processar. Cadastre tickers ou informe-os na linha de comando.")
        return

    logger.info("Backfill de %d ativo(s), período=%s.", len(tickers), period)

    coleta_id = registrar_inicio_coleta("backfill")
    ok = falha = registros = 0
    erro = None

    try:
        for indice, ticker in enumerate(tickers, start=1):
            linhas = fetch_history(yf.Ticker(ticker), period=period)

            if not linhas:
                falha += 1
                logger.warning("[%d/%d] %s: nenhum candle retornado.", indice, len(tickers), ticker)
                continue

            gravadas = salvar_historico_diario([{"ticker": ticker, "linhas": linhas}])
            registros += gravadas
            ok += 1
            logger.info("[%d/%d] %s: %d dia(s) de %s a %s.", indice, len(tickers), ticker,
                        gravadas, linhas[0]["data"], linhas[-1]["data"])

            if pausa and indice < len(tickers):
                time.sleep(pausa)
    except Exception as e:
        erro = f"{type(e).__name__}: {e}"
        logger.exception("Backfill abortado.")
        raise
    finally:
        finalizar_coleta(coleta_id, ok, falha, registros, erro)
        logger.info("Backfill concluído: %d ok, %d falha(s), %d registro(s).", ok, falha, registros)


if __name__ == "__main__":
    main()

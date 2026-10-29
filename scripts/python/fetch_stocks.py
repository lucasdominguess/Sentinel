"""Script de entrada do coletor.

Busca os ativos no yfinance e grava:
  - `cotacoes`          -> snapshot do momento (série intradiária);
  - `cotacoes_diarias`  -> fechamento do dia, vindo do histórico do mês que o
                           mín/máx já buscava (mesma requisição, zero custo);
  - `coletas`           -> log da própria execução.

Execução (preferir os comandos artisan, que resolvem o interpretador):
    php artisan sentinel:collect
    php artisan sentinel:collect PETR4.SA --tipo=acao

    python fetch_stocks.py            # todos os tickers ativos no banco
    python fetch_stocks.py PETR4.SA   # um ou mais tickers específicos
    python fetch_stocks.py --no-save  # só imprime, não grava
"""

import sys

from config.logging_config import setup_logging
from config.settings import TICKERS_SEED
from database.repository import (
    finalizar_coleta,
    listar_tickers_ativos,
    registrar_inicio_coleta,
    salvar_cotacoes,
    salvar_historico_diario,
)
from services.stock_service import fetch_all

# O console do Windows usa cp1252 por padrão e quebra ao imprimir emojis/acentos.                                                              
# Força UTF-8 na saída (inofensivo no Linux).
for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

logger = setup_logging("fetch_stocks")


def _fmt(value) -> str:
    """Formata números/None para exibição amigável."""
    if value is None:
        return "-"
    if isinstance(value, (int, float)):
        return f"{value:,.2f}".replace(",", "_").replace(".", ",").replace("_", ".")
    return str(value)


def print_quote(quote: dict) -> None:
    print(f"📈 {quote['ticker']}  —  {quote['name'] or 'N/D'}  [{quote.get('market_state') or '-'}]")
    print(f"   💰 Preço:          {_fmt(quote['price'])} {quote.get('currency') or ''}")
    print(f"   🔁 Fech. anterior: {_fmt(quote['previous_close'])}")
    print(f"   🔓 Abertura:       {_fmt(quote['open'])}")
    print(f"   ⬇️  Mín. dia:       {_fmt(quote['day_low'])}")
    print(f"   ⬆️  Máx. dia:       {_fmt(quote['day_high'])}")
    print(f"   📊 Var. dia:       {_fmt(quote.get('change_percent'))}%")
    print(f"   📉 Mín. 52s:       {_fmt(quote['week52_low'])}")
    print(f"   📈 Máx. 52s:       {_fmt(quote['week52_high'])}")
    print(f"   🏦 Market Cap:     {_fmt(quote['market_cap'])}")
    print(f"   💵 Div. Yield:     {_fmt(quote['dividend_yield'])}")
    print(f"   📊 P/L:            {_fmt(quote['trailing_pe'])}")
    print(f"   🔊 Vol. médio:     {_fmt(quote['avg_volume'])}")
    print("   " + "-" * 30)


def _opt(args: list[str], name: str, default=None):
    """Extrai o valor de uma opção no formato --nome=valor."""
    prefix = name + "="
    for arg in args:
        if arg.startswith(prefix):
            return arg[len(prefix):]
    return default


def _processar(tickers: list[str], tipo: str, salvar: bool) -> tuple[int, int, int]:
    """Busca os tickers, imprime e (opcionalmente) grava.

    Retorna (ok, total, registros). `registros` soma snapshots intradiários e
    fechamentos diários gravados — é o número que vai para o log de coleta.
    """
    quotes = fetch_all(tickers)
    for quote in quotes:
        print_quote(quote)

    if not salvar:
        return len(quotes), len(tickers), 0

    inseridas = salvar_cotacoes(quotes, tipo=tipo)

    # O histórico do mês vem de brinde na mesma requisição do mín/máx —
    # aproveitamos para manter cotacoes_diarias em dia (e tapar buracos).
    diarias = salvar_historico_diario([
        {"ticker": quote["ticker"], "linhas": quote.get("historico_mes") or []}
        for quote in quotes
    ])

    logger.info("Gravadas %d cotação(ões) e %d fechamento(s) diário(s) [tipo=%s].",
                inseridas, diarias, tipo)

    return len(quotes), len(tickers), inseridas + diarias


def _executar(args: list[str]) -> tuple[int, int, int]:
    """Decide a fonte dos tickers e processa. Retorna (ok, total, registros)."""
    salvar = "--no-save" not in args
    tipo = _opt(args, "--tipo", "acao")
    tickers_cli = [a for a in args if not a.startswith("--")]

    # 1) Tickers específicos na linha de comando (ex.: fetch_stocks.py VALE3.SA --tipo=fii)
    if tickers_cli:
        logger.info("Buscando %d ativo(s) informado(s)...", len(tickers_cli))
        return _processar(tickers_cli, tipo, salvar)

    # 2) Fonte de verdade: ativos marcados como ativos no banco
    ativos = listar_tickers_ativos()
    if ativos:
        logger.info("Buscando %d ativo(s) do banco...", len(ativos))
        # o tipo é preservado no upsert (ON CONFLICT não altera tipo/ativo)
        return _processar(ativos, tipo, salvar)

    # 3) Banco vazio → semente inicial, inserindo cada grupo com o tipo correto
    logger.warning("Nenhum ticker ativo no banco; usando a semente de settings.py (por tipo).")
    total_ok = total_geral = total_registros = 0
    for tipo_seed, lista in TICKERS_SEED.items():
        logger.info("Semente: %d %s(s)...", len(lista), tipo_seed)
        ok, total, registros = _processar(lista, tipo_seed, salvar)
        total_ok += ok
        total_geral += total
        total_registros += registros

    return total_ok, total_geral, total_registros


def main() -> None:
    args = sys.argv[1:]
    salvar = "--no-save" not in args

    print()

    # Toda execução que grava deixa rastro: uma linha em `coletas` aberta aqui e
    # fechada no finally. Se o processo morrer no meio, `fim` fica nulo — e o
    # painel mostra que a coleta não terminou em vez de falhar em silêncio.
    coleta_id = registrar_inicio_coleta("coleta") if salvar else None
    ok = total = registros = 0
    erro = None

    try:
        ok, total, registros = _executar(args)
        logger.info("Concluído: %d de %d ativo(s), %d registro(s).", ok, total, registros)
    except Exception as e:
        erro = f"{type(e).__name__}: {e}"
        logger.exception("Coleta abortada.")
        raise
    finally:
        finalizar_coleta(coleta_id, ok, max(total - ok, 0), registros, erro)


if __name__ == "__main__":
    main()

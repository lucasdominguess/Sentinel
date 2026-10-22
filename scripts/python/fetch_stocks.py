"""Script de entrada do coletor (v1).

Por enquanto apenas busca os ativos configurados e imprime o resultado
no terminal. Nas próximas etapas este mesmo script passará a gravar no
banco (database/connection.py) e/ou notificar (services/telegram_bot.py).

Execução:
    python fetch_stocks.py            # todos os tickers de config/settings.py
    python fetch_stocks.py PETR4.SA   # um ou mais tickers específicos
"""

import sys

from config.logging_config import setup_logging
from config.settings import TICKERS_SEED
from database.repository import listar_tickers_ativos, salvar_cotacoes
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


def _processar(tickers: list[str], tipo: str, salvar: bool) -> tuple[int, int]:
    """Busca os tickers, imprime e (opcionalmente) grava. Retorna (ok, total)."""
    quotes = fetch_all(tickers)
    for quote in quotes:
        print_quote(quote)
    if salvar:
        inseridas = salvar_cotacoes(quotes, tipo=tipo)
        logger.info("Gravadas %d cotação(ões) [tipo=%s].", inseridas, tipo)
    return len(quotes), len(tickers)


def main() -> None:
    args = sys.argv[1:]
    salvar = "--no-save" not in args
    tipo = _opt(args, "--tipo", "acao")
    tickers_cli = [a for a in args if not a.startswith("--")]

    print()

    # 1) Tickers específicos na linha de comando (ex.: fetch_stocks.py VALE3.SA --tipo=fii)
    if tickers_cli:
        logger.info("Buscando %d ativo(s) informado(s)...", len(tickers_cli))
        ok, total = _processar(tickers_cli, tipo, salvar)
        logger.info("Concluído: %d de %d ativo(s).", ok, total)
        return

    # 2) Fonte de verdade: ativos marcados como ativos no banco
    ativos = listar_tickers_ativos()
    if ativos:
        logger.info("Buscando %d ativo(s) do banco...", len(ativos))
        # o tipo é preservado no upsert (ON CONFLICT não altera tipo/ativo)
        ok, total = _processar(ativos, tipo, salvar)
        logger.info("Concluído: %d de %d ativo(s).", ok, total)
        return

    # 3) Banco vazio → semente inicial, inserindo cada grupo com o tipo correto
    logger.warning("Nenhum ticker ativo no banco; usando a semente de settings.py (por tipo).")
    total_ok = total_geral = 0
    for tipo_seed, lista in TICKERS_SEED.items():
        logger.info("Semente: %d %s(s)...", len(lista), tipo_seed)
        ok, total = _processar(lista, tipo_seed, salvar)
        total_ok += ok
        total_geral += total
    logger.info("Semente concluída: %d de %d ativo(s).", total_ok, total_geral)


if __name__ == "__main__":
    main()

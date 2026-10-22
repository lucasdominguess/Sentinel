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
from config.settings import TICKERS
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


def main() -> None:
    args = sys.argv[1:]
    salvar = "--no-save" not in args
    tipo = _opt(args, "--tipo", "acao")
    tickers_cli = [a for a in args if not a.startswith("--")]

    if tickers_cli:
        # modo "ticker específico" (ex.: fetch_stocks.py VALE3.SA --tipo=fii)
        tickers = tickers_cli
    else:
        # modo padrão: lê os ativos marcados como ativos no banco
        tickers = listar_tickers_ativos()
        if not tickers:
            logger.warning("Nenhum ticker ativo no banco; usando a lista de settings.py como semente.")
            tickers = TICKERS

    logger.info("Buscando %d ativo(s)...", len(tickers))
    quotes = fetch_all(tickers)

    print()
    for quote in quotes:
        print_quote(quote)

    if salvar:
        inseridas = salvar_cotacoes(quotes, tipo=tipo)
        logger.info("Gravadas %d cotação(ões) no banco.", inseridas)
    else:
        logger.info("Modo --no-save: nada foi gravado no banco.")

    logger.info("Concluído: %d de %d ativo(s) retornaram dados.", len(quotes), len(tickers))


if __name__ == "__main__":
    main()

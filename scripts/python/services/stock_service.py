"""Serviço de busca de dados de ativos via yfinance.

Responsável apenas por consultar o yfinance e devolver os campos mais
relevantes para análise. Não imprime nem grava nada — quem decide o que
fazer com o resultado é o script de entrada (fetch_stocks.py).
"""

import yfinance as yf

from config.logging_config import setup_logging

logger = setup_logging("stock_service")


def _none_if_zero(value):
    # yfinance devolve 0.0 em open/dayLow/dayHigh fora do pregão (PREPRE/CLOSED).
    # Tratamos como ausente (None) para não gravar um "0" enganoso no banco.
    return value if value else None


def _change_percent(info: dict, price, previous_close):
    """Variação percentual do dia.

    Fonte principal: chave `regularMarketChangePercent` (ver
    docs/REFERENCIA_YFINANCE_INFO.md). Se não vier, calcula a partir do
    fechamento anterior e do preço atual.
    """
    pct = info.get("regularMarketChangePercent")
    if pct is not None:
        return pct

    if price is not None and previous_close:  # evita divisão por zero/None
        return ((price - previous_close) / previous_close) * 100

    return None


def fetch_quote(ticker: str) -> dict:
    """Busca os dados de um ativo e retorna um dicionário com os campos-chave.

    Usa `info.get(...)` em tudo para não quebrar quando um campo não vier.
    As chaves seguem o guia docs/REFERENCIA_YFINANCE_INFO.md.
    """
    info = yf.Ticker(ticker).info

    price = info.get("currentPrice") or info.get("regularMarketPrice")
    previous_close = info.get("previousClose")

    return {
        "ticker": info.get("symbol", ticker),
        "name": info.get("longName") or info.get("shortName"),
        "sector": info.get("sector"),
        "industry": info.get("industry"),
        "currency": info.get("currency"),
        "market_state": info.get("marketState"),
        "price": price,
        "previous_close": previous_close,
        "open": _none_if_zero(info.get("open")),
        "day_low": _none_if_zero(info.get("dayLow")),
        "day_high": _none_if_zero(info.get("dayHigh")),
        "change_percent": _change_percent(info, price, previous_close),
        "week52_low": info.get("fiftyTwoWeekLow"),
        "week52_high": info.get("fiftyTwoWeekHigh"),
        "volume": info.get("volume") or info.get("regularMarketVolume"),
        "avg_volume": info.get("averageVolume"),
        "market_cap": info.get("marketCap"),
        "dividend_yield": info.get("dividendYield"),
        "ultimo_dividendo": info.get("lastDividendValue"),
        "ultimo_dividendo_data": info.get("lastDividendDate"),  # timestamp Unix (segundos)
        "trailing_pe": info.get("trailingPE"),
    }


def fetch_all(tickers: list[str]) -> list[dict]:
    """Busca vários ativos, pulando (e logando) os que derem erro."""
    results: list[dict] = []

    for ticker in tickers:
        try:
            results.append(fetch_quote(ticker))
        except Exception as e:
            logger.error("Erro ao buscar %s: %s", ticker, e)

    return results

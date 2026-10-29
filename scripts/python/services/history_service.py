"""Serviço de histórico diário (candles) via yfinance.

Separado do stock_service porque a origem do dado é outra: o `info` traz a
foto do momento, o `history()` traz a série diária consolidada da bolsa — já
ajustada por desdobramentos e proventos. É esta série que alimenta
`cotacoes_diarias`.

Chamamos sempre com auto_adjust=False para receber as DUAS colunas: `Close`
(preço de tela no dia) e `Adj Close` (ajustado). Guardar os dois evita a
escolha impossível entre "o preço que eu vi" e "o preço comparável".
"""

from math import isnan

from config.logging_config import setup_logging

logger = setup_logging("history_service")


def _num(value):
    """Converte para float tratando NaN/None do pandas como ausente."""
    if value is None:
        return None
    try:
        valor = float(value)
    except (TypeError, ValueError):
        return None
    return None if isnan(valor) else valor


def _vol(value):
    valor = _num(value)
    return int(valor) if valor is not None else None


def _coluna(row, *nomes):
    """Primeira coluna existente entre os nomes dados (tolera variação do yfinance)."""
    for nome in nomes:
        if nome in row:
            return row[nome]
    return None


def rows_from_history(hist) -> list[dict]:
    """Transforma o DataFrame do yfinance em linhas prontas para o banco.

    A linha do dia corrente pode ser parcial quando o pregão está aberto —
    isso é esperado: o upsert sobrescreve a cada coleta e a última execução
    do dia (após o fechamento) grava o valor definitivo.
    """
    if hist is None or hist.empty:
        return []

    linhas = []
    for indice, row in hist.iterrows():
        fechamento = _num(_coluna(row, "Close"))
        if fechamento is None:
            continue  # dia sem negociação útil

        linhas.append({
            "data": indice.date(),
            "abertura": _num(_coluna(row, "Open")),
            "maxima": _num(_coluna(row, "High")),
            "minima": _num(_coluna(row, "Low")),
            "fechamento": fechamento,
            "fechamento_ajustado": _num(_coluna(row, "Adj Close", "Adj_Close")) or fechamento,
            "volume": _vol(_coluna(row, "Volume")),
        })

    return linhas


def fetch_history(yf_ticker, period: str | None = None, start: str | None = None) -> list[dict]:
    """Busca o histórico diário de um ativo já instanciado (yf.Ticker)."""
    kwargs = {"auto_adjust": False}
    if period:
        kwargs["period"] = period
    if start:
        kwargs["start"] = start

    try:
        return rows_from_history(yf_ticker.history(**kwargs))
    except Exception as e:
        logger.error("Erro ao buscar histórico (%s): %s", kwargs, e)
        return []


def extremos(linhas: list[dict]) -> tuple:
    """Mínima/máxima e respectivas datas dentro das linhas recebidas.

    Devolve (minima, minima_data, maxima, maxima_data). Usa os valores NÃO
    ajustados de propósito: é o preço que apareceu na tela naquele dia.
    """
    com_minima = [l for l in linhas if l["minima"] is not None]
    com_maxima = [l for l in linhas if l["maxima"] is not None]

    if not com_minima or not com_maxima:
        return None, None, None, None

    menor = min(com_minima, key=lambda l: l["minima"])
    maior = max(com_maxima, key=lambda l: l["maxima"])

    return menor["minima"], menor["data"], maior["maxima"], maior["data"]

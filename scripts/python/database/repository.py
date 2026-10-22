"""Persistência das cotações no banco (camada de dados).

Recebe os dicionários produzidos por services.stock_service e grava:
  1. em `acoes`   -> upsert pelo ticker (cria ou atualiza o metadado);
  2. em `cotacoes` -> insere uma nova linha (série temporal).

Mantém todo o SQL isolado aqui. Cada ativo é gravado em sua própria
transação: se um falhar, os demais continuam.
"""

from datetime import datetime

from config.logging_config import setup_logging
from database.connection import get_connection

logger = setup_logging("repository")


def _to_int(value):
    """Converte para int (colunas de volume são bigint). None continua None."""
    return int(value) if value is not None else None


def _ts_to_date(ts):
    """Converte timestamp Unix (segundos) em date; None se vazio/inválido."""
    if not ts:
        return None
    try:
        return datetime.fromtimestamp(ts).date()
    except (ValueError, OSError, TypeError, OverflowError):
        return None


# acoes: cria pelo ticker ou atualiza o metadado; devolve o id.
# COALESCE evita sobrescrever um dado bom com NULL quando o yfinance não traz o campo.
# tipo e ativo só entram na criação — no conflito NÃO são tocados (preserva o que o
# usuário definiu pela tela, ex.: tipo=fii ou ativo=false).
_UPSERT_ACAO = """
    INSERT INTO acoes (ticker, nome, setor, segmento, moeda, tipo, ativo, created_at, updated_at)
    VALUES (%(ticker)s, %(nome)s, %(setor)s, %(segmento)s, %(moeda)s, %(tipo)s, true, now(), now())
    ON CONFLICT (ticker) DO UPDATE SET
        nome     = COALESCE(EXCLUDED.nome, acoes.nome),
        setor    = COALESCE(EXCLUDED.setor, acoes.setor),
        segmento = COALESCE(EXCLUDED.segmento, acoes.segmento),
        moeda    = EXCLUDED.moeda,
        updated_at = now()
    RETURNING id;
"""

_INSERT_COTACAO = """
    INSERT INTO cotacoes (
        acao_id, preco, fechamento_anterior, abertura,
        minima_dia, maxima_dia, variacao_dia_pct,
        minima_52s, maxima_52s,
        volume, volume_medio, valor_mercado,
        dividend_yield, ultimo_dividendo, ultimo_dividendo_data, preco_lucro, estado_mercado,
        capturado_em, created_at, updated_at
    ) VALUES (
        %(acao_id)s, %(preco)s, %(fechamento_anterior)s, %(abertura)s,
        %(minima_dia)s, %(maxima_dia)s, %(variacao_dia_pct)s,
        %(minima_52s)s, %(maxima_52s)s,
        %(volume)s, %(volume_medio)s, %(valor_mercado)s,
        %(dividend_yield)s, %(ultimo_dividendo)s, %(ultimo_dividendo_data)s, %(preco_lucro)s, %(estado_mercado)s,
        now(), now(), now()
    );
"""


def listar_tickers_ativos() -> list[str]:
    """Lê os tickers marcados como ativos (ativo = true) na tabela acoes."""
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT ticker FROM acoes WHERE ativo = true ORDER BY ticker")
            return [row[0] for row in cur.fetchall()]
    finally:
        conn.close()


def salvar_cotacoes(quotes: list[dict], tipo: str = "acao") -> int:
    """Grava uma lista de cotações. Retorna quantas foram inseridas.

    `tipo` só é usado ao CRIAR uma ação nova (ticker inédito); tickers já
    existentes preservam o tipo definido anteriormente.
    """
    if not quotes:
        return 0

    conn = get_connection()
    conn.autocommit = False
    inseridas = 0

    try:
        with conn.cursor() as cur:
            for quote in quotes:
                try:
                    cur.execute(_UPSERT_ACAO, {
                        "ticker": quote["ticker"],
                        "nome": quote.get("name"),
                        "setor": quote.get("sector"),
                        "segmento": quote.get("industry"),
                        "moeda": quote.get("currency") or "BRL",
                        "tipo": tipo,
                    })
                    acao_id = cur.fetchone()[0]

                    cur.execute(_INSERT_COTACAO, {
                        "acao_id": acao_id,
                        "preco": quote.get("price"),
                        "fechamento_anterior": quote.get("previous_close"),
                        "abertura": quote.get("open"),
                        "minima_dia": quote.get("day_low"),
                        "maxima_dia": quote.get("day_high"),
                        "variacao_dia_pct": quote.get("change_percent"),
                        "minima_52s": quote.get("week52_low"),
                        "maxima_52s": quote.get("week52_high"),
                        "volume": _to_int(quote.get("volume")),
                        "volume_medio": _to_int(quote.get("avg_volume")),
                        "valor_mercado": quote.get("market_cap"),
                        "dividend_yield": quote.get("dividend_yield"),
                        "ultimo_dividendo": quote.get("ultimo_dividendo"),
                        "ultimo_dividendo_data": _ts_to_date(quote.get("ultimo_dividendo_data")),
                        "preco_lucro": quote.get("trailing_pe"),
                        "estado_mercado": quote.get("market_state"),
                    })

                    conn.commit()
                    inseridas += 1
                except Exception as e:
                    conn.rollback()
                    logger.error("Erro ao salvar %s: %s", quote.get("ticker"), e)
    finally:
        conn.close()

    return inseridas

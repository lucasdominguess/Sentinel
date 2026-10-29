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
        minima_dia, maxima_dia, variacao_dia_pct, variacao_dia,
        minima_52s, maxima_52s, minima_mes, minima_mes_data, maxima_mes, maxima_mes_data,
        volume, volume_medio, valor_mercado,
        dividend_yield, ultimo_dividendo, ultimo_dividendo_data, preco_lucro, estado_mercado,
        capturado_em, created_at, updated_at
    ) VALUES (
        %(acao_id)s, %(preco)s, %(fechamento_anterior)s, %(abertura)s,
        %(minima_dia)s, %(maxima_dia)s, %(variacao_dia_pct)s, %(variacao_dia)s,
        %(minima_52s)s, %(maxima_52s)s, %(minima_mes)s, %(minima_mes_data)s, %(maxima_mes)s, %(maxima_mes_data)s,
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
                        "variacao_dia": quote.get("change_abs"),
                        "minima_52s": quote.get("week52_low"),
                        "maxima_52s": quote.get("week52_high"),
                        "minima_mes": quote.get("minima_mes"),
                        "minima_mes_data": quote.get("minima_mes_data"),
                        "maxima_mes": quote.get("maxima_mes"),
                        "maxima_mes_data": quote.get("maxima_mes_data"),
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


# ----------------------------------------------------------------------
# Fechamento diário (cotacoes_diarias)
# ----------------------------------------------------------------------
# Upsert pela chave (acao_id, data): rodar duas vezes no mesmo dia corrige a
# linha em vez de duplicar. É o que permite reprocessar o backfill à vontade
# e o que faz a coleta do dia sobrescrever o candle parcial pelo definitivo.
_UPSERT_COTACAO_DIARIA = """
    INSERT INTO cotacoes_diarias (
        acao_id, data, abertura, maxima, minima,
        fechamento, fechamento_ajustado, volume, created_at, updated_at
    ) VALUES (
        %(acao_id)s, %(data)s, %(abertura)s, %(maxima)s, %(minima)s,
        %(fechamento)s, %(fechamento_ajustado)s, %(volume)s, now(), now()
    )
    ON CONFLICT (acao_id, data) DO UPDATE SET
        abertura            = EXCLUDED.abertura,
        maxima              = EXCLUDED.maxima,
        minima              = EXCLUDED.minima,
        fechamento          = EXCLUDED.fechamento,
        fechamento_ajustado = EXCLUDED.fechamento_ajustado,
        volume              = EXCLUDED.volume,
        updated_at          = now();
"""


def listar_acoes_ativas() -> list[dict]:
    """Ativos marcados como ativos, com id e ticker (usado pelo backfill)."""
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT id, ticker FROM acoes WHERE ativo = true ORDER BY ticker")
            return [{"id": row[0], "ticker": row[1]} for row in cur.fetchall()]
    finally:
        conn.close()


def salvar_historico_diario(itens: list[dict]) -> int:
    """Grava os candles diários. Retorna o total de linhas gravadas.

    Cada item é {"ticker": str, "linhas": [...]} — formato produzido tanto
    pela coleta (histórico do mês, de brinde na mesma requisição do mín/máx)
    quanto pelo backfill (histórico longo). Um ativo por transação: se um
    falhar, os demais continuam.
    """
    if not itens:
        return 0

    conn = get_connection()
    conn.autocommit = False
    gravadas = 0

    try:
        with conn.cursor() as cur:
            for item in itens:
                linhas = item.get("linhas") or []
                if not linhas:
                    continue
                try:
                    cur.execute("SELECT id FROM acoes WHERE ticker = %(ticker)s", {"ticker": item["ticker"]})
                    encontrada = cur.fetchone()
                    if not encontrada:
                        logger.warning("Ticker %s não existe em acoes; histórico ignorado.", item["ticker"])
                        conn.rollback()
                        continue

                    acao_id = encontrada[0]
                    for linha in linhas:
                        cur.execute(_UPSERT_COTACAO_DIARIA, {"acao_id": acao_id, **linha})

                    conn.commit()
                    gravadas += len(linhas)
                except Exception as e:
                    conn.rollback()
                    logger.error("Erro ao salvar histórico de %s: %s", item.get("ticker"), e)
    finally:
        conn.close()

    return gravadas


# ----------------------------------------------------------------------
# Log de execução (coletas)
# ----------------------------------------------------------------------
def registrar_inicio_coleta(origem: str = "coleta") -> int | None:
    """Abre a linha de log e devolve o id. Erro aqui não aborta a coleta."""
    try:
        conn = get_connection()
    except Exception as e:
        logger.error("Não foi possível registrar o início da coleta: %s", e)
        return None

    try:
        conn.autocommit = True
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO coletas (origem, inicio, created_at, updated_at)"
                " VALUES (%(origem)s, now(), now(), now()) RETURNING id",
                {"origem": origem},
            )
            return cur.fetchone()[0]
    except Exception as e:
        logger.error("Não foi possível registrar o início da coleta: %s", e)
        return None
    finally:
        conn.close()


def finalizar_coleta(coleta_id: int | None, ativos_ok: int, ativos_falha: int,
                     registros: int, erro: str | None = None) -> None:
    """Fecha a linha de log. `fim` nulo significa execução que não terminou."""
    if coleta_id is None:
        return

    try:
        conn = get_connection()
    except Exception as e:
        logger.error("Não foi possível finalizar a coleta %s: %s", coleta_id, e)
        return

    try:
        conn.autocommit = True
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE coletas SET fim = now(), ativos_ok = %(ok)s, ativos_falha = %(falha)s,"
                " registros = %(registros)s, erro = %(erro)s, updated_at = now()"
                " WHERE id = %(id)s",
                {
                    "id": coleta_id,
                    "ok": ativos_ok,
                    "falha": ativos_falha,
                    "registros": registros,
                    "erro": erro[:2000] if erro else None,
                },
            )
    except Exception as e:
        logger.error("Não foi possível finalizar a coleta %s: %s", coleta_id, e)
    finally:
        conn.close()

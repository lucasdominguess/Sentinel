"""Importa `acoes` e `movimentacoes` a partir dos CSV — rodar no SERVIDOR (produção).

Lê scripts/python/sync/{acoes.csv, movimentacoes.csv} e grava no banco LOCAL:
  - acoes:         UPSERT por ticker (cria novos; atualiza tipo/ativo; usa COALESCE
                   nos metadados para não apagar o que o coletor já preencheu).
  - movimentacoes: resolve o acao_id pelo TICKER no banco local e INSERE com id novo
                   (os ids de dev NÃO são usados, para não colidir com o que já
                   existe aqui). Por segurança, PULA movimentações idênticas já
                   existentes (mesmo acao_id + tipo + preco + quantidade + data);
                   use --permitir-duplicados para inserir mesmo assim.

Compatível com o export antigo: sem movimentacoes.csv, lê aportes.csv
(preco_pago/total_investido) e importa cada linha como COMPRA.

Requer a migration de `movimentacoes` já aplicada no servidor (php artisan migrate).
Importa acoes ANTES das movimentações (elas precisam do ticker já cadastrado).
Depois de importar, limpe/apague os CSV para não reimportar por engano.

Uso (no servidor):
    .venv/bin/python import_sync.py
    .venv/bin/python import_sync.py --dir=/caminho/sync
    .venv/bin/python import_sync.py --permitir-duplicados
"""

import csv
import sys
from datetime import date, datetime
from decimal import ROUND_HALF_UP, Decimal, InvalidOperation
from pathlib import Path

from config.logging_config import setup_logging
from database.connection import get_connection

for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

logger = setup_logging("import_sync")

DIR_PADRAO = Path(__file__).resolve().parent / "sync"

# tipo/ativo vêm do dev (fonte de verdade do cadastro); metadados via COALESCE
# para não sobrescrever com NULL o que o coletor já preencheu no servidor.
UPSERT_ACAO = """
    INSERT INTO acoes (ticker, nome, setor, segmento, moeda, ativo, tipo, created_at, updated_at)
    VALUES (%(ticker)s, %(nome)s, %(setor)s, %(segmento)s, %(moeda)s, %(ativo)s, %(tipo)s, now(), now())
    ON CONFLICT (ticker) DO UPDATE SET
        nome       = COALESCE(EXCLUDED.nome, acoes.nome),
        setor      = COALESCE(EXCLUDED.setor, acoes.setor),
        segmento   = COALESCE(EXCLUDED.segmento, acoes.segmento),
        moeda      = EXCLUDED.moeda,
        ativo      = EXCLUDED.ativo,
        tipo       = EXCLUDED.tipo,
        updated_at = now();
"""

# created_at/updated_at preservados do dev quando vierem; senão now().
INSERT_MOVIMENTACAO = """
    INSERT INTO movimentacoes (acao_id, tipo, preco, quantidade, total, data, created_at, updated_at)
    VALUES (%(acao_id)s, %(tipo)s, %(preco)s, %(quantidade)s, %(total)s, %(data)s,
            COALESCE(%(created_at)s, now()), COALESCE(%(updated_at)s, now()));
"""

DUP_MOVIMENTACAO = """
    SELECT 1 FROM movimentacoes
    WHERE acao_id = %(acao_id)s AND tipo = %(tipo)s AND preco = %(preco)s
      AND quantidade = %(quantidade)s AND data = %(data)s
    LIMIT 1;
"""

TIPOS = ("compra", "venda")


def _opt(args: list[str], name: str, default=None):
    prefix = name + "="
    for arg in args:
        if arg.startswith(prefix):
            return arg[len(prefix):]
    return default


def _none(value):
    """String vazia/espaços vira None; caso contrário devolve o texto limpo."""
    value = (value or "").strip()
    return value or None


def _bool(value) -> bool:
    return str(value).strip().lower() in ("1", "true", "t", "yes", "y", "sim")


def _dec(value):
    value = _none(value)
    if value is None:
        return None
    try:
        return Decimal(value)
    except InvalidOperation:
        return None


def _total(preco, quantidade):
    if preco is None:
        return None
    return (preco * quantidade).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def _date(value):
    value = _none(value)
    return date.fromisoformat(value) if value else None


def _dt(value):
    """Parseia timestamp do CSV ('YYYY-MM-DD HH:MM:SS' ou ISO). Falha -> None (usa now())."""
    value = _none(value)
    if value is None:
        return None
    try:
        return datetime.fromisoformat(value.replace(" ", "T"))
    except ValueError:
        return None


def _ler_csv(caminho: Path):
    """Lê o CSV como lista de dicts; None se o arquivo não existir."""
    if not caminho.is_file():
        return None
    with open(caminho, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def importar_acoes(conn, linhas: list[dict]) -> int:
    inseridas = 0
    with conn.cursor() as cur:
        for row in linhas:
            ticker = _none(row.get("ticker"))
            if not ticker:
                continue
            try:
                cur.execute(UPSERT_ACAO, {
                    "ticker": ticker,
                    "nome": _none(row.get("nome")),
                    "setor": _none(row.get("setor")),
                    "segmento": _none(row.get("segmento")),
                    "moeda": _none(row.get("moeda")) or "BRL",
                    "ativo": _bool(row.get("ativo")),
                    "tipo": _none(row.get("tipo")) or "acao",
                })
                conn.commit()
                inseridas += 1
            except Exception as e:
                conn.rollback()
                logger.error("acoes: erro em %s: %s", ticker, e)
    return inseridas


def importar_movimentacoes(conn, linhas: list[dict], permitir_dup: bool) -> tuple[int, int, int]:
    inseridos = pulados = sem_ticker = 0
    with conn.cursor() as cur:
        for row in linhas:
            ticker = _none(row.get("ticker"))
            try:
                cur.execute("SELECT id FROM acoes WHERE ticker = %s", (ticker,))
                encontrada = cur.fetchone()
                if not encontrada:
                    sem_ticker += 1
                    logger.warning("movimentacoes: ticker %s não existe em acoes; pulado.", ticker)
                    conn.rollback()
                    continue

                # aportes.csv (export antigo) não tem tipo: é sempre compra
                tipo = (_none(row.get("tipo")) or "compra").lower()
                if tipo not in TIPOS:
                    logger.error("movimentacoes: tipo inválido %r em %s; pulado.", tipo, ticker)
                    conn.rollback()
                    continue

                preco = _dec(row.get("preco") or row.get("preco_pago"))
                quantidade = int(_none(row.get("quantidade")) or 0)
                params = {
                    "acao_id": encontrada[0],
                    "tipo": tipo,
                    "preco": preco,
                    "quantidade": quantidade,
                    # O total é derivado (mesma regra do MovimentacaoService): o do CSV
                    # pode vir divergente de exports antigos e distorceria o preço médio
                    "total": _total(preco, quantidade),
                    "data": _date(row.get("data")),
                    "created_at": _dt(row.get("created_at")),
                    "updated_at": _dt(row.get("updated_at")),
                }

                if not permitir_dup:
                    cur.execute(DUP_MOVIMENTACAO, params)
                    if cur.fetchone():
                        pulados += 1
                        conn.rollback()
                        continue

                cur.execute(INSERT_MOVIMENTACAO, params)
                conn.commit()
                inseridos += 1
            except Exception as e:
                conn.rollback()
                logger.error("movimentacoes: erro em %s (%s): %s", ticker, row.get("data"), e)
    return inseridos, pulados, sem_ticker


def main() -> None:
    args = sys.argv[1:]
    origem = Path(_opt(args, "--dir", str(DIR_PADRAO)))
    permitir_dup = "--permitir-duplicados" in args

    acoes = _ler_csv(origem / "acoes.csv")
    movs = _ler_csv(origem / "movimentacoes.csv")
    if movs is None:
        movs = _ler_csv(origem / "aportes.csv")  # export antigo: só compras

    if acoes is None and movs is None:
        logger.error("Nenhum CSV encontrado em %s (acoes.csv / movimentacoes.csv).", origem)
        print(f"Nada a importar: não achei acoes.csv/movimentacoes.csv em {origem}")
        return

    conn = get_connection()
    conn.autocommit = False
    n_acoes = ins = pul = semt = 0
    try:
        if acoes:
            n_acoes = importar_acoes(conn, acoes)
        if movs:
            ins, pul, semt = importar_movimentacoes(conn, movs, permitir_dup)
    finally:
        conn.close()

    logger.info("acoes: %d upsert(s).", n_acoes)
    logger.info("movimentacoes: %d inserida(s), %d duplicada(s) pulada(s), %d sem ticker.", ins, pul, semt)
    print(f"\nOK: acoes {n_acoes} upsert | movimentacoes {ins} inserida(s), "
          f"{pul} duplicada(s) pulada(s), {semt} sem ticker")
    if pul and not permitir_dup:
        print("Dica: movimentações idênticas já existentes foram puladas. "
              "Use --permitir-duplicados se elas forem operações reais distintas.")


if __name__ == "__main__":
    main()

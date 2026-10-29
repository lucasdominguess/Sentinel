"""Importa `acoes` e `aportes` a partir dos CSV — rodar no SERVIDOR (produção).

Lê scripts/python/sync/{acoes.csv, aportes.csv} e grava no banco LOCAL:
  - acoes:   UPSERT por ticker (cria novos; atualiza tipo/ativo; usa COALESCE
             nos metadados para não apagar o que o coletor já preencheu).
  - aportes: resolve o acao_id pelo TICKER no banco local e INSERE com id novo
             (os ids de dev NÃO são usados, para não colidir com o que já existe
             aqui). Por segurança, PULA aportes idênticos já existentes (mesmo
             acao_id + preco_pago + quantidade + data); use --permitir-duplicados
             para inserir mesmo assim.

Importa acoes ANTES de aportes (o aporte precisa do ticker já cadastrado).
Depois de importar, limpe/apague os CSV para não reimportar por engano.

Uso (no servidor):
    .venv/bin/python import_sync.py
    .venv/bin/python import_sync.py --dir=/caminho/sync
    .venv/bin/python import_sync.py --permitir-duplicados
"""

import csv
import sys
from datetime import date, datetime
from decimal import Decimal, InvalidOperation
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
INSERT_APORTE = """
    INSERT INTO aportes (acao_id, preco_pago, quantidade, total_investido, data, created_at, updated_at)
    VALUES (%(acao_id)s, %(preco_pago)s, %(quantidade)s, %(total_investido)s, %(data)s,
            COALESCE(%(created_at)s, now()), COALESCE(%(updated_at)s, now()));
"""

DUP_APORTE = """
    SELECT 1 FROM aportes
    WHERE acao_id = %(acao_id)s AND preco_pago = %(preco_pago)s
      AND quantidade = %(quantidade)s AND data = %(data)s
    LIMIT 1;
"""


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


def importar_aportes(conn, linhas: list[dict], permitir_dup: bool) -> tuple[int, int, int]:
    inseridos = pulados = sem_ticker = 0
    with conn.cursor() as cur:
        for row in linhas:
            ticker = _none(row.get("ticker"))
            try:
                cur.execute("SELECT id FROM acoes WHERE ticker = %s", (ticker,))
                encontrada = cur.fetchone()
                if not encontrada:
                    sem_ticker += 1
                    logger.warning("aportes: ticker %s não existe em acoes; pulado.", ticker)
                    conn.rollback()
                    continue

                params = {
                    "acao_id": encontrada[0],
                    "preco_pago": _dec(row.get("preco_pago")),
                    "quantidade": int(_none(row.get("quantidade")) or 0),
                    "total_investido": _dec(row.get("total_investido")),
                    "data": _date(row.get("data")),
                    "created_at": _dt(row.get("created_at")),
                    "updated_at": _dt(row.get("updated_at")),
                }

                if not permitir_dup:
                    cur.execute(DUP_APORTE, params)
                    if cur.fetchone():
                        pulados += 1
                        conn.rollback()
                        continue

                cur.execute(INSERT_APORTE, params)
                conn.commit()
                inseridos += 1
            except Exception as e:
                conn.rollback()
                logger.error("aportes: erro em %s (%s): %s", ticker, row.get("data"), e)
    return inseridos, pulados, sem_ticker


def main() -> None:
    args = sys.argv[1:]
    origem = Path(_opt(args, "--dir", str(DIR_PADRAO)))
    permitir_dup = "--permitir-duplicados" in args

    acoes = _ler_csv(origem / "acoes.csv")
    aportes = _ler_csv(origem / "aportes.csv")

    if acoes is None and aportes is None:
        logger.error("Nenhum CSV encontrado em %s (acoes.csv / aportes.csv).", origem)
        print(f"Nada a importar: não achei acoes.csv/aportes.csv em {origem}")
        return

    conn = get_connection()
    conn.autocommit = False
    n_acoes = ins = pul = semt = 0
    try:
        if acoes:
            n_acoes = importar_acoes(conn, acoes)
        if aportes:
            ins, pul, semt = importar_aportes(conn, aportes, permitir_dup)
    finally:
        conn.close()

    logger.info("acoes: %d upsert(s).", n_acoes)
    logger.info("aportes: %d inserido(s), %d duplicado(s) pulado(s), %d sem ticker.", ins, pul, semt)
    print(f"\nOK: acoes {n_acoes} upsert | aportes {ins} inserido(s), "
          f"{pul} duplicado(s) pulado(s), {semt} sem ticker")
    if pul and not permitir_dup:
        print("Dica: aportes idênticos já existentes foram pulados. "
              "Use --permitir-duplicados se eles forem compras reais distintas.")


if __name__ == "__main__":
    main()

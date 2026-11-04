"""Exporta `acoes` e `movimentacoes` para CSV — rodar no ambiente de DESENVOLVIMENTO.

Gera dois arquivos em scripts/python/sync/:
  - acoes.csv          todos os ativos cadastrados (chave natural: ticker)
  - movimentacoes.csv  todas as compras/vendas, referenciando o ativo pelo TICKER
                       (nunca pelo id, que difere entre ambientes)

Fluxo: rode aqui (dev), copie os CSV para o servidor e rode `import_sync.py` lá.
As credenciais de banco vêm do .env do Laravel (via config.settings / connection).

Uso:
    .venv/bin/python export_sync.py                  # grava em scripts/python/sync/
    .venv/bin/python export_sync.py --dir=/tmp/sync  # outra pasta de saída
"""

import csv
import sys
from pathlib import Path

from config.logging_config import setup_logging
from database.connection import get_connection

# Console do Windows em cp1252 quebra com acento/emoji; força UTF-8 (inofensivo no Linux).
for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

logger = setup_logging("export_sync")

DIR_PADRAO = Path(__file__).resolve().parent / "sync"

# acoes: sem id/timestamps — o servidor gera os seus. O ticker é a chave natural.
SQL_ACOES = """
    SELECT ticker, nome, setor, segmento, moeda, ativo, tipo
    FROM acoes
    ORDER BY ticker
"""
ACOES_COLS = ["ticker", "nome", "setor", "segmento", "moeda", "ativo", "tipo"]

# movimentacoes: referenciam o ativo pelo TICKER (join com acoes), não pelo acao_id.
SQL_MOVIMENTACOES = """
    SELECT a.ticker, m.tipo, m.preco, m.quantidade, m.total,
           m.data, m.created_at, m.updated_at
    FROM movimentacoes m
    JOIN acoes a ON a.id = m.acao_id
    ORDER BY m.data, m.id
"""
MOVIMENTACOES_COLS = ["ticker", "tipo", "preco", "quantidade", "total",
                      "data", "created_at", "updated_at"]


def _opt(args: list[str], name: str, default=None):
    """Extrai o valor de uma opção no formato --nome=valor."""
    prefix = name + "="
    for arg in args:
        if arg.startswith(prefix):
            return arg[len(prefix):]
    return default


def _exportar(cur, sql: str, colunas: list[str], caminho: Path) -> int:
    """Roda a query e grava o resultado em CSV (cabeçalho + linhas). Retorna a contagem."""
    cur.execute(sql)
    linhas = cur.fetchall()
    with open(caminho, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(colunas)
        writer.writerows(linhas)
    return len(linhas)


def main() -> None:
    destino = Path(_opt(sys.argv[1:], "--dir", str(DIR_PADRAO)))
    destino.mkdir(parents=True, exist_ok=True)

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            n_acoes = _exportar(cur, SQL_ACOES, ACOES_COLS, destino / "acoes.csv")
            n_movs = _exportar(cur, SQL_MOVIMENTACOES, MOVIMENTACOES_COLS, destino / "movimentacoes.csv")
    finally:
        conn.close()

    logger.info("Exportados %d ativo(s) -> %s", n_acoes, destino / "acoes.csv")
    logger.info("Exportadas %d movimentação(ões) -> %s", n_movs, destino / "movimentacoes.csv")
    print(f"\nOK: {n_acoes} ação(ões) e {n_movs} movimentação(ões) exportadas em {destino}")


if __name__ == "__main__":
    main()

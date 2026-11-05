"""Remove o ruído de float dos candles já gravados em `cotacoes_diarias`.

O history() do yfinance devolve float com ruído (165.449997 em vez de 165.45).
O coletor já grava arredondado; este script corrige o que entrou antes disso.
Preço ajustado por desdobramento/bonificação (casas de verdade) fica como está.

É idempotente: rodar de novo não altera nada. Rodar uma vez, depois do deploy.

Execução (a partir de scripts/python/):
    .venv/bin/python normalizar_precos.py
"""

import sys

from config.logging_config import setup_logging
from database.repository import normalizar_precos_diarios

for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except (AttributeError, ValueError):
        pass

logger = setup_logging("normalizar_precos")


def main() -> None:
    alteradas = normalizar_precos_diarios()
    logger.info("cotacoes_diarias: %d linha(s) normalizada(s).", alteradas)


if __name__ == "__main__":
    main()

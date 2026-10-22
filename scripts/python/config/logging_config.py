"""Configuração de logging do coletor.

Isola a criação do logger para que os demais módulos apenas chamem
`setup_logging(nome)` sem se preocupar com handlers/formatadores.

Os logs ficam em scripts/python/logs/, com **um arquivo por dia**
(ex.: coletor-2026-10-21.log), no mesmo estilo do canal "daily" do Laravel.
"""

import logging
from datetime import date
from pathlib import Path

PYTHON_DIR = Path(__file__).resolve().parents[1]  # scripts/python
LOG_DIR = PYTHON_DIR / "logs"
LOG_PREFIX = "coletor"


def _log_file() -> Path:
    """Garante a pasta de logs e devolve o caminho do arquivo do dia."""
    LOG_DIR.mkdir(parents=True, exist_ok=True)
    return LOG_DIR / f"{LOG_PREFIX}-{date.today():%Y-%m-%d}.log"


def setup_logging(name: str = __name__) -> logging.Logger:
    logger = logging.getLogger(name)
    logger.setLevel(logging.INFO)

    # Evita múltiplos handlers quando usado em vários módulos
    if not logger.handlers:
        formatter = logging.Formatter(
            "%(asctime)s - %(levelname)s - %(name)s - %(message)s"
        )

        file_handler = logging.FileHandler(_log_file(), encoding="utf-8")
        file_handler.setLevel(logging.INFO)
        file_handler.setFormatter(formatter)
        logger.addHandler(file_handler)

        stream_handler = logging.StreamHandler()
        stream_handler.setLevel(logging.INFO)
        stream_handler.setFormatter(formatter)
        logger.addHandler(stream_handler)

    return logger

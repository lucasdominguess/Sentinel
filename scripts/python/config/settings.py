"""Configurações centrais do coletor Python.

Centraliza o carregamento das variáveis de ambiente e as configurações
usadas pelos demais módulos (banco, Telegram, lista de ativos). Manter
tudo aqui evita `os.getenv` espalhado pelo código e deixa a separação de
responsabilidades clara: nenhum outro módulo lê o .env diretamente.

Estratégia de .env (duas fontes):
  1. .env do Laravel (raiz do projeto) -> fonte única das credenciais do banco.
  2. .env local em scripts/python/     -> configs específicas do coletor
     (ex.: token do Telegram). Sobrescreve a primeira quando houver conflito.
"""

import os
from pathlib import Path

from dotenv import load_dotenv

# ----------------------------------------------------------------------
# Diretórios de referência
# ----------------------------------------------------------------------
PYTHON_DIR = Path(__file__).resolve().parents[1]      # scripts/python
PROJECT_ROOT = Path(__file__).resolve().parents[3]    # raiz do projeto Laravel (Sentinel)

# ----------------------------------------------------------------------
# Carregamento do ambiente
# ----------------------------------------------------------------------
# 1) credenciais de banco compartilhadas com o Laravel
load_dotenv(PROJECT_ROOT / ".env")
# 2) configs específicas do Python (override=True para ter prioridade)
load_dotenv(PYTHON_DIR / ".env", override=True)

# ----------------------------------------------------------------------
# Banco de dados (mesmo banco do Laravel)
# ----------------------------------------------------------------------
DB_CONNECTION = os.getenv("DB_CONNECTION", "pgsql")
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "5432"))
DB_DATABASE = os.getenv("DB_DATABASE", "")
DB_USERNAME = os.getenv("DB_USERNAME", "")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")

# ----------------------------------------------------------------------
# Telegram (opcional — usado quando o coletor enviar notificações)
# ----------------------------------------------------------------------
TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
TELEGRAM_CHAT_ID = os.getenv("TELEGRAM_CHAT_ID")

# ----------------------------------------------------------------------
# Ativos monitorados
# ----------------------------------------------------------------------
TICKERS = [
    "ALPA3.SA",
    "ANIM3.SA",
    "AXIA3.SA",
    "B3SA3.SA",
    "BBSE3.SA",
    "BMGB4.SA",
    "CMIG4.SA",
    "COGN3.SA",
    "CPLE3.SA",
    "CSNA3.SA",
    "CXSE3.SA",
    "EGIE3.SA",
    "ENGI4.SA",
    "FESA4.SA",
    "GRND3.SA",
    "ITSA4.SA",
    "JSLG3.SA",
    "LAVV3.SA",
    "MDNE3.SA",
    "MELK3.SA",
    "MGLU3.SA",
    "MRVE3.SA",
    "MTRE3.SA",
    "PGMN3.SA",
    "POMO3.SA",
    "POMO4.SA",
    "RAIL3.SA",
    "RECV3.SA",
    "RDOR3.SA",
    "SAPR4.SA",
    "VAMO3.SA",
    "BHIA3.SA"
]

"""Serviço de envio de mensagens via Telegram.

Isola a comunicação com o Telegram. As credenciais vêm de
`config.settings` (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID), lidas do .env
local do coletor. **Não é usado no v1** — fica pronto para quando o
coletor passar a notificar.
"""

from config import settings
from config.logging_config import setup_logging

logger = setup_logging("telegram_bot")


class TelegramBot:
    """Bot do Telegram configurável.

    Por padrão usa o token e o chat_id definidos em config.settings.
    Também aceita valores explícitos, útil se houver mais de um bot/grupo.
    """

    def __init__(self, token: str | None = None, chat_id: str | None = None):
        token = token or settings.TELEGRAM_BOT_TOKEN
        self.chat_id = chat_id or settings.TELEGRAM_CHAT_ID

        if not token:
            raise ValueError("TELEGRAM_BOT_TOKEN não definido no .env")
        if not self.chat_id:
            raise ValueError("TELEGRAM_CHAT_ID não definido no .env")

        # import adiado: v1 (print) roda sem a lib do Telegram instalada
        import telebot

        self.bot = telebot.TeleBot(token)

    def send_message(self, msg: str) -> None:
        try:
            self.bot.send_message(self.chat_id, msg)
        except Exception as e:
            logger.error("Erro ao enviar mensagem via Telegram (chat %s): %s", self.chat_id, e)

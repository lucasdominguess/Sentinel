"""Conexão isolada com o banco de dados (PostgreSQL).

Mantida separada para que a lógica de negócio não conheça detalhes de
conexão. **Ainda não é usada no v1** (que apenas busca e imprime), mas já
deixa pronto o caminho para quando o coletor passar a gravar no banco
compartilhado com o Laravel.

As credenciais vêm de `config.settings`, que por sua vez lê o .env do
Laravel na raiz do projeto.
"""

from config import settings


def get_connection():
    """Abre uma conexão psycopg2 com o Postgres do projeto.

    O import do psycopg2 fica dentro da função de propósito: assim o v1
    (somente print) roda sem precisar do driver instalado.
    """
    import psycopg2

    return psycopg2.connect(
        host=settings.DB_HOST,
        port=settings.DB_PORT,
        dbname=settings.DB_DATABASE,
        user=settings.DB_USERNAME,
        password=settings.DB_PASSWORD,
    )

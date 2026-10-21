<p align="center">
  <img src="dtic1.png" width="220" alt="DTIC Logo">
</p>

# Template Padrão de API Laravel - DTIC

> Repositório base (Skeleton) para criação de novas APIs e microsserviços em PHP/Laravel pela equipe da DTIC.

```powershell
composer create-project dtic/php-skeleton <nome-do-projeto> 1.0.0 --repository="{\"type\":\"vcs\",\"url\":\"https://git-dtic.smsprefeiturasp.com.br/php/php-skeleton.git\"}"
```

---

## Arquitetura

O projeto segue uma separação de responsabilidades em camadas:

| Camada | Localização | Responsabilidade |
|---|---|---|
| Controllers | `app/Http/Controllers` | Receber requisições, delegar ao Service e retornar resposta |
| Services | `app/Services` | Regras de negócio |
| Repositories | `app/Repositories` | Acesso a dados |
| DTOs | `app/DTOs` | Tráfego tipado de dados entre camadas |
| Contracts | `app/Contracts` | Interfaces para Services e Repositories |

---

## O que já vem configurado

- **Autenticação JWT** — Tymon JWT Auth com refresh automático via middleware
- **Documentação Swagger** — L5-Swagger integrado; acesse `/api/documentation` após subir o projeto
- **Tratamento de Exceções** — Handler centralizado em `app/Exceptions/Handlers`
- **Validações customizadas** — Regras prontas para CPF, CNPJ e senha forte em `app/Rules`
- **CI/CD** — Pipeline GitLab CI configurado em `.gitlab-ci.yml`

---

## Comandos Artisan

Comandos customizados para acelerar o scaffolding:

| Comando | Descrição |
|---|---|
| `make:service {Nome}` | Cria Service + Interface + bind no Provider |
| `make:dto {Nome}` | Cria um DTO tipado |
| `make:crud {Nome}` | Gera Controller, Service, Repository, DTO e rotas |
| `make:abstract {Nome}` | Cria classe abstrata base |
| `make:bind {Nome}` | Registra bind de interface no Provider |
| `sync:contract` | Sincroniza contratos com suas implementações |
| `postman:generate` | Gera coleção Postman a partir das rotas |
| `clean:all` | Remove caches de config, rota e view |

---

## Configuração inicial

```bash
# 1. Copie o .env
cp .env.example .env

# 2. Instale as dependências
composer install

# 3. Gere a chave da aplicação e o secret JWT
php artisan key:generate
php artisan jwt:secret

# 4. Execute as migrations
php artisan migrate
```

---

## Estrutura de pastas relevante

```
app/
├── Console/Commands/   # Comandos Artisan customizados
├── Contracts/          # Interfaces de Services e Repositories
├── DTOs/               # Objetos de transferência de dados
├── Exceptions/         # Handlers de exceção centralizados
├── Http/
│   ├── Controllers/    # Controllers slim (sem lógica de negócio)
│   └── Middleware/     # Middlewares de autenticação e controle
├── Repositories/       # Camada de acesso a dados
├── Rules/              # Validações customizadas (CPF, CNPJ, Senha)
└── Services/           # Regras de negócio
```

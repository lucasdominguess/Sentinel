## Obsidian Brain (memória técnica externa)

Este projeto consulta o Brain global para padrões arquiteturais reutilizáveis.

- **Caminho local do Brain:** `C:\Users\lukas\git_projetos\Outros\Obsidian-ld\Obsidian-LD`
- **MCP server:** `obsidian-brain-mcp` v1.1 (tools: `brain_status`, `read_file`, `read_section`, `search_brain`)
- **Como usar:** prefira `read_section` quando souber a skill **e a seção**; `read_file` quando precisar do arquivo inteiro; `search_brain` só para descoberta; `brain_status` só na 1ª conversa em máquina nova.

---

## O projeto

**Sentinel** é um painel pessoal de renda variável da B3 (ações, FIIs, ETFs) e da carteira de
investimentos. Um coletor Python grava cotações do Yahoo Finance no Postgres; o Laravel lê esse
banco, registra compras/vendas e calcula posição, foto de um dia passado e evolução do patrimônio.
Visão completa (funcionalidades, tabelas, scripts, regras de cálculo, limitações e ideias) no
[`README.md`](README.md). Referência do coletor em [`scripts/python/README.md`](scripts/python/README.md).

---

## Stack do projeto

Monolito **full-stack**: painel web server-rendered (Blade) + API REST no mesmo app, mais um
coletor Python no mesmo repositório e no mesmo banco.

- **Linguagem:** PHP `^8.2`
- **Framework:** Laravel `^12.0` (12.53)
- **Banco de dados:** PostgreSQL (`DB_CONNECTION=pgsql`) — database `SENTINEL`, compartilhado com o coletor
- **Front-end:** Blade + CSS e JS próprios por tela (sem framework JS), design system em `resources/css/tokens.css` + `components.css`
- **Build de assets:** Vite 7 (`laravel-vite-plugin`). Tailwind 4 está no scaffold (`resources/css/app.css`), mas **nenhum layout carrega** — não use classes Tailwind
- **Coletor:** Python 3 em `scripts/python/` — `yfinance`, `psycopg2-binary`, `python-dotenv` (`pyTelegramBotAPI` instalado, ainda sem uso)
- **Agendamento da coleta:** cron do servidor (não o scheduler do Laravel), a cada 15 min em dias úteis
- **API:** respostas via `App\Helpers\ApiResponse`; L5-Swagger instalado, sem anotações nas rotas do Sentinel
- **Autenticação:** JWT (`tymon/jwt-auth`) + Sanctum vêm do template e **não protegem** as rotas do Sentinel (painel pessoal)
- **Testes:** **PHPUnit 11** (`./vendor/bin/phpunit`) — este projeto **não usa Pest**
- **Template de origem:** `dtic/php-skeleton` (SIGA/SOAP, LDAP, Socialite, DomPDF, Excel herdados e sem uso — ver README §12)

### Quando consultar o Brain (regra mecânica)

| Tarefa                                                    | Skill primária                            |
| --------------------------------------------------------- | ------------------------------------------ |
| Criar/refatorar fluxo backend (Controller→Service→Repo)   | `Skills/dev/skill-layers.md`             |
| Regras de backend / Eloquent / queries                    | `Skills/dev/skill-back.md`               |
| Preencher DTOs a partir de model/request                  | `Skills/dev/skill-dto-filler.md`         |
| Revisão de segurança / discordância ativa                 | `Skills/dev/skill-secur.md`              |
| Escrever testes (PHPUnit)                                 | `Skills/dev/skill-qa.md`, `Skills/dev/skill-unit-tests.md` |
| Tela/UI nova (Blade + CSS + JS isolados)                  | `Skills/dev/skill-front.md`              |
| Setup Docker, deploy, CI/CD, cron                         | `Skills/dev/skill-infra.md`              |
| Documentar API (Swagger/OpenAPI)                          | `Skills/dev/skill-swagger-docs.md`       |
| Gerar collection Postman de um CRUD                       | `Skills/dev/skill-postman-crud.md`       |
| Tarefa complexa (>3 arquivos, planejar antes de codar)    | `Skills/ops/skill-planner.md`            |
| Decisão arquitetural durável                              | `Skills/ops/skill-memory.md` (criar ADR) |

### Quando NÃO consultar o Brain

- Rename de variável/função
- Fix de typo / formatação
- Comando rápido (ex: "rode os testes", "abra o arquivo X")
- Pergunta puramente informativa sobre o código existente
- Qualquer tarefa mecânica que não envolva decisão arquitetural

---

## Padrões inegociáveis deste projeto

### Backend
- **Fluxo canônico:** `FormRequest → DTO → Service → Repository → ResponseDTO`. Detalhe em `Skills/dev/skill-layers.md`.
- Nunca passar `$request` cru ao Service — sempre `$request->validated()` (ou um método do FormRequest que normaliza, como `EvolucaoRequest::intervalo()`).
- Service nunca devolve Eloquent Model para o controller — sempre `ResponseDTO::fromModel()` (ou `->toArray()`).
- Repository é o único que faz query. `store()` e `update()` chamam `->load($this->withRelations())` antes de retornar.
- Interface de Service e de Repository em `app/Contracts`, vinculadas no `AppServiceProvider`.
- Service com múltiplas escritas usa `DB::transaction()`.
- Controllers web (`Web\PainelController`) e de API reaproveitam os mesmos Services — sem regra de negócio no controller.
- API sem versionamento (`/api/acao`, `/api/movimentacao`), respostas via `ApiResponse::success|created|paginated`.

### Nomenclatura e comentários
- **Domínio em PT-BR**, como já está no código: tabelas, models, colunas, métodos e variáveis de negócio (`Acao`, `Movimentacao`, `posicoes()`, `fotoDoDia()`, `preco_medio`). Termos de framework seguem o Laravel em inglês (`store`, `update`, `index`, `Repository`, `Service`). Não misture: código novo de domínio segue o padrão em português do arquivo vizinho.
- Comentários e explicações em **PT-BR**, só quando o "porquê" não for óbvio. Sem PHPDoc descritivo em métodos internos.

### Regras de domínio (não quebrar)
- **Só o coletor Python escreve cotações** (`cotacoes`, `cotacoes_diarias`, `coletas`). O Laravel apenas lê.
- **`movimentacoes` é a fonte da verdade da carteira.** Posição, preço médio, valorização, foto do dia e evolução são **calculados na hora** — não criar tabela de saldo/patrimônio sem decisão explícita (ADR).
- **Custo médio ponderado** em `app/Services/Carteira/LivroCarteira.php`: venda não altera o preço médio e gera lucro realizado. Toda consolidação de posição passa por ele.
- **Validação de saldo** (`MovimentacaoService::validarSaldo`) roda em registrar, editar e excluir: nenhuma venda pode deixar o saldo negativo em data nenhuma. Ordem do livro: data → compras antes de vendas → id.
- **Preço por data:** usar `fechamento` (preço de tela) de `cotacoes_diarias`, **não** o `fechamento_ajustado`. Dia sem fechamento usa o último pregão anterior.
- **Evolução:** ganho de mercado = valor final − valor inicial − (compras − vendas); rentabilidade TWR com compras no início e vendas no fim do dia. Ver README §3.4.

### Front-end
- Uma tela = Blade + `resources/css/pages/{tela}.css` + `resources/js/pages/{tela}.js`, registrados no `vite.config.js`.
- Reaproveitar `tokens.css` (cores, espaços, raios, tipografia, tema claro/escuro via `<html data-theme>`) e os componentes de `components.css` / `resources/views/components` antes de criar CSS novo. Nenhuma cor hardcoded: sempre token.
- **Progressive enhancement:** a tela precisa funcionar sem JS (gráficos em SVG desenhados no servidor, calendário cai em `<input type="date">`, filtros com fallback).
- Alta/queda sempre com cor **e** ▲/▼ **e** texto `sr-only`. Números com `font-variant-numeric: tabular-nums`.
- Layouts: `layouts.monitor` (novo — monitores, carteira, evolução) e `layouts.app` (legado — cadastro, ferramentas, formulários). Tela nova vai no `monitor`.
- Responsivo a partir de 375px sem scroll horizontal; nav completa no topo a partir de 1024px.

### Coletor Python
- Todo SQL do coletor fica em `scripts/python/database/repository.py`; chamadas externas em `services/`.
- Rodar sempre com o Python do venv (`.venv/bin/python`); credenciais do banco vêm do `.env` do Laravel.
- Toda execução abre e fecha uma linha em `coletas` (`fim` nulo = não terminou).

---

## Comandos úteis

```bash
# Backend
php artisan serve
php artisan migrate
./vendor/bin/phpunit
php artisan cls                       # limpa caches (comando do template)

# Assets
npm run dev
npm run build

# Coletor (a partir de scripts/python/)
.venv/bin/python fetch_stocks.py                      # coleta do momento (todos os ativos ativos)
.venv/bin/python fetch_stocks.py VALE3.SA --no-save   # só imprime
.venv/bin/python backfill_history.py --period=5y      # carga histórica de cotacoes_diarias
.venv/bin/python export_sync.py                       # dev: exporta acoes/movimentacoes p/ CSV
.venv/bin/python import_sync.py                       # servidor: importa os CSV (após o migrate)

# Deploy (servidor Linux)
bash deploy.sh                        # pull → composer → migrate → npm build → optimize:clear

# Geradores custom do template
php artisan make:service {Name} -r -b   # -r gera Repository + interface, -b registra os binds
php artisan make:crud {Name}
php artisan make:bind
php artisan sync:contract
php artisan postman:generate
```

> No Windows, `php artisan tinker arquivo.php` fica esperando o stdin — rode com `< /dev/null`.

---

## Mapa rápido

| Preciso mexer em… | Onde |
| --- | --- |
| Telas (todas) | `app/Http/Controllers/Web/PainelController.php` + `routes/web.php` |
| Monitores (Ações/FIIs/ETFs) | `resources/views/acoes/index.blade.php`, `components/asset-card`, `asset-row`, `sparkline`, `range-bar` |
| Meus ativos / foto do dia | `resources/views/carteira/index.blade.php`, `CarteiraService::fotoDoDia`, `MovimentacaoService::posicoes` |
| Evolução do patrimônio | `resources/views/carteira/evolucao.blade.php`, `components/evolucao-chart`, `CarteiraService::evolucao` |
| Compras e vendas | `MovimentacaoService`, `Services/Carteira/LivroCarteira`, `resources/views/movimentacoes/*` |
| Fechamentos diários | `CotacaoDiariaRepository` |
| Calendário | `components/date-picker.blade.php` + `resources/js/components/date-picker.js` |
| Coleta / tabelas de cotação | `scripts/python/` (nunca pelo Laravel) |
| Tokens e componentes visuais | `resources/css/tokens.css`, `resources/css/components.css`, `resources/js/sentinel.js` |
| Handoffs de layout (Claude Design) | `app/Docs/layout/` |

---

## Produção

- Servidor Linux; `fetch_stocks.py` no cron a cada 15 min em dias úteis (`CRON_TZ=America/Sao_Paulo`, com `flock`). A última execução do dia precisa ser **após as 18h** para gravar o fechamento definitivo.
- `cotacoes_diarias` tem ~5 anos de histórico de todos os ativos em produção; no dev os dados são parciais.
- Migration nova que mexe em dados (ex.: `aportes` → `movimentacoes`) roda **antes** do `import_sync.py`.
- Nunca rodar `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback` ou `db:wipe` sem pedido explícito — o banco tem o histórico de cotações e a carteira real.

---

## Agentes globais

- **`audit-code`** — auditor read-only: varre um fluxo a partir de um ponto de entrada e devolve um plano; o orquestrador grava em `/plans` só se houver item.
- **`refactor-code`** — executor: aplica um item aprovado do plano por vez; pode criar migrations/models/DTOs/services e rodar testes; nunca roda comando destrutivo de banco nem faz commit/push.

---

## Pendências conhecidas

- **Suíte de testes não sobe:** `app/Console/Commands/MakeDTO.php` declara a classe `MakeService` (duplicada com `MakeService.php`), o que quebra o boot do `php artisan test`. Também não há testes do domínio do Sentinel, e o `phpunit.xml` usa SQLite em memória enquanto parte das consultas é específica do Postgres (`date_trunc`, `NULLS LAST`).
- **Fuso do Laravel em UTC** (`config/app.php`) enquanto o coletor grava `capturado_em` no horário da B3: entre 21h e 0h de Brasília, `today()` já é o dia seguinte (validações de data e limite do calendário).
- **Proventos e desdobramentos** ainda não entram no livro da carteira (README §10).
- Código herdado do template sem uso (SIGA, LDAP, Socialite, DomPDF, Excel, `TestController`, `DogRepository`).

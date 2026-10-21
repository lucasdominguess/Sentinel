# Comandos Artisan Customizados

Este documento centraliza os comandos customizados disponíveis em `app/Console/Commands`.

## Visão Geral

| Comando | Objetivo |
| --- | --- |
| `php artisan cls` | Limpa caches e otimizações do Laravel |
| `php artisan make:dto` | Gera DTOs de entrada, array ou resposta |
| `php artisan make:abstract` | Gera controllers abstratos base |
| `php artisan make:service` | Gera services, contracts, repositories e integrações auxiliares |
| `php artisan make:bind` | Gerencia binds manuais e automáticos no container |
| `php artisan sync:contract` | Sincroniza métodos públicos entre classes concretas e interfaces |
| `php artisan postman:generate` | Gera collection do Postman a partir das rotas da API |

---

## 1. `cls`

Arquivo fonte: `app/Console/Commands/CleanAll.php`

### Objetivo

Executa uma limpeza padronizada do projeto Laravel, removendo caches e, opcionalmente, atualizando o autoload do Composer e otimizando a aplicação para produção.

### Assinatura

```bash
php artisan cls {--all} {--prod}
```

### Opções

- `--all`: inclui `composer dump-autoload`
- `--prod`: executa otimizações adicionais para produção

### Fluxo executado

1. `cache:clear`
2. `view:clear`
3. `event:clear`
4. `config:clear`
5. `route:clear`
6. `clear-compiled`
7. `optimize:clear`
8. recriação de cache com `config:cache`, `route:cache` e `event:cache`

### Exemplos

```bash
php artisan cls
php artisan cls --all
php artisan cls --prod
php artisan cls --all --prod
```

### Quando usar

- após alterar configs, rotas ou eventos
- depois de mudanças estruturais no projeto
- antes de subir uma build para produção

---

## 2. `make:dto`

Arquivo fonte: `app/Console/Commands/MakeDTO.php`

### Objetivo

Gera DTOs em `app/DTOs`, com suporte a DTO de request, array ou response.

### Assinatura

```bash
php artisan make:dto {name} {--r|response} {--A|array} {--N|no-suffix}
```

### Opções

- `-r`, `--response`: gera `ResponseDTO` com método `fromModel()`
- `-A`, `--array`: gera DTO com método `fromArray()`
- `-N`, `--no-suffix`: não força o sufixo `DTO` ou `ResponseDTO`

### Regras principais

- por padrão, gera `NomeDTO`
- com `-r`, gera `NomeResponseDTO`
- aceita subpastas, por exemplo `Admin/User`
- evita sobrescrever arquivos existentes
- cria diretórios automaticamente

### Exemplos

```bash
php artisan make:dto User
php artisan make:dto User --array
php artisan make:dto User --response
php artisan make:dto Admin/User
php artisan make:dto UserDTO --no-suffix
```

### Estruturas geradas

Request DTO:

```php
class UserDTO
{
    public static function fromRequest(array $data): self
    {
        return new self();
    }
}
```

Response DTO:

```php
class UserResponseDTO
{
    public static function fromModel(Model $model): self
    {
        return new self();
    }
}
```

---

## 3. `make:abstract`

Arquivo fonte: `app/Console/Commands/MakeAbstract.php`

### Objetivo

Gera classes abstratas de controller, normalmente em `app/Http/Controllers/Abstracts`.

### Assinatura

```bash
php artisan make:abstract {name} {--N|no-suffix} {--a=} {--f=}
```

### Opções

- `-N`, `--no-suffix`: não adiciona automaticamente `AbstractController`
- `--a=`: injeta atributos no construtor
- `--f=`: define caminho customizado dentro de `app/`

### Regras principais

- por padrão, gera `NomeAbstractController`
- aceita subpastas, por exemplo `Admin/Auth`
- ajusta namespace automaticamente
- pergunta o nível de acesso das propriedades quando há atributos e o terminal está em modo interativo
- suporta `public`, `private`, `protected` ou valor customizado

### Exemplos

```bash
php artisan make:abstract Auth
php artisan make:abstract Admin/Auth
php artisan make:abstract Auth --a='AdminServiceInterface $service'
php artisan make:abstract Auth --f='Http/Controllers/Base'
php artisan make:abstract Auth --no-suffix
```

### Estrutura gerada

```php
abstract class AuthAbstractController extends Controller
{
    public function __construct(
        protected AdminServiceInterface $service,
    ) {}
}
```

---

## 4. `make:service`

Arquivo fonte: `app/Console/Commands/MakeService.php`

### Objetivo

Gera uma camada de service com suporte opcional a:

- contract de service
- repository e contract de repository
- métodos CRUD
- DTOs no stub
- bind automático no container
- injeção em controller

### Assinatura

```bash
php artisan make:service {name} {--r|repository} {--b|bind} {--c|contract} {--d|dto} {--m|methods} {--i=} {--f=}
```

### Opções

- `-r`, `--repository`: gera repository e interface do repository
- `-b`, `--bind`: registra binds no `AppServiceProvider`
- `-c`, `--contract`: gera interface do service
- `-d`, `--dto`: gera assinaturas preparadas para `DTO` e `ResponseDTO`
- `-m`, `--methods`: adiciona métodos CRUD aos stubs
- `--i=`: injeta o service em um controller existente
- `--f=`: define caminho customizado apenas para o service

### Estruturas geradas

Sem flags extras:

- `app/Services/UserService.php`

Com `-c`:

- `app/Services/UserService.php`
- `app/Contracts/Services/UserServiceInterface.php`

Com `-r`:

- `app/Services/UserService.php`
- `app/Repositories/UserRepository.php`
- `app/Contracts/Services/UserServiceInterface.php` quando necessário
- `app/Contracts/Repositories/UserRepositoryInterface.php`

### Regras principais

- `Services` contém apenas services concretos
- `Repositories` contém apenas repositories concretos
- interfaces ficam em `app/Contracts/Services` e `app/Contracts/Repositories`
- aceita subpastas, por exemplo `Admin/User`
- não sobrescreve arquivos existentes
- ajusta namespace automaticamente
- `--bind` exige `--contract`
- `--i` procura controllers pelo basename mesmo em subpastas de `app/Http/Controllers`
- sem `-c`, a injeção com `--i` usa o service concreto em vez da interface
- sem `-m`, o stub nasce sem métodos CRUD
- com `-m`, adiciona `create`, `update`, `delete` e `find`

### Exemplos

```bash
php artisan make:service Auth
php artisan make:service Auth --contract
php artisan make:service Auth --contract --methods
php artisan make:service Auth --repository --contract --bind
php artisan make:service Admin/User --repository --dto --methods
php artisan make:service Auth --contract --i=AuthController
php artisan make:service Auth --contract --f='Domain/Services'
```

### Exemplo de stub simples

```php
class UserService
{
    public function __construct(
        // dependências
    ) {}
}
```

### Exemplo com contract + repository + methods

```php
class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository,
    ) {}

    public function create(array $data)
    {
        // lógica
    }
}
```

---

## 5. `make:bind`

Arquivo fonte: `app/Console/Commands/MakeBind.php`

### Objetivo

Gerencia binds de interfaces e classes no `AppServiceProvider`, com suporte a modo manual e modo inteligente por scanner.

### Assinatura

```bash
php artisan make:bind {interface?} {class?} {--dry-run} {--force} {--sort}
```

### Modos de uso

Modo manual:

```bash
php artisan make:bind UserServiceInterface UserService
```

Modo inteligente:

```bash
php artisan make:bind
```

Sem argumentos, o comando pode entrar em modo interativo e perguntar se deve verificar binds pendentes automaticamente.

### Opções

- `--dry-run`: mostra os binds que seriam adicionados sem modificar arquivos
- `--force`: reescreve binds já existentes quando necessário
- `--sort`: ordena alfabeticamente os binds dentro do `register()`

### Scanner automático

O scanner verifica os pares interface/classe nos diretórios:

- `app/Services/Contracts`
- `app/Repositories/Contracts`
- `app/Contracts/Services`
- `app/Contracts/Repositories`
- `app/Interfaces`
- `app/Services`
- `app/Repositories`

O match segue a regra:

```text
UserServiceInterface -> UserService
AuthRepositoryInterface -> AuthRepository
```

Ou seja, o comando remove o sufixo `Interface` e procura a classe concreta correspondente.

### Seleção interativa

Quando encontra binds pendentes, o comando exibe uma lista numerada e aceita seleções como:

```text
all
1 3
all --except=2,5
all 2 -n 5 -n
```

### Validações

- verifica se o `AppServiceProvider` existe
- valida se a interface existe
- valida se a classe concreta existe
- ignora arquivos inválidos
- evita duplicidade quando o bind já existe, exceto com `--force`

### Exemplos

```bash
php artisan make:bind LdapInterface LdapService
php artisan make:bind SocialAuthInterface GoogleAuthService --dry-run
php artisan make:bind --dry-run
php artisan make:bind --sort
php artisan make:bind --force --sort
```

### Resultado esperado

O comando adiciona dentro de `register(): void` blocos como:

```php
$this->app->bind(
    UserServiceInterface::class,
    UserService::class
);
```

### Observação de arquitetura

A implementação já está separada em responsabilidades menores:

- resolução manual de referências
- scanner de pendências
- parser de binds existentes
- seleção interativa
- escrita no provider

Isso facilita uma futura evolução para um comando como `php artisan sync:container`.

---

## 6. `sync:contract`

Arquivo fonte: `app/Console/Commands/SyncContract.php`

### Objetivo

Sincroniza métodos públicos de classes concretas `Service` e `Repository` com suas interfaces correspondentes.

### Assinatura

```bash
php artisan sync:contract {class?} {--check} {--force} {--only-missing} {--sort} {--dry-run}
```

### Modos de uso

Modo manual:

```bash
php artisan sync:contract UserService
php artisan sync:contract Admin/UserRepository --check
```

Modo automático:

```bash
php artisan sync:contract
php artisan sync:contract --check
```

Sem argumentos, o comando pergunta se deve verificar todos os `Services` e `Repositories` automaticamente quando o terminal está em modo interativo.

### Opções

- `--check`: apenas mostra métodos faltantes, diferentes ou extras, sem alterar arquivos
- `--force`: reescreve a interface mesmo quando o conteúdo final já esteja equivalente
- `--only-missing`: adiciona apenas métodos ausentes, preservando métodos já existentes
- `--sort`: ordena alfabeticamente os métodos da interface final
- `--dry-run`: exibe o conteúdo final da interface sem salvar

### Regras principais

- verifica classes em `app/Services` e `app/Repositories`
- resolve interfaces tanto no layout atual quanto no legado
- layout atual: `app/Contracts/Services` e `app/Contracts/Repositories`
- layout legado: `app/Services/Contracts` e `app/Repositories/Contracts`
- ignora `__construct` e outros métodos mágicos
- sincroniza apenas métodos `public`
- valida namespace, nome da declaração e existência dos arquivos antes de escrever

### Exemplos

```bash
php artisan sync:contract LdapService
php artisan sync:contract UserRepository --check
php artisan sync:contract UserRepository --dry-run --sort
php artisan sync:contract --check
php artisan sync:contract --only-missing --sort
```

### Resultado esperado

Uma interface como `UserRepositoryInterface` passa a refletir os métodos públicos da classe concreta correspondente, por exemplo:

```php
interface UserRepositoryInterface
{
    public function create(array $data): array;

    public function delete(int $id): bool;
}
```

---

## 7. `postman:generate`

Arquivo fonte: `app/Console/Commands/PostmanGenerate.php`

### Objetivo

Gera uma collection do Postman com base nas rotas da API do projeto.

### Assinatura

```bash
php artisan postman:generate
```

### Fluxo interativo

Durante a execução, o comando pergunta:

1. se a collection terá nome customizado
2. se a API usa Bearer Token
3. se deve incluir rotas do Swagger
4. qual host será usado
5. qual porta será usada

### Comportamento

- percorre as rotas registradas no Laravel
- inclui rotas `api/*`
- opcionalmente inclui Swagger e documentação
- ignora rotas internas iniciadas com `_`
- organiza as requisições em pastas por segmento da URL
- cria autenticação Bearer quando solicitado
- salva o token automaticamente em requests de login quando aplicável

### Arquivo gerado

- `storage/app/postman_collection.json`

### Exemplo de uso

```bash
php artisan postman:generate
```

---

## Observações Gerais

- todos os comandos estão centralizados em `app/Console/Commands`
- os geradores usam validação para evitar sobrescrita indevida
- os comandos aceitam subpastas em boa parte dos casos, mantendo namespaces consistentes
- após criar ou alterar estruturas importantes, o comando `php artisan cls` pode ser útil para limpar e reconstruir caches
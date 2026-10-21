<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    protected $signature = 'make:crud
        {name : Nome da entidade, aceita subpastas (ex: User ou Accout/User)}
        {--m|model= : SubPath da Model em app/Models/ (default: mesmo subPath do name)}
        {--no-bind : Não registra os binds Interface→Implementação no AppServiceProvider}
        {--no-routes : Não registra o grupo de rotas em routes/api.php}';

    protected $description = 'Gera o stack CRUD completo (Controller, Service, Repository, Interfaces, DTOs e FormRequests) seguindo o padrão "fluxo canônico" do projeto.';

    protected $help = <<<HELP
Cria 10 arquivos + registra binds:
  - Controller (index/show/store/update/destroy com route model binding e ApiResponse)
  - Service (orquestra lógica, retorna ResponseDTO; index transforma o paginator)
  - Repository (paginate + show via route model binding)
  - ServiceInterface + RepositoryInterface
  - DTO e ResponseDTO (placeholders para você preencher)
  - FilterIndexRequest com prepareForValidation() agrupando filtros em array
  - CreateRequest e UpdateRequest

Padrão do FilterIndexRequest:
  GET /user?perPage=10&page=1&name=lucas&email=teste@x.com
  -> validated: ['perPage'=>10, 'page'=>1, 'filters'=>['name'=>'lucas','email'=>'teste@x.com']]

Exemplos:
  php artisan make:crud User
  php artisan make:crud Accout/User
  php artisan make:crud Product --model=Inventory     # model em App\\Models\\Inventory\\Product
  php artisan make:crud Order --no-bind               # não registra binds
  php artisan make:crud Order --no-routes             # não registra rotas

Rota gerada em routes/api.php:
  Route::apiResource('user', UserController::class);

Aceita correções automáticas:
  user            -> User
  userrepository  -> User       (strip suffix)
  accout/user     -> Accout/User
HELP;

    public function handle()
    {
        $rawName = (string) $this->argument('name');
        if (trim($rawName, "/\\ ") === '') {
            $this->error('Informe um nome válido para o CRUD.');
            return Command::FAILURE;
        }

        [$baseName, $subPath] = $this->normalizeNameAndSubPath($rawName);

        if ($baseName === '') {
            $this->error('Não foi possível extrair um nome de classe válido.');
            return Command::FAILURE;
        }

        // Resolve subPath da Model — default: igual ao subPath da entidade
        $customModelPath = trim((string) ($this->option('model') ?? ''), "/\\ ");
        if ($customModelPath !== '') {
            $modelSubPath = $this->normalizeSubPath($customModelPath);
        } else {
            $modelSubPath = $subPath;
        }

        $modelClass = $baseName;
        $modelNamespace = 'App\\Models'
            . ($modelSubPath !== '' ? '\\' . str_replace('/', '\\', $modelSubPath) : '')
            . '\\' . $modelClass;
        $modelVar = lcfirst($baseName);

        $shouldBind = !$this->option('no-bind');
        $shouldRegisterRoutes = !$this->option('no-routes');

        $specs = $this->buildFileSpecs(
            $baseName,
            $subPath,
            $modelClass,
            $modelNamespace,
            $modelVar
        );

        // Verifica conflitos antes de qualquer escrita
        foreach ($specs as $spec) {
            if (File::exists($spec['path'])) {
                $this->error('O arquivo já existe: ' . $spec['path']);
                return Command::FAILURE;
            }
        }

        // Escreve os arquivos
        foreach ($specs as $spec) {
            File::ensureDirectoryExists(dirname($spec['path']));
            File::put($spec['path'], $spec['content']);
            $this->line('  <info>created</info>  ' . $this->toRelative($spec['path']));
        }

        if ($shouldBind) {
            $registered = $this->registerBinds($baseName, $subPath);
            if ($registered) {
                $this->line('  <info>updated</info>  ' . $this->toRelative(app_path('Providers/AppServiceProvider.php')));
            }
        }

        if ($shouldRegisterRoutes) {
            $routed = $this->registerRoutes($baseName, $subPath, $modelVar);
            if ($routed) {
                $this->line('  <info>updated</info>  ' . $this->toRelative(base_path('routes/api.php')));
            }
        }

        $this->newLine();
        $this->info("CRUD [{$baseName}] gerado com sucesso.");

        return Command::SUCCESS;
    }

    // ---------------- normalização ----------------

    private function normalizeNameAndSubPath(string $input): array
    {
        $normalized = str_replace('\\', '/', trim($input, "/\\ "));
        $segments = array_values(array_filter(explode('/', $normalized), fn($s) => $s !== ''));

        if ($segments === []) {
            return ['', ''];
        }

        $segments = array_map(fn($s) => Str::studly($s), $segments);

        $baseName = (string) array_pop($segments);
        $baseName = $this->stripKnownSuffixes($baseName);
        $baseName = Str::studly($baseName);

        $subPath = implode('/', $segments);

        return [$baseName, $subPath];
    }

    private function normalizeSubPath(string $input): string
    {
        $normalized = str_replace('\\', '/', trim($input, "/\\ "));
        $segments = array_values(array_filter(explode('/', $normalized), fn($s) => $s !== ''));
        $segments = array_map(fn($s) => Str::studly($s), $segments);

        return implode('/', $segments);
    }

    private function stripKnownSuffixes(string $name): string
    {
        $suffixes = [
            'ServiceInterface',
            'RepositoryInterface',
            'ResponseDTO',
            'CreateRequest',
            'UpdateRequest',
            'IndexRequest',
            'Controller',
            'Service',
            'Repository',
            'Request',
            'DTO',
            'Interface',
        ];

        $lower = strtolower($name);
        foreach ($suffixes as $suffix) {
            $sufLower = strtolower($suffix);
            if (Str::endsWith($lower, $sufLower) && strlen($name) > strlen($suffix)) {
                return substr($name, 0, -strlen($suffix));
            }
        }

        return $name;
    }

    private function toRelative(string $path): string
    {
        $base = base_path() . DIRECTORY_SEPARATOR;
        if (Str::startsWith($path, $base)) {
            return substr($path, strlen($base));
        }
        return $path;
    }

    // ---------------- especificação dos arquivos ----------------

    private function buildFileSpecs(
        string $baseName,
        string $subPath,
        string $modelClass,
        string $modelFqcn,
        string $modelVar
    ): array {
        $dirSeg = $subPath !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath) : '';
        $nsSeg = $subPath !== '' ? '\\' . str_replace('/', '\\', $subPath) : '';

        $controllerNs = 'App\\Http\\Controllers' . $nsSeg;
        $serviceNs = 'App\\Services' . $nsSeg;
        $repositoryNs = 'App\\Repositories' . $nsSeg;
        $serviceContractNs = 'App\\Contracts\\Services' . $nsSeg;
        $repositoryContractNs = 'App\\Contracts\\Repositories' . $nsSeg;
        $dtoNs = 'App\\DTOs' . $nsSeg;
        $requestNs = 'App\\Http\\Requests' . $nsSeg;

        $ctx = [
            'baseName' => $baseName,
            'modelClass' => $modelClass,
            'modelFqcn' => $modelFqcn,
            'modelVar' => $modelVar,
            'controllerNs' => $controllerNs,
            'serviceNs' => $serviceNs,
            'repositoryNs' => $repositoryNs,
            'serviceContractNs' => $serviceContractNs,
            'repositoryContractNs' => $repositoryContractNs,
            'dtoNs' => $dtoNs,
            'requestNs' => $requestNs,
        ];

        return [
            [
                'path' => app_path('Http' . DIRECTORY_SEPARATOR . 'Controllers' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'Controller.php'),
                'content' => $this->stubController($ctx),
            ],
            [
                'path' => app_path('Services' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'Service.php'),
                'content' => $this->stubService($ctx),
            ],
            [
                'path' => app_path('Repositories' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'Repository.php'),
                'content' => $this->stubRepository($ctx),
            ],
            [
                'path' => app_path('Contracts' . DIRECTORY_SEPARATOR . 'Services' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'ServiceInterface.php'),
                'content' => $this->stubServiceInterface($ctx),
            ],
            [
                'path' => app_path('Contracts' . DIRECTORY_SEPARATOR . 'Repositories' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'RepositoryInterface.php'),
                'content' => $this->stubRepositoryInterface($ctx),
            ],
            [
                'path' => app_path('DTOs' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'DTO.php'),
                'content' => $this->stubDTO($ctx),
            ],
            [
                'path' => app_path('DTOs' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'ResponseDTO.php'),
                'content' => $this->stubResponseDTO($ctx),
            ],
            [
                'path' => app_path('Http' . DIRECTORY_SEPARATOR . 'Requests' . $dirSeg . DIRECTORY_SEPARATOR . 'Filter' . $baseName . 'IndexRequest.php'),
                'content' => $this->stubFilterIndexRequest($ctx),
            ],
            [
                'path' => app_path('Http' . DIRECTORY_SEPARATOR . 'Requests' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'CreateRequest.php'),
                'content' => $this->stubBasicRequest($ctx, $baseName . 'CreateRequest'),
            ],
            [
                'path' => app_path('Http' . DIRECTORY_SEPARATOR . 'Requests' . $dirSeg . DIRECTORY_SEPARATOR . $baseName . 'UpdateRequest.php'),
                'content' => $this->stubBasicRequest($ctx, $baseName . 'UpdateRequest'),
            ],
        ];
    }

    // ---------------- bind em AppServiceProvider ----------------

    private function registerBinds(string $baseName, string $subPath): bool
    {
        $providerPath = app_path('Providers' . DIRECTORY_SEPARATOR . 'AppServiceProvider.php');

        if (!File::exists($providerPath)) {
            $this->warn('AppServiceProvider não encontrado, binds não registrados.');
            return false;
        }

        $nsSeg = $subPath !== '' ? '\\' . str_replace('/', '\\', $subPath) : '';

        $serviceContractFqcn = 'App\\Contracts\\Services' . $nsSeg . '\\' . $baseName . 'ServiceInterface';
        $serviceFqcn = 'App\\Services' . $nsSeg . '\\' . $baseName . 'Service';
        $repoContractFqcn = 'App\\Contracts\\Repositories' . $nsSeg . '\\' . $baseName . 'RepositoryInterface';
        $repoFqcn = 'App\\Repositories' . $nsSeg . '\\' . $baseName . 'Repository';

        $content = File::get($providerPath);

        $alreadyBoundService = preg_match(
            '/bind\(\s*' . preg_quote($baseName . 'ServiceInterface', '/') . '::class\s*,\s*' . preg_quote($baseName . 'Service', '/') . '::class\s*\)/s',
            $content
        ) === 1;

        $alreadyBoundRepo = preg_match(
            '/bind\(\s*' . preg_quote($baseName . 'RepositoryInterface', '/') . '::class\s*,\s*' . preg_quote($baseName . 'Repository', '/') . '::class\s*\)/s',
            $content
        ) === 1;

        if ($alreadyBoundService && $alreadyBoundRepo) {
            return false;
        }

        $content = $this->addUseStatement($content, $serviceContractFqcn);
        $content = $this->addUseStatement($content, $serviceFqcn);
        $content = $this->addUseStatement($content, $repoContractFqcn);
        $content = $this->addUseStatement($content, $repoFqcn);

        $bindLines = [];
        if (!$alreadyBoundService) {
            $bindLines = array_merge($bindLines, [
                '        $this->app->bind(',
                "            {$baseName}ServiceInterface::class,",
                "            {$baseName}Service::class",
                '        );',
            ]);
        }
        if (!$alreadyBoundRepo) {
            $bindLines = array_merge($bindLines, [
                '        $this->app->bind(',
                "            {$baseName}RepositoryInterface::class,",
                "            {$baseName}Repository::class",
                '        );',
            ]);
        }

        $bindBlock = implode("\n", $bindLines);

        $updated = preg_replace(
            '/(public function register\(\): void\s*\{\R)(.*?)(^\s{4}\})/ms',
            '$1$2' . $bindBlock . "\n$3",
            $content,
            1,
            $count
        );

        if ($updated === null || $count === 0) {
            $this->warn('Não foi possível registrar binds automaticamente no AppServiceProvider.');
            return false;
        }

        File::put($providerPath, $updated);
        return true;
    }

    private function registerRoutes(string $baseName, string $subPath, string $modelVar): bool
    {
        $routesPath = base_path('routes' . DIRECTORY_SEPARATOR . 'api.php');

        if (!File::exists($routesPath)) {
            $this->warn('routes/api.php não encontrado, rotas não registradas.');
            return false;
        }

        $controllerClass = $baseName . 'Controller';
        $nsSeg = $subPath !== '' ? '\\' . str_replace('/', '\\', $subPath) : '';
        $controllerFqcn = 'App\\Http\\Controllers' . $nsSeg . '\\' . $controllerClass;

        $content = File::get($routesPath);

        // Verifica pelo FQCN exato — evita colisão com controllers de mesmo nome em outros namespaces
        if (Str::contains($content, 'use ' . $controllerFqcn . ';')) {
            return false;
        }

        $content = $this->addUseStatement($content, $controllerFqcn);

        // Prefix em kebab-case: User -> user, UserProfile -> user-profile
        $prefix = Str::kebab($baseName);

        $routeBlock = implode("\n", [
            '',
            "Route::apiResource('{$prefix}', {$controllerClass}::class);",
            '',
        ]);

        File::put($routesPath, rtrim($content, "\n") . "\n" . $routeBlock);

        return true;
    }

    private function addUseStatement(string $content, string $fqcn): string
    {
        $useStatement = 'use ' . $fqcn . ';';

        if (Str::contains($content, $useStatement)) {
            return $content;
        }

        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $lastUse = end($matches[0]);
            $pos = $lastUse[1] + strlen($lastUse[0]);
            return substr($content, 0, $pos) . "\n" . $useStatement . substr($content, $pos);
        }

        if (preg_match('/namespace\s+[^;]+;/', $content, $m, PREG_OFFSET_CAPTURE) === 1) {
            $pos = $m[0][1] + strlen($m[0][0]);
            return substr($content, 0, $pos) . "\n\n" . $useStatement . substr($content, $pos);
        }

        return $content;
    }

    // ---------------- stubs ----------------

    private function stubController(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $modelVar = $c['modelVar'];
        $modelFqcn = $c['modelFqcn'];

        $uses = $this->buildUseBlock([
            'App\\Helpers\\ApiResponse',
            $c['serviceContractNs'] . '\\' . $name . 'ServiceInterface',
            $c['dtoNs'] . '\\' . $name . 'DTO',
            $c['requestNs'] . '\\Filter' . $name . 'IndexRequest',
            $c['requestNs'] . '\\' . $name . 'CreateRequest',
            $c['requestNs'] . '\\' . $name . 'UpdateRequest',
            $modelFqcn,
            'Illuminate\\Http\\JsonResponse',
            'Illuminate\\Routing\\Controller',
        ]);

        $ns = $c['controllerNs'];

        return <<<PHP
<?php

namespace {$ns};
{$uses}
class {$name}Controller extends Controller
{
    public function __construct(
        private {$name}ServiceInterface \$service,
    ) {}

    public function index(Filter{$name}IndexRequest \$request): JsonResponse
    {
        \$data = \$request->validated();
        \$paginator = \$this->service->index(\$data['perPage'], \$data['page'], \$data['filters']);

        return ApiResponse::paginated(\$paginator, '{$name}s recuperados com sucesso');
    }

    public function show({$model} \${$modelVar}): JsonResponse
    {
        \${$modelVar}Response = \$this->service->show(\${$modelVar});

        return ApiResponse::success(\${$modelVar}Response, '{$name} recuperado com sucesso');
    }

    public function store({$name}CreateRequest \$request): JsonResponse
    {
        \$data = \$request->validated();
        \${$modelVar}Response = \$this->service->store({$name}DTO::fromRequest(\$data));

        return ApiResponse::created(\${$modelVar}Response, '{$name} criado com sucesso');
    }

    public function update({$model} \${$modelVar}, {$name}UpdateRequest \$request): JsonResponse
    {
        \$data = \$request->validated();
        \${$modelVar}Response = \$this->service->update(\${$modelVar}, {$name}DTO::fromRequest(\$data));

        return ApiResponse::success(\${$modelVar}Response, '{$name} atualizado com sucesso');
    }

    public function destroy({$model} \${$modelVar}): JsonResponse
    {
        \$this->service->delete(\${$modelVar});

        return ApiResponse::success(null, '{$name} deletado com sucesso');
    }
}

PHP;
    }

    private function stubService(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $modelVar = $c['modelVar'];

        $uses = $this->buildUseBlock([
            $c['repositoryContractNs'] . '\\' . $name . 'RepositoryInterface',
            $c['serviceContractNs'] . '\\' . $name . 'ServiceInterface',
            $c['dtoNs'] . '\\' . $name . 'DTO',
            $c['dtoNs'] . '\\' . $name . 'ResponseDTO',
            $c['modelFqcn'],
            'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
        ]);

        $ns = $c['serviceNs'];

        return <<<PHP
<?php

namespace {$ns};
{$uses}
class {$name}Service implements {$name}ServiceInterface
{
    public function __construct(
        private {$name}RepositoryInterface \$repository,
    ) {}

    public function index(int \$perPage = 15, int \$page = 1, ?array \$filters = []): LengthAwarePaginator
    {
        \$paginator = \$this->repository->index(\$perPage, \$page, \$filters);

        \$paginator->getCollection()->transform(function (\$item) {
            return {$name}ResponseDTO::fromModel(\$item)->toArray();
        });

        return \$paginator;
    }

    public function show({$model} \${$modelVar}): {$name}ResponseDTO
    {
        \${$modelVar} = \$this->repository->show(\${$modelVar});

        return {$name}ResponseDTO::fromModel(\${$modelVar});
    }

    public function store({$name}DTO \$dto): {$name}ResponseDTO
    {
        \${$modelVar} = \$this->repository->store(\$dto->toArray());

        return {$name}ResponseDTO::fromModel(\${$modelVar});
    }

    public function update({$model} \${$modelVar}, {$name}DTO \$dto): {$name}ResponseDTO
    {
        \${$modelVar} = \$this->repository->update(\${$modelVar}, \$dto->toArray());

        return {$name}ResponseDTO::fromModel(\${$modelVar});
    }

    public function delete({$model} \${$modelVar})
    {
        return \$this->repository->delete(\${$modelVar});
    }
}

PHP;
    }

    private function stubRepository(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $modelVar = $c['modelVar'];

        $uses = $this->buildUseBlock([
            $c['repositoryContractNs'] . '\\' . $name . 'RepositoryInterface',
            $c['modelFqcn'],
            'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
        ]);

        $ns = $c['repositoryNs'];

        return <<<PHP
<?php

namespace {$ns};
{$uses}
class {$name}Repository implements {$name}RepositoryInterface
{
    public function __construct(
        private {$model} \${$modelVar}Model,
    ) {}

    public function index(int \$perPage = 15, int \$page = 1, ?array \$filters = []): LengthAwarePaginator
    {
        \$query = \$this->{$modelVar}Model->query();

        if (empty(\$filters)) return \$query->orderByDesc('id')->paginate(\$perPage);

        // Adicione filtros específicos aqui:
        // if (!empty(\$filters['name'])) {
        //     \$query->where('name', 'like', '%' . \$filters['name'] . '%');
        // }

        return \$query->orderByDesc('id')->paginate(\$perPage);
    }

    public function show({$model} \${$modelVar}): {$model}
    {
        // Route model binding já buscou o registro — adicione \$->load('relacao') se precisar
        return \${$modelVar};
    }

    public function store(array \$data): {$model}
    {
        return \$this->{$modelVar}Model->create(\$data);
    }

    public function update({$model} \${$modelVar}, array \$data): {$model}
    {
        \${$modelVar}->update(\$data);

        return \${$modelVar};
    }

    public function delete({$model} \${$modelVar})
    {
        return \${$modelVar}->delete();
    }
}

PHP;
    }

    private function stubServiceInterface(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $modelVar = $c['modelVar'];

        $uses = $this->buildUseBlock([
            $c['dtoNs'] . '\\' . $name . 'DTO',
            $c['dtoNs'] . '\\' . $name . 'ResponseDTO',
            $c['modelFqcn'],
            'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
        ]);

        $ns = $c['serviceContractNs'];

        return <<<PHP
<?php

namespace {$ns};
{$uses}
interface {$name}ServiceInterface
{
    public function index(int \$perPage = 15, int \$page = 1, ?array \$filters = []): LengthAwarePaginator;

    public function show({$model} \${$modelVar}): {$name}ResponseDTO;

    public function store({$name}DTO \$dto): {$name}ResponseDTO;

    public function update({$model} \${$modelVar}, {$name}DTO \$dto): {$name}ResponseDTO;

    public function delete({$model} \${$modelVar});
}

PHP;
    }

    private function stubRepositoryInterface(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $modelVar = $c['modelVar'];

        $uses = $this->buildUseBlock([
            $c['modelFqcn'],
            'Illuminate\\Contracts\\Pagination\\LengthAwarePaginator',
        ]);

        $ns = $c['repositoryContractNs'];

        return <<<PHP
<?php

namespace {$ns};
{$uses}
interface {$name}RepositoryInterface
{
    public function index(int \$perPage = 15, int \$page = 1, ?array \$filters = []): LengthAwarePaginator;

    public function show({$model} \${$modelVar}): {$model};

    public function store(array \$data): {$model};

    public function update({$model} \${$modelVar}, array \$data): {$model};

    public function delete({$model} \${$modelVar});
}

PHP;
    }

    private function stubDTO(array $c): string
    {
        $name = $c['baseName'];
        $ns = $c['dtoNs'];

        return <<<PHP
<?php

namespace {$ns};

class {$name}DTO
{
    public function __construct(
        // public readonly string \$exemplo,
    ) {}

    public static function fromRequest(array \$data): self
    {
        return new self(
            // exemplo: \$data['exemplo'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            // 'exemplo' => \$this->exemplo,
        ];
    }
}

PHP;
    }

    private function stubResponseDTO(array $c): string
    {
        $name = $c['baseName'];
        $model = $c['modelClass'];
        $ns = $c['dtoNs'];

        $uses = $this->buildUseBlock([$c['modelFqcn']]);

        return <<<PHP
<?php

namespace {$ns};
{$uses}
class {$name}ResponseDTO implements \JsonSerializable
{
    public function __construct(
        // public readonly int \$id,
        // public readonly string \$name,
    ) {}

    public static function fromModel({$model} \$model): self
    {
        return new self(
            // id: \$model->id,
            // name: \$model->name,
        );
    }

    public function jsonSerialize(): array
    {
        return \$this->toArray();
    }

    public function toArray(): array
    {
        return [
            // 'id'   => \$this->id,
            // 'name' => \$this->name,
        ];
    }
}

PHP;
    }

    private function stubFilterIndexRequest(array $c): string
    {
        $name = $c['baseName'];
        $ns = $c['requestNs'];

        return <<<PHP
<?php

namespace {$ns};

use Illuminate\Foundation\Http\FormRequest;

class Filter{$name}IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reorganiza a query antes da validação: tudo que não é perPage/page vai para 'filters'.
     * Ex: ?perPage=10&page=1&name=lucas&email=teste -> filters: ['name'=>'lucas','email'=>'teste']
     */
    protected function prepareForValidation(): void
    {
        \$reserved = ['perPage', 'page'];

        \$filters = collect(\$this->query())
            ->except(\$reserved)
            ->filter(fn (\$value) => \$value !== null && \$value !== '')
            ->all();

        \$perPage = \$this->query('perPage');
        \$page    = \$this->query('page');

        \$this->merge([
            'perPage' => (\$perPage === '' || \$perPage === null) ? 15 : (int) \$perPage,
            'page'    => (\$page === '' || \$page === null) ? 1 : (int) \$page,
            'filters' => \$filters,
        ]);
    }

    public function rules(): array
    {
        return [
            'perPage' => ['integer', 'min:1', 'max:100'],
            'page'    => ['integer', 'min:1'],
            'filters' => ['array'],
        ];
    }
}

PHP;
    }

    private function stubBasicRequest(array $c, string $className): string
    {
        $ns = $c['requestNs'];

        return <<<PHP
<?php

namespace {$ns};

use Illuminate\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function buildUseBlock(array $uses): string
    {
        $uses = array_values(array_unique(array_filter($uses)));
        if ($uses === []) {
            return "\n";
        }
        sort($uses);

        return "\n" . implode("\n", array_map(fn(string $u) => 'use ' . $u . ';', $uses)) . "\n";
    }
}

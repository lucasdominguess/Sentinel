<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeService extends Command
{
    protected $signature = 'make:service
        {name : Nome da classe, aceita subpastas (ex: User ou Admin/User)}
        {--r|repository : Gera Repository e RepositoryInterface com injeção automática no Service}
        {--b|bind : Registra os binds Interface→Implementação no AppServiceProvider}
        {--c|contract : Gera ServiceInterface (contrato) para o Service}
        {--d|dto : Importa DTO e ResponseDTO no Service e na Interface}
        {--m|methods : Adiciona métodos CRUD (index/find/store/update/delete) em todas as classes geradas}
        {--C|controller : Gera Controller com métodos CRUD e FilterRequest de index}
        {--i= : Injeta o Service no __construct de um Controller existente (ex: --i=AuthController)}
        {--f= : Caminho customizado para o Service, relativo a app/ (ex: --f=Domain/Auth)}
        {--all : Ativa todas as flags booleanas: --repository, --contract, --bind, --dto, --methods, --controller}';

    protected $description = 'Gera uma classe Service com suporte a contract, repository, DTO, métodos CRUD, bind e injeção em controller.';

    protected $help = <<<HELP
Exemplos de uso:

  Criar apenas o Service:
    php artisan make:service User

  Service + Interface (contrato):
    php artisan make:service User --contract

  Service + Repository + Bind:
    php artisan make:service User --repository --bind

  Service + Controller com CRUD:
    php artisan make:service User --controller --methods

  Tudo de uma vez (todas as flags booleanas):
    php artisan make:service User --all

  Tudo + injetar no Controller existente:
    php artisan make:service User --all --i=UserController

  Service em caminho customizado:
    php artisan make:service Admin/User --all --f=Domain/Admin
HELP;

    public function handle()
    {
        $name = trim((string) $this->argument('name'), '/\\');
        $isAll = (bool) $this->option('all');
        $shouldGenerateRepository = $isAll || (bool) $this->option('repository');
        $shouldBind = $isAll || (bool) $this->option('bind');
        $injectControllerName = trim((string) ($this->option('i') ?? ''), '/\\');
        $shouldGenerateContract = $isAll || (bool) $this->option('contract') || $shouldBind || $injectControllerName !== '';
        $shouldUseDto = $isAll || (bool) $this->option('dto');
        $shouldGenerateMethods = $isAll || (bool) $this->option('methods');
        $shouldGenerateController = $isAll || (bool) $this->option('controller');
        $customServicePath = trim((string) ($this->option('f') ?? ''));

        if ($name === '') {
            $this->error('Informe um nome válido para o service.');
            return Command::FAILURE;
        }

        $normalizedName = $this->normalizeName($name);
        $baseName = $this->extractBaseName($normalizedName);
        $relativeBaseName = $this->buildRelativeBaseName($normalizedName, $baseName);
        $subPath = dirname($relativeBaseName);

        [$serviceDirectory, $serviceBaseNamespace] = $this->resolvePathAndNamespace('Services', $customServicePath);
        [$serviceContractDirectory, $serviceContractBaseNamespace] = $this->resolvePathAndNamespace('Contracts/Services');
        [$repositoryDirectory, $repositoryBaseNamespace] = $this->resolvePathAndNamespace('Repositories');
        [$repositoryContractDirectory, $repositoryContractBaseNamespace] = $this->resolvePathAndNamespace('Contracts/Repositories');
        [$httpControllerDirectory, $httpControllerBaseNamespace] = $this->resolvePathAndNamespace('Http/Controllers');
        [$httpRequestDirectory, $httpRequestBaseNamespace] = $this->resolvePathAndNamespace('Http/Requests');

        $serviceRelativeClass = $relativeBaseName . 'Service';
        $serviceClassName = class_basename(str_replace('/', '\\', $serviceRelativeClass));
        $serviceNamespace = $this->appendNamespace($serviceBaseNamespace, $subPath);
        $servicePath = $serviceDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $serviceRelativeClass) . '.php';

        $serviceContractRelativeClass = $relativeBaseName . 'ServiceInterface';
        $serviceContractClassName = class_basename(str_replace('/', '\\', $serviceContractRelativeClass));
        $serviceContractNamespace = $this->appendNamespace($serviceContractBaseNamespace, $subPath);
        $serviceContractPath = $serviceContractDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $serviceContractRelativeClass) . '.php';

        $repositoryRelativeClass = $relativeBaseName . 'Repository';
        $repositoryClassName = class_basename(str_replace('/', '\\', $repositoryRelativeClass));
        $repositoryNamespace = $this->appendNamespace($repositoryBaseNamespace, $subPath);
        $repositoryPath = $repositoryDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $repositoryRelativeClass) . '.php';

        $repositoryContractRelativeClass = $relativeBaseName . 'RepositoryInterface';
        $repositoryContractClassName = class_basename(str_replace('/', '\\', $repositoryContractRelativeClass));
        $repositoryContractNamespace = $this->appendNamespace($repositoryContractBaseNamespace, $subPath);
        $repositoryContractPath = $repositoryContractDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $repositoryContractRelativeClass) . '.php';

        $generatedControllerRelativeClass = $relativeBaseName . 'Controller';
        $generatedControllerClassName = class_basename(str_replace('/', '\\', $generatedControllerRelativeClass));
        $generatedControllerNamespace = $this->appendNamespace($httpControllerBaseNamespace, $subPath);
        $generatedControllerPath = $httpControllerDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $generatedControllerRelativeClass) . '.php';

        $filterRequestClassName = 'Filter' . $baseName . 'IndexRequest';
        $filterRequestNamespace = $this->appendNamespace($httpRequestBaseNamespace, $subPath);
        $filterRequestSubDir = ($subPath !== '.' && $subPath !== '') ? str_replace('/', DIRECTORY_SEPARATOR, $subPath) . DIRECTORY_SEPARATOR : '';
        $filterRequestPath = $httpRequestDirectory . DIRECTORY_SEPARATOR . $filterRequestSubDir . $filterRequestClassName . '.php';

        $filesToCreate = [
            $servicePath => [
                'label' => 'service',
                'path' => $servicePath,
                'directory' => dirname($servicePath),
            ],
        ];

        if ($shouldGenerateContract) {
            $filesToCreate[$serviceContractPath] = [
                'label' => 'interface',
                'path' => $serviceContractPath,
                'directory' => dirname($serviceContractPath),
            ];
        }

        if ($shouldGenerateRepository) {
            $filesToCreate[$repositoryPath] = [
                'label' => 'repository',
                'path' => $repositoryPath,
                'directory' => dirname($repositoryPath),
            ];

            $filesToCreate[$repositoryContractPath] = [
                'label' => 'repository interface',
                'path' => $repositoryContractPath,
                'directory' => dirname($repositoryContractPath),
            ];
        }

        if ($shouldGenerateController) {
            $filesToCreate[$generatedControllerPath] = [
                'label' => 'controller',
                'path' => $generatedControllerPath,
                'directory' => dirname($generatedControllerPath),
            ];

            if ($shouldGenerateMethods) {
                $filesToCreate[$filterRequestPath] = [
                    'label' => 'filter request',
                    'path' => $filterRequestPath,
                    'directory' => dirname($filterRequestPath),
                ];
            }
        }

        foreach ($filesToCreate as $file) {
            if (File::exists($file['path'])) {
                $this->error('O arquivo [' . $file['path'] . '] já existe.');
                return Command::FAILURE;
            }
        }

        $injectControllerPath = null;
        $controllerDependencyClassName = $shouldGenerateContract ? $serviceContractClassName : $serviceClassName;
        $controllerDependencyNamespace = $shouldGenerateContract ? $serviceContractNamespace : $serviceNamespace;

        if ($injectControllerName !== '') {
            $injectControllerPath = $this->resolveControllerPath($injectControllerName);

            if ($injectControllerPath === null) {
                $this->error("Foram encontrados múltiplos controllers com o nome [{$injectControllerName}]. Informe o caminho completo, por exemplo: Admin/{$injectControllerName}");
                return Command::FAILURE;
            }

            if ($injectControllerPath === '' || !File::exists($injectControllerPath)) {
                $this->error("O controller [{$injectControllerName}] não foi encontrado em: {$injectControllerPath}");
                return Command::FAILURE;
            }
        }

        $paginatorUse = 'Illuminate\Contracts\Pagination\LengthAwarePaginator';

        $serviceUses = [];

        if ($shouldGenerateContract) {
            $serviceUses[] = $serviceContractNamespace . '\\' . $serviceContractClassName;
        }

        if ($shouldGenerateMethods) {
            $serviceUses[] = $paginatorUse;
        }

        $serviceConstructorDependencies = '        // dependências';

        if ($shouldGenerateRepository) {
            $serviceUses[] = $repositoryContractNamespace . '\\' . $repositoryContractClassName;
            $serviceConstructorDependencies = '        protected ' . $repositoryContractClassName . ' $repository,';
        }

        if ($shouldUseDto) {
            $dtoNamespace = $this->appendNamespace('App\\DTOs', $subPath);
            $serviceUses[] = $dtoNamespace . '\\' . $baseName . 'DTO';
            $serviceUses[] = $dtoNamespace . '\\' . $baseName . 'ResponseDTO';
        }

        $serviceContent = $this->getServiceStub(
            $serviceNamespace,
            $serviceClassName,
            $serviceUses,
            $shouldGenerateContract ? ' implements ' . $serviceContractClassName : '',
            $serviceConstructorDependencies,
            $shouldGenerateMethods ? $this->getCrudMethods($baseName, $shouldUseDto, false) : ''
        );

        $serviceContractContent = null;

        if ($shouldGenerateContract) {
            $contractUses = [];

            if ($shouldGenerateMethods) {
                $contractUses[] = $paginatorUse;
            }

            if ($shouldUseDto) {
                $dtoNamespace = $this->appendNamespace('App\\DTOs', $subPath);
                $contractUses[] = $dtoNamespace . '\\' . $baseName . 'DTO';
                $contractUses[] = $dtoNamespace . '\\' . $baseName . 'ResponseDTO';
            }

            $serviceContractContent = $this->getInterfaceStub(
                $serviceContractNamespace,
                $serviceContractClassName,
                $contractUses,
                $shouldGenerateMethods ? $this->getCrudMethods($baseName, $shouldUseDto, true) : ''
            );
        }

        $repositoryContent = null;
        $repositoryContractContent = null;

        if ($shouldGenerateRepository) {
            $repositoryUses = [$repositoryContractNamespace . '\\' . $repositoryContractClassName];

            if ($shouldGenerateMethods) {
                $repositoryUses[] = $paginatorUse;
            }

            $repositoryContent = $this->getRepositoryStub(
                $repositoryNamespace,
                $repositoryClassName,
                $repositoryUses,
                ' implements ' . $repositoryContractClassName,
                '        // dependências',
                $shouldGenerateMethods ? $this->getCrudMethods($baseName, false, false) : ''
            );

            $repositoryContractUses = [];

            if ($shouldGenerateMethods) {
                $repositoryContractUses[] = $paginatorUse;
            }

            $repositoryContractContent = $this->getInterfaceStub(
                $repositoryContractNamespace,
                $repositoryContractClassName,
                $repositoryContractUses,
                $shouldGenerateMethods ? $this->getCrudMethods($baseName, false, true) : ''
            );
        }

        $generatedControllerContent = null;
        $filterRequestContent = null;

        if ($shouldGenerateController) {
            $controllerServiceDep = $shouldGenerateContract ? $serviceContractClassName : $serviceClassName;
            $controllerServiceDepNamespace = $shouldGenerateContract ? $serviceContractNamespace : $serviceNamespace;

            $controllerUses = [
                'Illuminate\Http\JsonResponse',
                'Illuminate\Http\Request',
                'Illuminate\Routing\Controller',
                $controllerServiceDepNamespace . '\\' . $controllerServiceDep,
            ];

            $controllerMethods = '';

            if ($shouldGenerateMethods) {
                $controllerUses[] = $filterRequestNamespace . '\\' . $filterRequestClassName;
                $controllerMethods = $this->getControllerCrudMethods($filterRequestClassName);
            }

            $generatedControllerContent = $this->getControllerStub(
                $generatedControllerNamespace,
                $generatedControllerClassName,
                $controllerUses,
                '        protected ' . $controllerServiceDep . ' $service,',
                $controllerMethods
            );

            if ($shouldGenerateMethods) {
                $filterRequestContent = $this->getFilterRequestStub(
                    $filterRequestNamespace,
                    $filterRequestClassName
                );
            }
        }

        foreach ($filesToCreate as $file) {
            File::ensureDirectoryExists($file['directory']);
        }

        File::put($servicePath, $serviceContent);

        if ($shouldGenerateContract && $serviceContractContent !== null) {
            File::put($serviceContractPath, $serviceContractContent);
        }

        if ($shouldGenerateRepository && $repositoryContent !== null && $repositoryContractContent !== null) {
            File::put($repositoryContractPath, $repositoryContractContent);
            File::put($repositoryPath, $repositoryContent);
        }

        if ($shouldGenerateController && $generatedControllerContent !== null) {
            File::put($generatedControllerPath, $generatedControllerContent);

            if ($shouldGenerateMethods && $filterRequestContent !== null) {
                File::put($filterRequestPath, $filterRequestContent);
            }
        }

        if ($shouldBind) {
            $this->registerBind(
                app_path('Providers' . DIRECTORY_SEPARATOR . 'AppServiceProvider.php'),
                $serviceContractNamespace . '\\' . $serviceContractClassName,
                $serviceNamespace . '\\' . $serviceClassName
            );

            if ($shouldGenerateRepository) {
                $this->registerBind(
                    app_path('Providers' . DIRECTORY_SEPARATOR . 'AppServiceProvider.php'),
                    $repositoryContractNamespace . '\\' . $repositoryContractClassName,
                    $repositoryNamespace . '\\' . $repositoryClassName
                );
            }
        }

        if ($injectControllerPath !== null) {
            $this->injectDependencyIntoController(
                $injectControllerPath,
                $controllerDependencyNamespace . '\\' . $controllerDependencyClassName,
                $controllerDependencyClassName,
                '$service'
            );
        }

        $this->info("Service [{$serviceClassName}] criado com sucesso.");

        if ($shouldGenerateContract) {
            $this->info('Interface criada.');
        }

        if ($shouldGenerateRepository) {
            $this->info('Repository criado.');
            $this->info('Repository Interface criada.');
        }

        if ($shouldBind) {
            $this->info('Bind registrado.');
        }

        if ($shouldGenerateController) {
            $this->info("Controller [{$generatedControllerClassName}] criado.");

            if ($shouldGenerateMethods) {
                $this->info("FilterRequest [{$filterRequestClassName}] criado.");
            }
        }

        if ($injectControllerPath !== null) {
            $this->info('Service injetado no controller.');
        }

        return Command::SUCCESS;
    }

    private function normalizeName(string $name): string
    {
        return str_replace('\\', '/', trim($name, '/\\'));
    }

    private function extractBaseName(string $name): string
    {
        $baseName = class_basename(str_replace('/', '\\', $name));

        foreach (['ServiceInterface', 'RepositoryInterface', 'ResponseDTO', 'Service', 'Repository', 'DTO', 'Interface'] as $suffix) {
            if (Str::endsWith(strtolower($baseName), strtolower($suffix))) {
                return substr($baseName, 0, -strlen($suffix));
            }
        }

        return $baseName;
    }

    private function buildRelativeBaseName(string $name, string $baseName): string
    {
        $subPath = dirname($name);

        if ($subPath === '.') {
            return $baseName;
        }

        return $subPath . '/' . $baseName;
    }

    private function resolvePathAndNamespace(string $defaultRelativePath, string $customPath = ''): array
    {
        if ($customPath === '') {
            return [
                app_path(str_replace('/', DIRECTORY_SEPARATOR, $defaultRelativePath)),
                'App\\' . str_replace('/', '\\', $defaultRelativePath),
            ];
        }

        $normalizedCustomPath = str_replace('\\', '/', trim($customPath, '/\\'));
        $normalizedAppPath = str_replace('\\', '/', app_path());

        if (preg_match('/^[A-Za-z]:\//', $normalizedCustomPath) === 1) {
            $directory = str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath);
            $normalizedDirectory = strtolower(str_replace('\\', '/', $directory));
            $normalizedAppRoot = strtolower($normalizedAppPath);

            if ($normalizedDirectory !== $normalizedAppRoot && !Str::startsWith($normalizedDirectory, $normalizedAppRoot . '/')) {
                throw new \InvalidArgumentException('O caminho informado deve estar dentro do diretório app/.');
            }

            $relativePath = ltrim(substr(str_replace('\\', '/', $directory), strlen($normalizedAppPath)), '/');

            return [
                $directory,
                'App' . ($relativePath !== '' ? '\\' . str_replace('/', '\\', $relativePath) : ''),
            ];
        }

        if ($normalizedCustomPath === 'app' || Str::startsWith($normalizedCustomPath, 'app/')) {
            $relativePath = ltrim(Str::after($normalizedCustomPath, 'app'), '/');
            $directory = base_path(str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath));
        } else {
            $relativePath = $normalizedCustomPath;
            $directory = app_path(str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath));
        }

        return [
            $directory,
            'App' . ($relativePath !== '' ? '\\' . str_replace('/', '\\', $relativePath) : ''),
        ];
    }

    private function appendNamespace(string $baseNamespace, string $subPath): string
    {
        if ($subPath === '.' || $subPath === '') {
            return $baseNamespace;
        }

        return $baseNamespace . '\\' . str_replace('/', '\\', $subPath);
    }

    private function getServiceStub(
        string $namespace,
        string $className,
        array $uses,
        string $implementsClause,
        string $constructorDependencies,
        string $methods
    ): string {
        $useBlock = $this->buildUseBlock($uses);
        $methodsBlock = $methods !== '' ? "\n\n{$methods}" : '';

        return <<<PHP
<?php

namespace {$namespace};
{$useBlock}
class {$className}{$implementsClause}
{
    public function __construct(
{$constructorDependencies}
    ) {}
{$methodsBlock}
}

PHP;
    }

    private function getRepositoryStub(
        string $namespace,
        string $className,
        array $uses,
        string $implementsClause,
        string $constructorDependencies,
        string $methods
    ): string {
        $useBlock = $this->buildUseBlock($uses);
        $methodsBlock = $methods !== '' ? "\n\n{$methods}" : '';

        return <<<PHP
<?php

namespace {$namespace};
{$useBlock}
class {$className}{$implementsClause}
{
    public function __construct(
{$constructorDependencies}
    ) {}
{$methodsBlock}
}

PHP;
    }

    private function getControllerStub(
        string $namespace,
        string $className,
        array $uses,
        string $constructorDependencies,
        string $methods
    ): string {
        $useBlock = $this->buildUseBlock($uses);
        $methodsBlock = $methods !== '' ? "\n\n{$methods}" : '';

        return <<<PHP
<?php

namespace {$namespace};
{$useBlock}
class {$className} extends Controller
{
    public function __construct(
{$constructorDependencies}
    ) {}
{$methodsBlock}
}

PHP;
    }

    private function getInterfaceStub(string $namespace, string $className, array $uses, string $methods): string
    {
        $useBlock = $this->buildUseBlock($uses);
        $methodsBlock = $methods !== '' ? "\n{$methods}\n" : '';

        return <<<PHP
<?php

namespace {$namespace};
{$useBlock}
interface {$className}
{
{$methodsBlock}
}

PHP;
    }

    private function getFilterRequestStub(string $namespace, string $className): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
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

        return "\n" . implode("\n", array_map(fn (string $use) => 'use ' . $use . ';', $uses)) . "\n";
    }

    private function getCrudMethods(string $baseName, bool $shouldUseDto, bool $forInterface): string
    {
        if ($shouldUseDto) {
            if ($forInterface) {
                return implode("\n", [
                    '    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;',
                    '',
                    '    public function find(int $id): ' . $baseName . 'ResponseDTO;',
                    '',
                    '    public function store(' . $baseName . 'DTO $dto): ' . $baseName . 'ResponseDTO;',
                    '',
                    '    public function update(int $id, ' . $baseName . 'DTO $dto): ' . $baseName . 'ResponseDTO;',
                    '',
                    '    public function delete(int $id);',
                ]);
            }

            return implode("\n", [
                '    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator',
                '    {',
                '        // lógica',
                '    }',
                '',
                '    public function find(int $id): ' . $baseName . 'ResponseDTO',
                '    {',
                '        // lógica',
                '    }',
                '',
                '    public function store(' . $baseName . 'DTO $dto): ' . $baseName . 'ResponseDTO',
                '    {',
                '        // lógica',
                '    }',
                '',
                '    public function update(int $id, ' . $baseName . 'DTO $dto): ' . $baseName . 'ResponseDTO',
                '    {',
                '        // lógica',
                '    }',
                '',
                '    public function delete(int $id)',
                '    {',
                '        // lógica',
                '    }',
            ]);
        }

        if ($forInterface) {
            return implode("\n", [
                '    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;',
                '',
                '    public function find(int $id);',
                '',
                '    public function store(array $data);',
                '',
                '    public function update(int $id, array $data);',
                '',
                '    public function delete(int $id);',
            ]);
        }

        return implode("\n", [
            '    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function find(int $id)',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function store(array $data)',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function update(int $id, array $data)',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function delete(int $id)',
            '    {',
            '        // lógica',
            '    }',
        ]);
    }

    private function getControllerCrudMethods(string $filterRequestClassName): string
    {
        return implode("\n", [
            "    public function index({$filterRequestClassName} \$request): JsonResponse",
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function find(int $id): JsonResponse',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function store(Request $request): JsonResponse',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function update(int $id, Request $request): JsonResponse',
            '    {',
            '        // lógica',
            '    }',
            '',
            '    public function delete(int $id): JsonResponse',
            '    {',
            '        // lógica',
            '    }',
        ]);
    }

    private function resolveControllerPath(string $controllerName): ?string
    {
        $normalizedControllerName = str_replace('\\', '/', trim($controllerName, '/\\'));

        if (!Str::endsWith(strtolower($normalizedControllerName), 'controller')) {
            $normalizedControllerName .= 'Controller';
        }

        $directPath = app_path('Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedControllerName) . '.php');

        if (File::exists($directPath) || Str::contains($normalizedControllerName, '/')) {
            return $directPath;
        }

        $controllersDirectory = app_path('Http' . DIRECTORY_SEPARATOR . 'Controllers');
        $matches = [];

        foreach (File::allFiles($controllersDirectory) as $file) {
            if ($file->getFilename() !== $normalizedControllerName . '.php') {
                continue;
            }

            $matches[] = $file->getPathname();
        }

        if ($matches === []) {
            return $directPath;
        }

        if (count($matches) > 1) {
            return null;
        }

        return $matches[0];
    }

    private function registerBind(string $providerPath, string $interfaceNamespace, string $implementationNamespace): void
    {
        $content = File::get($providerPath);
        $interfaceClassName = class_basename($interfaceNamespace);
        $implementationClassName = class_basename($implementationNamespace);

        if (preg_match('/bind\(\s*' . preg_quote($interfaceClassName, '/') . '::class\s*,\s*' . preg_quote($implementationClassName, '/') . '::class\s*\)/s', $content) === 1) {
            return;
        }

        $content = $this->addUseStatement($content, $interfaceNamespace);
        $content = $this->addUseStatement($content, $implementationNamespace);

        $bindBlock = implode("\n", [
            '        $this->app->bind(',
            '            ' . $interfaceClassName . '::class,',
            '            ' . $implementationClassName . '::class',
            '        );',
        ]);

        $updatedContent = preg_replace(
            '/(public function register\(\): void\s*\{\R)(.*?)(^\s{4}\})/ms',
            '$1$2' . $bindBlock . "\n$3",
            $content,
            1,
            $count
        );

        if ($updatedContent === null || $count === 0) {
            throw new \RuntimeException('Não foi possível registrar o bind no AppServiceProvider.');
        }

        File::put($providerPath, $updatedContent);
    }

    private function injectDependencyIntoController(
        string $controllerPath,
        string $dependencyNamespace,
        string $dependencyClassName,
        string $variableName
    ): void {
        $content = File::get($controllerPath);

        if (Str::contains($content, $dependencyClassName . ' ' . $variableName)) {
            return;
        }

        $content = $this->addUseStatement($content, $dependencyNamespace);
        $parameterLine = '        protected ' . $dependencyClassName . ' ' . $variableName . ',';

        if (preg_match('/public function __construct\((.*?)\)\s*\{/s', $content, $matches) === 1) {
            $existingParameters = $matches[1];
            $newParameters = $this->appendConstructorParameter($existingParameters, $parameterLine);

            $updatedContent = preg_replace(
                '/public function __construct\((.*?)\)\s*\{/s',
                "public function __construct({$newParameters})\n    {",
                $content,
                1,
                $count
            );

            if ($updatedContent === null || $count === 0) {
                throw new \RuntimeException('Não foi possível atualizar o __construct do controller.');
            }

            File::put($controllerPath, $updatedContent);
            return;
        }

        $constructor = implode("\n", [
            '',
            '    public function __construct(',
            $parameterLine,
            '    ) {}',
            '',
        ]);

        $updatedContent = preg_replace('/(class\s+\w+[^{]*\{\R)/', '$1' . $constructor, $content, 1, $count);

        if ($updatedContent === null || $count === 0) {
            throw new \RuntimeException('Não foi possível criar o __construct no controller.');
        }

        File::put($controllerPath, $updatedContent);
    }

    private function appendConstructorParameter(string $existingParameters, string $parameterLine): string
    {
        $trimmedParameters = trim($existingParameters);

        if ($trimmedParameters === '' || preg_match('/^\/\/.*$/s', $trimmedParameters) === 1) {
            return "\n{$parameterLine}\n    ";
        }

        $normalizedParameters = rtrim($existingParameters);

        if (!Str::endsWith(rtrim($normalizedParameters), ',')) {
            $normalizedParameters = rtrim($normalizedParameters) . ',';
        }

        return $normalizedParameters . "\n{$parameterLine}\n    ";
    }

    private function addUseStatement(string $content, string $namespace): string
    {
        $useStatement = 'use ' . $namespace . ';';

        if (Str::contains($content, $useStatement)) {
            return $content;
        }

        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $lastUse = end($matches[0]);
            $insertPosition = $lastUse[1] + strlen($lastUse[0]);

            return substr($content, 0, $insertPosition) . "\n" . $useStatement . substr($content, $insertPosition);
        }

        if (preg_match('/namespace\s+[^;]+;/', $content, $match, PREG_OFFSET_CAPTURE) === 1) {
            $insertPosition = $match[0][1] + strlen($match[0][0]);

            return substr($content, 0, $insertPosition) . "\n\n" . $useStatement . substr($content, $insertPosition);
        }

        return $content;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDTO extends Command
{
    protected $signature = 'make:dto {name} {--r|response} {--A|array} {--N|no-suffix}';

    protected $description = 'Gera um DTO de entrada, array ou resposta.';

    public function handle(): int
    {
        $rawName = trim((string) $this->argument('name'), "/\\ ");
        $isResponse = (bool) $this->option('response');
        $isArray = (bool) $this->option('array');
        $noSuffix = (bool) $this->option('no-suffix');

        if ($rawName === '') {
            $this->error('Informe um nome válido para o DTO.');
            return Command::FAILURE;
        }

        if ($isResponse && $isArray) {
            $this->error('A flag -A (--array) serve apenas para dados de entrada e não pode ser usada junto com -r (--response).');
            return Command::FAILURE;
        }

        $segments = preg_split('#[\\\\/]#', $rawName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($segments === [] || array_filter($segments, fn (string $segment): bool => preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment) !== 1) !== []) {
            $this->error('Informe um nome válido para o DTO.');
            return Command::FAILURE;
        }

        $segments = array_map(static fn (string $segment): string => Str::studly($segment), $segments);
        $baseName = array_pop($segments);
        $className = $baseName;

        if (! $noSuffix) {
            if ($isResponse) {
                $baseName = preg_replace('/(?:ResponseDTO|Response|DTO)$/i', '', $baseName) ?: $baseName;
                $className = $baseName . 'ResponseDTO';
            } else {
                $baseName = preg_replace('/DTO$/i', '', $baseName) ?: $baseName;
                $className = $baseName . 'DTO';
            }
        }

        $subPath = implode(DIRECTORY_SEPARATOR, $segments);
        $namespace = 'App\\DTOs' . ($segments !== [] ? '\\' . implode('\\', $segments) : '');
        $directory = app_path('DTOs' . ($subPath !== '' ? DIRECTORY_SEPARATOR . $subPath : ''));
        $path = $directory . DIRECTORY_SEPARATOR . $className . '.php';

        if (File::exists($path)) {
            $this->error("O DTO [{$className}] já existe!");
            return Command::FAILURE;
        }

        if ($isResponse) {
            $factory = 'fromModel(Model $model)';
            $factoryBody = 'return new self($model->toArray());';
            $factoryName = 'fromModel';
            $factoryArgument = 'Model $model';
            $factoryValue = '$model->toArray()';
            $imports = "use Illuminate\\Database\\Eloquent\\Model;\n\n";
        } else {
            $factoryName = $isArray ? 'fromArray' : 'fromRequest';
            $factoryArgument = 'array $data';
            $factoryValue = '$data';
            $imports = '';
        }

        $content = <<<PHP
<?php

namespace {$namespace};

{$imports}class {$className}
{
    public function __construct(
        public readonly array \$data,
    ) {}

    public static function {$factoryName}({$factoryArgument}): self
    {
        return new self({$factoryValue});
    }

    public function toArray(): array
    {
        return \$this->data;
    }
}

PHP;

        File::ensureDirectoryExists($directory);
        File::put($path, $content);

        $this->info("DTO [{$namespace}\\{$className}] gerada com sucesso.");
        return Command::SUCCESS;
    }
}

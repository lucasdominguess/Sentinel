<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAbstract extends Command
{
    /**
     * Assinatura do comando no terminal.
     * * Argumentos:
     * {name}         -> Nome da classe (ex: Auth ou Admin/Auth)
     * * Opções (Flags):
     * {--N|no-suffix} -> Não adiciona automaticamente "AbstractController"
     * {--a=}          -> Define atributos do construtor (ex: "string $name, int $age")
     * {--f=}          -> Define um caminho customizado para salvar a abstract
     * 
     * 
     * exemplo = make:abstract Admin --a 'AdminServiceInterface $service'
     */

    

    protected $signature = 'make:abstract {name} {--N|no-suffix} {--a=} {--f=}';

    /**
     * Descrição do comando.
     */
    protected $description = 'Gera uma classe abstrata base para controllers com sufixo AbstractController.';

    public function handle()
    {
        $name = trim((string) $this->argument('name'), '/\\');
        $noSuffix = (bool) $this->option('no-suffix');
        $attributes = trim((string) ($this->option('a') ?? ''));
        $customPath = trim((string) ($this->option('f') ?? ''));

        if ($name === '') {
            $this->error('Informe um nome válido para a abstract.');
            return Command::FAILURE;
        }

        if (!$noSuffix) {
            if (Str::endsWith(strtolower($name), 'abstractcontroller')) {
                $name = substr($name, 0, -18) . 'AbstractController';
            } else {
                if (Str::endsWith(strtolower($name), 'controller')) {
                    $name = substr($name, 0, -10);
                }

                if (Str::endsWith(strtolower($name), 'abstract')) {
                    $name = substr($name, 0, -8);
                }

                $name .= 'AbstractController';
            }
        }

        $directory = app_path('Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Abstracts');
        $namespace = 'App\Http\Controllers\Abstracts';

        if ($customPath !== '') {
            $normalizedCustomPath = str_replace('\\', '/', trim($customPath, '/\\'));
            $normalizedAppPath = str_replace('\\', '/', app_path());

            if (preg_match('/^[A-Za-z]:\//', $normalizedCustomPath) === 1) {
                $directory = str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath);
                $normalizedDirectory = strtolower(str_replace('\\', '/', $directory));
                $normalizedAppRoot = strtolower($normalizedAppPath);

                if ($normalizedDirectory !== $normalizedAppRoot && !Str::startsWith($normalizedDirectory, $normalizedAppRoot . '/')) {
                    $this->error('O caminho informado em -f deve estar dentro do diretório app/.');
                    return Command::FAILURE;
                }

                $relativePath = ltrim(substr(str_replace('\\', '/', $directory), strlen($normalizedAppPath)), '/');
                $namespace = 'App' . ($relativePath !== '' ? '\\' . str_replace('/', '\\', $relativePath) : '');
            } else {
                if ($normalizedCustomPath === 'app' || Str::startsWith($normalizedCustomPath, 'app/')) {
                    $relativePath = ltrim(Str::after($normalizedCustomPath, 'app'), '/');
                    $directory = base_path(str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath));
                } else {
                    $relativePath = $normalizedCustomPath;
                    $directory = app_path(str_replace('/', DIRECTORY_SEPARATOR, $normalizedCustomPath));
                }

                $namespace = 'App' . ($relativePath !== '' ? '\\' . str_replace('/', '\\', $relativePath) : '');
            }
        }

        $cleanName = str_replace('\\', '/', $name);
        $path = $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanName) . '.php';
        $className = class_basename($name);

        $subNamespace = str_replace('/', '\\', dirname($cleanName));
        if ($subNamespace !== '.') {
            $namespace .= '\\' . $subNamespace;
        }

        if (File::exists($path)) {
            $this->error("A abstract [{$className}] já existe!");
            return Command::FAILURE;
        }

        $visibility = 'protected';

        if ($attributes !== '' && $this->input->isInteractive()) {
            $changeVisibility = $this->confirm('Deseja alterar o nível de acesso das propriedades?', false);

            if ($changeVisibility) {
                $selectedVisibility = $this->choice(
                    'Escolha o nível de acesso',
                    ['public', 'private', 'customizado'],
                    0
                );

                $visibility = match ($selectedVisibility) {
                    'public' => 'public',
                    'private' => 'private',
                    'customizado' => trim((string) $this->ask('Informe o valor customizado', 'protected')),
                    default => 'protected',
                };

                if ($visibility === '') {
                    $this->error('O nível de acesso customizado não pode ser vazio.');
                    return Command::FAILURE;
                }
            }
        }

        $constructorAttributes = '        // propriedades aqui';

        if ($attributes !== '') {
            $attributeList = array_values(array_filter(array_map('trim', explode(',', $attributes))));

            if ($attributeList === []) {
                $this->error('Informe atributos válidos na flag -a.');
                return Command::FAILURE;
            }

            $formattedAttributes = [];

            foreach ($attributeList as $attribute) {
                if (!Str::contains($attribute, '$')) {
                    $this->error("O atributo [{$attribute}] deve conter o nome da variável com $.");
                    return Command::FAILURE;
                }

                $attribute = preg_replace('/^(public|protected|private)\s+/i', '', $attribute) ?? $attribute;

                $formattedAttributes[] = "        {$visibility} {$attribute},";
            }

            $constructorAttributes = implode("\n", $formattedAttributes);
        }

        File::ensureDirectoryExists(dirname($path));

        $content = $this->getStub($namespace, $className, $constructorAttributes);

        File::put($path, $content);

        $this->info("Abstract [{$className}] criada com sucesso em: {$path}");

        return Command::SUCCESS;
    }

    private function getStub(string $namespace, string $className, string $constructorAttributes): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Routing\Controller;

abstract class {$className} extends Controller
{
    public function __construct(
{$constructorAttributes}
    ) {}
}

PHP;
    }
}

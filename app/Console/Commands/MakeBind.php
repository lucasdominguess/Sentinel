<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class MakeBind extends Command
{
    /**
     * Assinatura do comando no terminal.
     * * Argumentos:
     * {interface?} -> Nome da interface
     * {class?}     -> Nome da classe concreta
     * * Opções (Flags):
     * {--dry-run}  -> Apenas mostra os binds pendentes sem modificar arquivos
     * {--force}    -> Reescreve binds mesmo se já existirem
     * {--sort}     -> Ordena binds alfabeticamente
     */
    protected $signature = 'make:bind {interface?} {class?} {--dry-run} {--force} {--sort}';

    /**
     * Descrição do comando.
     */
    protected $description = 'Gerencia binds de interfaces e classes no AppServiceProvider.';

    public function handle()
    {
        $providerPath = app_path('Providers' . DIRECTORY_SEPARATOR . 'AppServiceProvider.php');
        $interfaceInput = trim((string) ($this->argument('interface') ?? ''));
        $classInput = trim((string) ($this->argument('class') ?? ''));
        $isDryRun = (bool) $this->option('dry-run');
        $shouldForce = (bool) $this->option('force');
        $shouldSort = (bool) $this->option('sort');

        if (!File::exists($providerPath)) {
            $this->error('AppServiceProvider não encontrado em: ' . $providerPath);
            return Command::FAILURE;
        }

        if (($interfaceInput === '') !== ($classInput === '')) {
            $this->error('Informe interface e classe juntas no modo manual.');
            return Command::FAILURE;
        }

        try {
            if ($interfaceInput !== '' && $classInput !== '') {
                return $this->handleManualMode($interfaceInput, $classInput, $providerPath, $isDryRun, $shouldForce, $shouldSort);
            }

            return $this->handleScannerMode($providerPath, $isDryRun, $shouldForce, $shouldSort);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function handleManualMode(
        string $interfaceInput,
        string $classInput,
        string $providerPath,
        bool $isDryRun,
        bool $shouldForce,
        bool $shouldSort
    ): int {
        $candidate = $this->buildManualCandidate($interfaceInput, $classInput);

        if ($candidate === null) {
            return Command::FAILURE;
        }

        $existingBinds = $this->parseExistingBinds($providerPath);
        $alreadyExists = isset($existingBinds[$candidate['interface_fqcn']]);

        if ($alreadyExists && !$shouldForce && !$shouldSort) {
            $this->info('O bind já existe:');
            $this->line($this->formatCandidateLabel($candidate));
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->info('Bind que seria adicionado:');
            $this->line('- ' . $this->formatCandidateLabel($candidate));
            return Command::SUCCESS;
        }

        $changes = $this->writeProviderBinds($providerPath, [$candidate], $shouldForce, $shouldSort);

        if ($changes === 0) {
            $this->info('Nenhuma alteração foi necessária.');
            return Command::SUCCESS;
        }

        $this->info('Bind registrado com sucesso:');
        $this->line($this->formatCandidateLabel($candidate));

        return Command::SUCCESS;
    }

    private function handleScannerMode(string $providerPath, bool $isDryRun, bool $shouldForce, bool $shouldSort): int
    {
        if (!$this->input->isInteractive() && !$isDryRun) {
            $this->error('Sem argumentos, use o modo interativo ou execute com --dry-run.');
            return Command::FAILURE;
        }

        if ($this->input->isInteractive() && !$isDryRun) {
            $shouldScan = $this->confirm('Deseja verificar binds pendentes automaticamente?', true);

            if (!$shouldScan) {
                $this->info('Nenhum bind foi processado.');
                return Command::SUCCESS;
            }
        }

        $candidates = $this->scanBindCandidates($providerPath, $shouldForce);

        if ($candidates === []) {
            if ($shouldSort && !$isDryRun) {
                $changes = $this->writeProviderBinds($providerPath, [], $shouldForce, true);

                if ($changes > 0) {
                    $this->info('Binds ordenados com sucesso.');
                    return Command::SUCCESS;
                }
            }

            $this->info($shouldForce ? 'Nenhum bind válido foi encontrado para reescrita.' : 'Nenhum bind pendente foi encontrado.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->displayCandidates($candidates, $shouldForce ? 'Binds que seriam escritos:' : 'Binds que seriam adicionados:');
            return Command::SUCCESS;
        }

        $selectedCandidates = $candidates;

        if ($this->input->isInteractive()) {
            $this->displayCandidates($candidates, $shouldForce ? 'Binds detectados:' : 'Binds pendentes encontrados:');
            $selectedCandidates = $this->promptCandidateSelection($candidates);

            if ($selectedCandidates === []) {
                $this->info('Nenhum bind selecionado.');
                return Command::SUCCESS;
            }
        }

        $changes = $this->writeProviderBinds($providerPath, $selectedCandidates, $shouldForce, $shouldSort);

        if ($changes === 0) {
            $this->info('Nenhuma alteração foi necessária.');
            return Command::SUCCESS;
        }

        if (count($selectedCandidates) === 1) {
            $this->info('Bind registrado com sucesso:');
            $this->line($this->formatCandidateLabel($selectedCandidates[0]));
            return Command::SUCCESS;
        }

        $this->info(count($selectedCandidates) . ' binds adicionados com sucesso.');
        return Command::SUCCESS;
    }

    private function buildManualCandidate(string $interfaceInput, string $classInput): ?array
    {
        $interfaceReference = $this->resolveReference($interfaceInput, $this->interfaceRoots(), true);
        $classReference = $this->resolveReference($classInput, $this->classRoots(), false);

        if ($interfaceReference === null) {
            $this->error('Interface não encontrada: ' . $interfaceInput);
            return null;
        }

        if ($classReference === null) {
            $this->error('Classe concreta não encontrada: ' . $classInput);
            return null;
        }

        return $this->createCandidate($interfaceReference, $classReference);
    }

    private function scanBindCandidates(string $providerPath, bool $includeExisting): array
    {
        $existingBinds = $this->parseExistingBinds($providerPath);
        $candidates = [];

        foreach ($this->scannerGroups() as $group) {
            foreach ($group['interface_dirs'] as $interfaceDirectory) {
                if (!File::isDirectory($interfaceDirectory)) {
                    continue;
                }

                foreach (File::allFiles($interfaceDirectory) as $interfaceFile) {
                    $interfacePath = $interfaceFile->getPathname();

                    if (!Str::endsWith($interfacePath, 'Interface.php')) {
                        continue;
                    }

                    $relativeInterface = str_replace('\\', '/', $interfaceFile->getRelativePathname());
                    $relativeClass = preg_replace('/Interface\.php$/', '.php', $relativeInterface);

                    if ($relativeClass === null) {
                        continue;
                    }

                    $classPath = $group['class_dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeClass);

                    if (!File::exists($classPath)) {
                        continue;
                    }

                    $interfaceReference = $this->referenceFromAppFile($interfacePath);
                    $classReference = $this->referenceFromAppFile($classPath);

                    if ($interfaceReference === null || $classReference === null) {
                        continue;
                    }

                    $candidate = $this->createCandidate($interfaceReference, $classReference);

                    if (!$includeExisting && isset($existingBinds[$candidate['interface_fqcn']])) {
                        continue;
                    }

                    $candidates[$candidate['interface_fqcn']] = $candidate;
                }
            }
        }

        ksort($candidates);

        return array_values($candidates);
    }

    private function parseExistingBinds(string $providerPath): array
    {
        $content = File::get($providerPath);
        $useMap = $this->parseUseMap($content);
        $registerBody = $this->extractRegisterBody($content);
        $bindings = [];

        foreach ($this->findBindMatches($registerBody) as $match) {
            $interfaceFqcn = $this->resolveImportedName($match['interface_name'], $useMap);
            $classFqcn = $this->resolveImportedName($match['class_name'], $useMap);

            $bindings[$interfaceFqcn] = [
                'interface_name' => class_basename($interfaceFqcn),
                'interface_fqcn' => $interfaceFqcn,
                'class_name' => class_basename($classFqcn),
                'class_fqcn' => $classFqcn,
            ];
        }

        return $bindings;
    }

    private function writeProviderBinds(string $providerPath, array $candidates, bool $shouldForce, bool $shouldSort): int
    {
        $content = File::get($providerPath);
        $registerBody = $this->extractRegisterBody($content);
        $existingBinds = $this->parseExistingBinds($providerPath);
        $mergedBinds = $this->mergeBinds($existingBinds, $candidates, $shouldForce, $shouldSort);
        $updatedContent = $this->addUseStatements($content, $this->collectNamespacesForUses($mergedBinds));
        $cleanBody = $this->removeExistingBindStatements($this->extractRegisterBody($updatedContent));
        $newRegisterBody = $this->composeRegisterBody($cleanBody, $mergedBinds, $shouldSort);
        $updatedContent = $this->replaceRegisterBody($updatedContent, $newRegisterBody);

        if ($updatedContent === $content && $registerBody === $newRegisterBody) {
            return 0;
        }

        File::put($providerPath, $updatedContent);
        return count($candidates) > 0 ? count($candidates) : 1;
    }

    private function mergeBinds(array $existingBinds, array $candidates, bool $shouldForce, bool $shouldSort): array
    {
        $merged = $existingBinds;
        $order = array_keys($existingBinds);

        foreach ($candidates as $candidate) {
            $key = $candidate['interface_fqcn'];

            if (!isset($merged[$key])) {
                $order[] = $key;
                $merged[$key] = $candidate;
                continue;
            }

            if ($shouldForce) {
                $merged[$key] = $candidate;
            }
        }

        $bindings = [];

        foreach ($order as $key) {
            if (isset($merged[$key])) {
                $bindings[] = $merged[$key];
            }
        }

        if ($shouldSort) {
            usort($bindings, fn (array $left, array $right) => strcmp($left['interface_name'], $right['interface_name']));
        }

        return $bindings;
    }

    private function extractRegisterBody(string $content): string
    {
        if (preg_match('/public function register\(\): void\s*\{(?P<body>.*?)(^\s{4}\})/ms', $content, $matches) !== 1) {
            throw new RuntimeException('Não foi possível localizar o método register() no AppServiceProvider.');
        }

        return $matches['body'];
    }

    private function replaceRegisterBody(string $content, string $newBody): string
    {
        $updatedContent = preg_replace(
            '/(public function register\(\): void\s*\{)(?P<body>.*?)(^\s{4}\})/ms',
            '$1' . $newBody . '$3',
            $content,
            1,
            $count
        );

        if ($updatedContent === null || $count === 0) {
            throw new RuntimeException('Não foi possível atualizar o método register() no AppServiceProvider.');
        }

        return $updatedContent;
    }

    private function removeExistingBindStatements(string $registerBody): string
    {
        $matches = $this->findBindMatches($registerBody);

        if ($matches === []) {
            return rtrim($registerBody, "\r\n");
        }

        usort($matches, fn (array $left, array $right) => $right['offset'] <=> $left['offset']);

        foreach ($matches as $match) {
            $registerBody = substr($registerBody, 0, $match['remove_start']) . substr($registerBody, $match['remove_end']);
        }

        return rtrim($registerBody, "\r\n");
    }

    private function composeRegisterBody(string $cleanBody, array $bindings, bool $shouldSort): string
    {
        $parts = [];
        $trimmedBody = trim($cleanBody, "\r\n");

        if ($trimmedBody !== '') {
            $parts[] = $trimmedBody;
        }

        if ($bindings !== []) {
            if ($shouldSort) {
                usort($bindings, fn (array $left, array $right) => strcmp($left['interface_name'], $right['interface_name']));
            }

            $parts[] = implode("\n", array_map(fn (array $binding) => $this->buildBindBlock($binding), $bindings));
        }

        if ($parts === []) {
            return "\n";
        }

        return "\n" . implode("\n\n", $parts) . "\n";
    }

    private function buildBindBlock(array $binding): string
    {
        return implode("\n", [
            '        $this->app->bind(',
            '            ' . $binding['interface_name'] . '::class,',
            '            ' . $binding['class_name'] . '::class',
            '        );',
        ]);
    }

    private function collectNamespacesForUses(array $bindings): array
    {
        $uses = [];

        foreach ($bindings as $binding) {
            $uses[] = $binding['interface_fqcn'];
            $uses[] = $binding['class_fqcn'];
        }

        return array_values(array_unique($uses));
    }

    private function addUseStatements(string $content, array $namespaces): string
    {
        $missingNamespaces = [];

        foreach ($namespaces as $namespace) {
            $useStatement = 'use ' . $namespace . ';';

            if (!Str::contains($content, $useStatement)) {
                $missingNamespaces[] = $namespace;
            }
        }

        if ($missingNamespaces === []) {
            return $content;
        }

        sort($missingNamespaces);

        foreach ($missingNamespaces as $namespace) {
            $useStatement = 'use ' . $namespace . ';';

            if (preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
                $lastUse = end($matches[0]);
                $insertPosition = $lastUse[1] + strlen($lastUse[0]);
                $content = substr($content, 0, $insertPosition) . "\n" . $useStatement . substr($content, $insertPosition);
                continue;
            }

            if (preg_match('/namespace\s+[^;]+;/', $content, $match, PREG_OFFSET_CAPTURE) === 1) {
                $insertPosition = $match[0][1] + strlen($match[0][0]);
                $content = substr($content, 0, $insertPosition) . "\n\n" . $useStatement . substr($content, $insertPosition);
            }
        }

        return $content;
    }

    private function resolveImportedName(string $name, array $useMap): string
    {
        if (Str::contains($name, '\\')) {
            return ltrim($name, '\\');
        }

        return $useMap[$name] ?? $name;
    }

    private function parseUseMap(string $content): array
    {
        $useMap = [];
        $matches = [];

        preg_match_all('/^use\s+([^;]+);$/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fqcn = trim($match[1]);
            $useMap[class_basename($fqcn)] = $fqcn;
        }

        return $useMap;
    }

    private function findBindMatches(string $registerBody): array
    {
        $matches = [];
        $rawMatches = [];

        preg_match_all(
            '/\$this->app->bind\(\s*([A-Za-z0-9_\\\\]+)::class\s*,\s*([A-Za-z0-9_\\\\]+)::class\s*\);/s',
            $registerBody,
            $rawMatches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        foreach ($rawMatches as $match) {
            $offset = $match[0][1];
            $lineStart = strrpos(substr($registerBody, 0, $offset), "\n");
            $lineStart = $lineStart === false ? 0 : $lineStart + 1;
            $linePrefix = substr($registerBody, $lineStart, $offset - $lineStart);

            if (Str::contains($linePrefix, '//')) {
                continue;
            }

            $removeStart = $lineStart;
            $removeEnd = $offset + strlen($match[0][0]);
            $bodyLength = strlen($registerBody);

            while ($removeEnd < $bodyLength && ($registerBody[$removeEnd] === " " || $registerBody[$removeEnd] === "\t" || $registerBody[$removeEnd] === "\r")) {
                $removeEnd++;
            }

            if ($removeEnd < $bodyLength && $registerBody[$removeEnd] === "\n") {
                $removeEnd++;
            }

            $matches[] = [
                'full_match' => $match[0][0],
                'offset' => $offset,
                'length' => strlen($match[0][0]),
                'remove_start' => $removeStart,
                'remove_end' => $removeEnd,
                'interface_name' => $match[1][0],
                'class_name' => $match[2][0],
            ];
        }

        return $matches;
    }

    private function displayCandidates(array $candidates, string $title): void
    {
        $this->info($title);
        $this->newLine();

        foreach ($candidates as $index => $candidate) {
            $this->line('[' . ($index + 1) . '] ' . $this->formatCandidateLabel($candidate));
        }

        $this->newLine();
    }

    private function promptCandidateSelection(array $candidates): array
    {
        $input = trim((string) $this->ask('Selecione os itens (ex: all, 1 3, all --except=2,5)', 'all'));
        $selectedIndexes = $this->parseSelectionInput($input, count($candidates));
        $selectedCandidates = [];

        foreach ($selectedIndexes as $index) {
            $selectedCandidates[] = $candidates[$index - 1];
        }

        return $selectedCandidates;
    }

    private function parseSelectionInput(string $input, int $totalItems): array
    {
        $normalizedInput = trim($input);

        if ($normalizedInput === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $normalizedInput) ?: [];
        $allSelected = false;
        $included = [];
        $excluded = [];

        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];

            if ($token === 'all') {
                $allSelected = true;
                continue;
            }

            if (Str::startsWith($token, '--except=')) {
                $values = array_filter(explode(',', Str::after($token, '--except=')));

                foreach ($values as $value) {
                    if (!ctype_digit($value)) {
                        throw new RuntimeException('Seleção inválida: ' . $token);
                    }

                    $excluded[] = (int) $value;
                }

                continue;
            }

            if ($token === '-n') {
                if (isset($tokens[$index + 1]) && ctype_digit($tokens[$index + 1])) {
                    $excluded[] = (int) $tokens[$index + 1];
                    $index++;
                    continue;
                }

                continue;
            }

            if (ctype_digit($token)) {
                if (isset($tokens[$index + 1]) && $tokens[$index + 1] === '-n') {
                    $excluded[] = (int) $token;
                    $index++;
                    continue;
                }

                $included[] = (int) $token;
                continue;
            }

            throw new RuntimeException('Seleção inválida: ' . $token);
        }

        $selected = $allSelected ? range(1, $totalItems) : $included;
        $selected = array_values(array_unique(array_diff($selected, $excluded)));

        foreach ($selected as $item) {
            if ($item < 1 || $item > $totalItems) {
                throw new RuntimeException('Seleção fora do intervalo: ' . $item);
            }
        }

        sort($selected);

        return $selected;
    }

    private function formatCandidateLabel(array $candidate): string
    {
        return $candidate['interface_name'] . ' -> ' . $candidate['class_name'];
    }

    private function createCandidate(array $interfaceReference, array $classReference): array
    {
        return [
            'interface_name' => $interfaceReference['name'],
            'interface_fqcn' => $interfaceReference['fqcn'],
            'class_name' => $classReference['name'],
            'class_fqcn' => $classReference['fqcn'],
        ];
    }

    private function resolveReference(string $input, array $roots, bool $mustBeInterface): ?array
    {
        $normalizedInput = trim(str_replace('\\', '/', $input), '/');
        $normalizedInput = preg_replace('/^App\//i', '', $normalizedInput) ?? $normalizedInput;
        $matches = [];

        foreach ($roots as $root) {
            if (!File::isDirectory($root)) {
                continue;
            }

            foreach (File::allFiles($root) as $file) {
                $reference = $this->referenceFromAppFile($file->getPathname());

                if ($reference === null) {
                    continue;
                }

                if ($mustBeInterface && !Str::endsWith($reference['name'], 'Interface')) {
                    continue;
                }

                if (!$mustBeInterface && Str::endsWith($reference['name'], 'Interface')) {
                    continue;
                }

                $comparisonValues = [
                    strtolower($reference['name']),
                    strtolower(str_replace('\\', '/', $reference['fqcn'])),
                    strtolower($reference['app_relative']),
                    strtolower(pathinfo($reference['app_relative'], PATHINFO_FILENAME)),
                ];

                if (in_array(strtolower($normalizedInput), $comparisonValues, true)) {
                    $matches[] = $reference;
                }
            }
        }

        if ($matches === []) {
            return null;
        }

        if (count($matches) > 1) {
            throw new RuntimeException('Referência ambígua: ' . $input);
        }

        return $matches[0];
    }

    private function referenceFromAppFile(string $filePath): ?array
    {
        if (!Str::endsWith($filePath, '.php')) {
            return null;
        }

        $appPath = str_replace('\\', '/', app_path());
        $normalizedPath = str_replace('\\', '/', $filePath);

        if (!Str::startsWith($normalizedPath, $appPath . '/')) {
            return null;
        }

        $relativePath = substr($normalizedPath, strlen($appPath) + 1);
        $classPath = preg_replace('/\.php$/', '', $relativePath);

        if ($classPath === null) {
            return null;
        }

        return [
            'name' => class_basename(str_replace('/', '\\', $classPath)),
            'fqcn' => 'App\\' . str_replace('/', '\\', $classPath),
            'app_relative' => $classPath,
            'path' => $filePath,
        ];
    }

    private function interfaceRoots(): array
    {
        return [
            app_path('Interfaces'),
            app_path('Contracts' . DIRECTORY_SEPARATOR . 'Services'),
            app_path('Contracts' . DIRECTORY_SEPARATOR . 'Repositories'),
            app_path('Services' . DIRECTORY_SEPARATOR . 'Contracts'),
            app_path('Repositories' . DIRECTORY_SEPARATOR . 'Contracts'),
        ];
    }

    private function classRoots(): array
    {
        return [
            app_path('Services'),
            app_path('Repositories'),
        ];
    }

    private function scannerGroups(): array
    {
        return [
            [
                'interface_dirs' => [
                    app_path('Services' . DIRECTORY_SEPARATOR . 'Contracts'),
                    app_path('Contracts' . DIRECTORY_SEPARATOR . 'Services'),
                ],
                'class_dir' => app_path('Services'),
            ],
            [
                'interface_dirs' => [
                    app_path('Repositories' . DIRECTORY_SEPARATOR . 'Contracts'),
                    app_path('Contracts' . DIRECTORY_SEPARATOR . 'Repositories'),
                ],
                'class_dir' => app_path('Repositories'),
            ],
        ];
    }
}
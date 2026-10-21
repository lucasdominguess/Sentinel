<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class SyncContract extends Command
{
    /**
     * Assinatura do comando no terminal.
     * * Argumentos:
     * {class?} -> Nome da classe concreta
     * * Opções (Flags):
     * {--check}         -> Apenas verifica diferenças sem alterar arquivos
     * {--force}         -> Reescreve completamente a interface
     * {--only-missing}  -> Adiciona apenas métodos faltantes
     * {--sort}          -> Ordena métodos alfabeticamente
     * {--dry-run}       -> Exibe resultado sem salvar
     */
    protected $signature = 'sync:contract {class?} {--check} {--force} {--only-missing} {--sort} {--dry-run}';

    /**
     * Descrição do comando.
     */
    protected $description = 'Sincroniza métodos públicos de Services e Repositories com seus Contracts.';

    public function handle()
    {
        $classInput = trim((string) ($this->argument('class') ?? ''));
        $isCheck = (bool) $this->option('check');
        $shouldForce = (bool) $this->option('force');
        $shouldOnlyMissing = (bool) $this->option('only-missing');
        $shouldSort = (bool) $this->option('sort');
        $isDryRun = (bool) $this->option('dry-run');

        if ($shouldForce && $shouldOnlyMissing) {
            $this->error('As flags --force e --only-missing não podem ser usadas juntas.');
            return Command::FAILURE;
        }

        try {
            if ($classInput !== '') {
                return $this->handleManualMode($classInput, $isCheck, $shouldForce, $shouldOnlyMissing, $shouldSort, $isDryRun);
            }

            return $this->handleAutomaticMode($isCheck, $shouldForce, $shouldOnlyMissing, $shouldSort, $isDryRun);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function handleManualMode(
        string $classInput,
        bool $isCheck,
        bool $shouldForce,
        bool $shouldOnlyMissing,
        bool $shouldSort,
        bool $isDryRun
    ): int {
        $target = $this->resolveTargetByInput($classInput);

        if ($target === null) {
            $this->error('Classe não encontrada: ' . $classInput);
            return Command::FAILURE;
        }

        $result = $this->synchronizeTarget($target, $isCheck, $shouldForce, $shouldOnlyMissing, $shouldSort, $isDryRun);

        return $this->renderResults([$result], $isCheck, $isDryRun);
    }

    private function handleAutomaticMode(
        bool $isCheck,
        bool $shouldForce,
        bool $shouldOnlyMissing,
        bool $shouldSort,
        bool $isDryRun
    ): int {
        if ($this->input->isInteractive()) {
            $shouldScan = $this->confirm('Deseja verificar todos os Services e Repositories automaticamente?', true);

            if (!$shouldScan) {
                $this->info('Nenhum contrato foi processado.');
                return Command::SUCCESS;
            }
        }

        $targets = $this->scanTargets();

        if ($targets === []) {
            $this->info('Nenhum contrato correspondente foi encontrado para sincronização.');
            return Command::SUCCESS;
        }

        $results = [];

        foreach ($targets as $target) {
            $results[] = $this->synchronizeTarget($target, $isCheck, $shouldForce, $shouldOnlyMissing, $shouldSort, $isDryRun);
        }

        return $this->renderResults($results, $isCheck, $isDryRun);
    }

    private function synchronizeTarget(
        array $target,
        bool $isCheck,
        bool $shouldForce,
        bool $shouldOnlyMissing,
        bool $shouldSort,
        bool $isDryRun
    ): array {
        $classInfo = $this->analyzePhpFile($target['class_path'], 'class', $target['class_name'], $target['class_namespace']);
        $interfaceInfo = $this->analyzePhpFile($target['interface_path'], 'interface', $target['interface_name'], $target['interface_namespace']);

        $classMethods = $classInfo['methods'];
        $interfaceMethods = $interfaceInfo['methods'];

        $missingMethods = [];
        $changedMethods = [];
        $extraMethods = [];

        foreach ($classMethods as $methodName => $signature) {
            if (!isset($interfaceMethods[$methodName])) {
                $missingMethods[$methodName] = $signature;
                continue;
            }

            if ($interfaceMethods[$methodName] !== $signature) {
                $changedMethods[$methodName] = $signature;
            }
        }

        foreach ($interfaceMethods as $methodName => $signature) {
            if (!isset($classMethods[$methodName])) {
                $extraMethods[$methodName] = $signature;
            }
        }

        if ($shouldOnlyMissing) {
            $finalMethods = $interfaceMethods;

            foreach ($missingMethods as $methodName => $signature) {
                $finalMethods[$methodName] = $signature;
            }
        } else {
            $finalMethods = $classMethods;
        }

        if ($shouldSort) {
            ksort($finalMethods);
        }

        $finalContent = $this->buildInterfaceContent(
            $interfaceInfo['namespace'],
            $interfaceInfo['declaration_name'],
            $this->mergeUseStatements($classInfo['uses'], $interfaceInfo['uses']),
            array_values($finalMethods)
        );

        $hasDifferences = $missingMethods !== [] || $changedMethods !== [] || (!$shouldOnlyMissing && $extraMethods !== []);
        $wasWritten = false;

        if (!$isCheck && !$isDryRun && ($shouldForce || $interfaceInfo['content'] !== $finalContent)) {
            File::put($target['interface_path'], $finalContent);
            $wasWritten = true;
        }

        return [
            'target' => $target,
            'missing_methods' => $missingMethods,
            'changed_methods' => $changedMethods,
            'extra_methods' => $extraMethods,
            'has_differences' => $hasDifferences,
            'was_written' => $wasWritten,
            'final_content' => $finalContent,
        ];
    }

    private function renderResults(array $results, bool $isCheck, bool $isDryRun): int
    {
        if ($isCheck) {
            return $this->renderCheckResults($results);
        }

        if ($isDryRun) {
            return $this->renderDryRunResults($results);
        }

        $writtenResults = array_values(array_filter($results, fn (array $result) => $result['was_written']));

        if ($writtenResults === []) {
            $this->info('Nenhuma alteração foi necessária.');
            return Command::SUCCESS;
        }

        if (count($writtenResults) === 1) {
            $target = $writtenResults[0]['target'];
            $this->info('Contrato sincronizado com sucesso:');
            $this->line($target['class_name'] . ' -> ' . $target['interface_name']);
            return Command::SUCCESS;
        }

        $this->info(count($writtenResults) . ' contratos sincronizados com sucesso.');
        return Command::SUCCESS;
    }

    private function renderCheckResults(array $results): int
    {
        $reported = false;

        foreach ($results as $result) {
            if (!$result['has_differences']) {
                continue;
            }

            $reported = true;
            $target = $result['target'];
            $this->info($target['class_name'] . ' -> ' . $target['interface_name']);

            if ($result['missing_methods'] !== []) {
                $this->line('Métodos faltando na interface:');

                foreach ($result['missing_methods'] as $signature) {
                    $this->line('- ' . trim($signature));
                }
            }

            if ($result['changed_methods'] !== []) {
                $this->line('Métodos com assinatura diferente:');

                foreach ($result['changed_methods'] as $signature) {
                    $this->line('- ' . trim($signature));
                }
            }

            if ($result['extra_methods'] !== []) {
                $this->line('Métodos extras na interface:');

                foreach ($result['extra_methods'] as $signature) {
                    $this->line('- ' . trim($signature));
                }
            }

            $this->newLine();
        }

        if (!$reported) {
            $this->info('Nenhuma diferença foi encontrada.');
        }

        return Command::SUCCESS;
    }

    private function renderDryRunResults(array $results): int
    {
        $printed = false;

        foreach ($results as $result) {
            if (!$result['has_differences']) {
                continue;
            }

            $printed = true;
            $target = $result['target'];
            $this->info('Conteúdo final da interface: ' . $target['interface_name']);
            $this->line($result['final_content']);
            $this->newLine();
        }

        if (!$printed) {
            $this->info('Nenhuma alteração seria necessária.');
        }

        return Command::SUCCESS;
    }

    private function resolveTargetByInput(string $classInput): ?array
    {
        $classReference = $this->resolveClassReference($classInput);

        if ($classReference === null) {
            return null;
        }

        $target = $this->buildTargetFromClassReference($classReference);

        if ($target === null) {
            throw new RuntimeException('Interface correspondente não encontrada para ' . $classReference['name'] . '.');
        }

        return $target;
    }

    private function scanTargets(): array
    {
        $targets = [];

        foreach ($this->classGroups() as $group) {
            if (!File::isDirectory($group['class_root'])) {
                continue;
            }

            foreach (File::allFiles($group['class_root']) as $file) {
                if (!$this->isSupportedConcreteClass($file->getFilename())) {
                    continue;
                }

                $classReference = $this->referenceFromRoot($file->getPathname(), $group['class_root'], $group['kind']);

                if ($classReference === null) {
                    continue;
                }

                $target = $this->buildTargetFromClassReference($classReference);

                if ($target === null) {
                    continue;
                }

                $targets[$target['class_fqcn']] = $target;
            }
        }

        ksort($targets);

        return array_values($targets);
    }

    private function resolveClassReference(string $input): ?array
    {
        $normalizedInput = trim(str_replace('\\', '/', $input), '/');
        $normalizedInput = preg_replace('/^App\//i', '', $normalizedInput) ?? $normalizedInput;
        $matches = [];

        foreach ($this->classGroups() as $group) {
            if (!File::isDirectory($group['class_root'])) {
                continue;
            }

            foreach (File::allFiles($group['class_root']) as $file) {
                if (!$this->isSupportedConcreteClass($file->getFilename())) {
                    continue;
                }

                $reference = $this->referenceFromRoot($file->getPathname(), $group['class_root'], $group['kind']);

                if ($reference === null) {
                    continue;
                }

                $comparisonValues = [
                    strtolower($reference['name']),
                    strtolower($reference['fqcn']),
                    strtolower(str_replace('\\', '/', $reference['relative_class'])),
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
            throw new RuntimeException('Classe ambígua: ' . $input . '. Informe o caminho completo.');
        }

        return $matches[0];
    }

    private function buildTargetFromClassReference(array $classReference): ?array
    {
        $interfaceCandidates = [];

        foreach ($classReference['interface_roots'] as $interfaceRoot) {
            $interfacePath = $interfaceRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $classReference['relative_subpath'] . $classReference['base_name'] . 'Interface.php');

            if (File::exists($interfacePath)) {
                $interfaceCandidates[] = $interfacePath;
            }
        }

        if ($interfaceCandidates === []) {
            return null;
        }

        if (count($interfaceCandidates) > 1) {
            throw new RuntimeException('Múltiplas interfaces encontradas para ' . $classReference['name'] . '.');
        }

        $interfacePath = $interfaceCandidates[0];
        $interfaceNamespace = $this->namespaceFromPath($interfacePath);
        $interfaceName = pathinfo($interfacePath, PATHINFO_FILENAME);

        return [
            'kind' => $classReference['kind'],
            'class_name' => $classReference['name'],
            'class_fqcn' => $classReference['fqcn'],
            'class_namespace' => $classReference['namespace'],
            'class_path' => $classReference['path'],
            'interface_name' => $interfaceName,
            'interface_namespace' => $interfaceNamespace,
            'interface_path' => $interfacePath,
        ];
    }

    private function analyzePhpFile(string $path, string $expectedKind, string $expectedName, string $expectedNamespace): array
    {
        if (!File::exists($path)) {
            throw new RuntimeException('Arquivo não encontrado: ' . $path);
        }

        $content = File::get($path);
        $header = $this->parseHeader($content);
        $declaration = $this->parseDeclaration($content);

        if ($declaration === null) {
            throw new RuntimeException('Declaração PHP inválida em: ' . $path);
        }

        if ($declaration['kind'] !== $expectedKind) {
            throw new RuntimeException('Declaração inesperada em: ' . $path);
        }

        if ($declaration['name'] !== $expectedName) {
            throw new RuntimeException('Nome da declaração não confere em: ' . $path);
        }

        if ($header['namespace'] !== $expectedNamespace) {
            throw new RuntimeException('Namespace inválido em: ' . $path);
        }

        $body = $this->extractDeclarationBody($content, $declaration['body_open_index']);

        return [
            'content' => $content,
            'namespace' => $header['namespace'],
            'uses' => $header['uses'],
            'declaration_name' => $declaration['name'],
            'methods' => $this->extractPublicMethods($body),
        ];
    }

    private function parseHeader(string $content): array
    {
        $declaration = $this->parseDeclaration($content);
        $headerContent = $declaration === null ? $content : substr($content, 0, $declaration['offset']);
        $namespace = '';
        $uses = [];

        if (preg_match('/namespace\s+([^;]+);/', $headerContent, $namespaceMatch) === 1) {
            $namespace = trim($namespaceMatch[1]);
        }

        if (preg_match_all('/^use\s+([^;]+);$/m', $headerContent, $useMatches, PREG_SET_ORDER) > 0) {
            foreach ($useMatches as $useMatch) {
                $uses[] = trim($useMatch[1]);
            }
        }

        return [
            'namespace' => $namespace,
            'uses' => array_values(array_unique($uses)),
        ];
    }

    private function parseDeclaration(string $content): ?array
    {
        $matches = [];

        if (preg_match('/\b(class|interface)\s+([A-Za-z_][A-Za-z0-9_]*)\b[^{]*\{/s', $content, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $fullMatch = $matches[0][0];
        $offset = $matches[0][1];
        $bodyOpenIndex = $offset + strrpos($fullMatch, '{');

        return [
            'kind' => $matches[1][0],
            'name' => $matches[2][0],
            'offset' => $offset,
            'body_open_index' => $bodyOpenIndex,
        ];
    }

    private function extractDeclarationBody(string $content, int $bodyOpenIndex): string
    {
        $bodyCloseIndex = $this->findMatchingBrace($content, $bodyOpenIndex);

        if ($bodyCloseIndex === null) {
            throw new RuntimeException('Não foi possível localizar o fechamento da declaração PHP.');
        }

        return substr($content, $bodyOpenIndex + 1, $bodyCloseIndex - $bodyOpenIndex - 1);
    }

    private function extractPublicMethods(string $body): array
    {
        $cleanBody = $this->stripCommentLines($body);
        $matches = [];
        $methods = [];

        preg_match_all('/\bpublic\s+(static\s+)?function\s+(&\s*)?([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $cleanBody, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $methodName = $match[3][0];

            if ($methodName === '__construct' || Str::startsWith($methodName, '__')) {
                continue;
            }

            $fullMatch = $match[0][0];
            $offset = $match[0][1];
            $openParenthesisIndex = $offset + strrpos($fullMatch, '(');
            $closeParenthesisIndex = $this->findMatchingParenthesis($body, $openParenthesisIndex);

            if ($closeParenthesisIndex === null) {
                continue;
            }

            $afterParametersIndex = $closeParenthesisIndex + 1;
            $methodEndIndex = $this->findMethodSignatureEnd($body, $afterParametersIndex);

            if ($methodEndIndex === null) {
                continue;
            }

            $parameterBlock = substr($body, $openParenthesisIndex + 1, $closeParenthesisIndex - $openParenthesisIndex - 1);
            $returnType = $this->extractReturnType($body, $afterParametersIndex, $methodEndIndex);
            $isStatic = trim((string) $match[1][0]) !== '';
            $isReference = trim((string) $match[2][0]) !== '';

            $methods[$methodName] = $this->buildMethodSignature($methodName, $parameterBlock, $returnType, $isStatic, $isReference);
        }

        return $methods;
    }

    private function buildMethodSignature(
        string $methodName,
        string $parameterBlock,
        string $returnType,
        bool $isStatic,
        bool $isReference
    ): string {
        $parameters = $this->formatParameters($parameterBlock);
        $signature = '    public ' . ($isStatic ? 'static ' : '') . 'function ' . ($isReference ? '&' : '') . $methodName . $parameters;

        if ($returnType !== '') {
            $signature .= ': ' . $this->normalizeInlineWhitespace($returnType);
        }

        return $signature . ';';
    }

    private function formatParameters(string $parameterBlock): string
    {
        $trimmed = trim($parameterBlock);

        if ($trimmed === '') {
            return '()';
        }

        if (!Str::contains($parameterBlock, "\n")) {
            return '(' . $this->normalizeInlineWhitespace($trimmed) . ')';
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $parameterBlock) ?: []), fn (string $line) => $line !== ''));

        return "(\n        " . implode("\n        ", $lines) . "\n    )";
    }

    private function extractReturnType(string $body, int $startIndex, int $endIndex): string
    {
        $segment = trim(substr($body, $startIndex, $endIndex - $startIndex));

        if ($segment === '' || !Str::startsWith($segment, ':')) {
            return '';
        }

        return trim(substr($segment, 1));
    }

    private function findMethodSignatureEnd(string $body, int $startIndex): ?int
    {
        $length = strlen($body);

        for ($index = $startIndex; $index < $length; $index++) {
            $character = $body[$index];

            if ($character === '{' || $character === ';') {
                return $index;
            }
        }

        return null;
    }

    private function findMatchingParenthesis(string $content, int $openIndex): ?int
    {
        return $this->findMatchingDelimiter($content, $openIndex, '(', ')');
    }

    private function findMatchingBrace(string $content, int $openIndex): ?int
    {
        return $this->findMatchingDelimiter($content, $openIndex, '{', '}');
    }

    private function findMatchingDelimiter(string $content, int $openIndex, string $openDelimiter, string $closeDelimiter): ?int
    {
        $depth = 0;
        $length = strlen($content);
        $stringDelimiter = null;

        for ($index = $openIndex; $index < $length; $index++) {
            $character = $content[$index];

            if ($stringDelimiter !== null) {
                if ($character === '\\') {
                    $index++;
                    continue;
                }

                if ($character === $stringDelimiter) {
                    $stringDelimiter = null;
                }

                continue;
            }

            if ($character === '"' || $character === '\'') {
                $stringDelimiter = $character;
                continue;
            }

            if ($character === $openDelimiter) {
                $depth++;
                continue;
            }

            if ($character === $closeDelimiter) {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function stripCommentLines(string $content): string
    {
        $content = preg_replace('/\/\*.*?\*\//s', '', $content) ?? $content;
        $content = preg_replace('/^[ \t]*\/\/.*$/m', '', $content) ?? $content;
        $content = preg_replace('/^[ \t]*#.*$/m', '', $content) ?? $content;

        return $content;
    }

    private function buildInterfaceContent(string $namespace, string $interfaceName, array $uses, array $methodSignatures): string
    {
        $useBlock = $this->buildUseBlock($uses);
        $methodsBlock = $methodSignatures === [] ? "\n" : "\n" . implode("\n\n", $methodSignatures) . "\n";

        return <<<PHP
<?php

namespace {$namespace};

{$useBlock}interface {$interfaceName}
{{$methodsBlock}}

PHP;
    }

    private function buildUseBlock(array $uses): string
    {
        $uses = array_values(array_unique(array_filter($uses)));

        if ($uses === []) {
            return '';
        }

        sort($uses);

        return implode("\n", array_map(fn (string $use) => 'use ' . $use . ';', $uses)) . "\n\n";
    }

    private function mergeUseStatements(array $classUses, array $interfaceUses): array
    {
        return array_values(array_unique(array_merge($classUses, $interfaceUses)));
    }

    private function normalizeInlineWhitespace(string $value): string
    {
        return preg_replace('/[ \t\r\n]+/', ' ', trim($value)) ?? trim($value);
    }

    private function isSupportedConcreteClass(string $filename): bool
    {
        return Str::endsWith($filename, 'Service.php') || Str::endsWith($filename, 'Repository.php');
    }

    private function referenceFromRoot(string $path, string $root, string $kind): ?array
    {
        if (!Str::endsWith($path, '.php')) {
            return null;
        }

        $normalizedRoot = str_replace('\\', '/', $root);
        $normalizedPath = str_replace('\\', '/', $path);

        if (!Str::startsWith($normalizedPath, $normalizedRoot . '/')) {
            return null;
        }

        $relativePath = substr($normalizedPath, strlen($normalizedRoot) + 1);
        $classPath = preg_replace('/\.php$/', '', $relativePath);

        if ($classPath === null) {
            return null;
        }

        $baseName = class_basename(str_replace('/', '\\', $classPath));
        $relativeSubpath = dirname($classPath);
        $relativeSubpath = $relativeSubpath === '.' ? '' : str_replace('\\', '/', $relativeSubpath) . '/';
        $namespace = $this->namespaceFromPath($path);

        return [
            'kind' => $kind,
            'name' => $baseName,
            'base_name' => $this->baseContractName($baseName),
            'fqcn' => $namespace . '\\' . $baseName,
            'namespace' => $namespace,
            'path' => $path,
            'relative_class' => $classPath,
            'relative_subpath' => $relativeSubpath,
            'interface_roots' => $this->interfaceRootsForKind($kind),
        ];
    }

    private function namespaceFromPath(string $path): string
    {
        $normalizedAppPath = str_replace('\\', '/', app_path());
        $normalizedPath = str_replace('\\', '/', $path);
        $relativePath = substr($normalizedPath, strlen($normalizedAppPath) + 1);
        $directory = dirname($relativePath);

        if ($directory === '.' || $directory === '') {
            return 'App';
        }

        return 'App\\' . str_replace('/', '\\', $directory);
    }

    private function baseContractName(string $className): string
    {
        if (Str::endsWith($className, 'Service')) {
            return substr($className, 0, -7) . 'Service';
        }

        if (Str::endsWith($className, 'Repository')) {
            return substr($className, 0, -10) . 'Repository';
        }

        return $className;
    }

    private function classGroups(): array
    {
        return [
            [
                'kind' => 'service',
                'class_root' => app_path('Services'),
            ],
            [
                'kind' => 'repository',
                'class_root' => app_path('Repositories'),
            ],
        ];
    }

    private function interfaceRootsForKind(string $kind): array
    {
        return $kind === 'service'
            ? [
                app_path('Services' . DIRECTORY_SEPARATOR . 'Contracts'),
                app_path('Contracts' . DIRECTORY_SEPARATOR . 'Services'),
            ]
            : [
                app_path('Repositories' . DIRECTORY_SEPARATOR . 'Contracts'),
                app_path('Contracts' . DIRECTORY_SEPARATOR . 'Repositories'),
            ];
    }
}
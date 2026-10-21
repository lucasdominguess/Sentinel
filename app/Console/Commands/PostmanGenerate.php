<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class PostmanGenerate extends Command
{
    protected $signature = 'postman:generate';
    protected $description = 'Gera collection do Postman com rotas da API organizadas em pastas';

    public function handle()
    {
        $this->info('Iniciando geração da collection...');

        // ?? Nome
        $useCustomName = $this->confirm('Deseja definir um nome personalizado?');
        $collectionName = $useCustomName
            ? $this->ask('Digite o nome da collection')
            : config('app.name');

        // ?? Auth
        $useAuth = $this->confirm('A API usa Bearer Token?');

        // ?? Swagger
        $includeSwagger = $this->confirm('Deseja incluir rotas do Swagger?');

        // ?? Base URL
        $host = rtrim($this->ask('Digite o host', config('app.url') ?? 'http://localhost'), '/');
        $port = $this->ask('Digite a porta', '8000');
        $baseUrl = preg_match('/^https?:\/\//', $host) ? "$host:$port" : "http://$host:$port";

        $routes = Route::getRoutes();

        $groups = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $routeName = $route->getName() ?? '';

            $isApiRoute = $uri === 'api' || str_starts_with($uri, 'api/');
            $isSwaggerRoute = str_contains($uri, 'swagger')
                || str_contains($uri, 'docs')
                || str_contains($uri, 'documentation')
                || str_contains($uri, 'oauth2-callback')
                || str_contains($routeName, 'swagger');

            if (!$isApiRoute && !($includeSwagger && $isSwaggerRoute)) {
                continue;
            }

            if (str_starts_with($uri, '_')) {
                continue;
            }

            $segments = explode('/', $uri);

            if ($segments[0] === 'api') {
                array_shift($segments);
            }

            if (isset($segments[0]) && preg_match('/^v[0-9]+$/', $segments[0])) {
                array_shift($segments);
            }

            $folder = $segments[0] ?? 'outros';

            $method = collect($route->methods())
                ->reject(fn ($method) => in_array($method, ['HEAD', 'OPTIONS'], true))
                ->first() ?: collect($route->methods())->first();

            $title = end($segments) ?: $uri;

            $request = [
                'name' => strtoupper($method) . ' ' . $title,
                'request' => [
                    'method' => $method,
                    'header' => [],
                    'url' => [
                        'raw' => '{{base_url}}/' . $uri,
                        'host' => ['{{base_url}}'],
                        'path' => explode('/', $uri),
                    ],
                ],
            ];

            if ($useAuth && $isApiRoute) {
                $request['request']['auth'] = [
                    'type' => 'bearer',
                    'bearer' => [
                        [
                            'key' => 'token',
                            'value' => '{{token}}',
                            'type' => 'string',
                        ],
                    ],
                ];
            }

            if ($useAuth && str_contains(strtolower($uri), 'login')) {
                $request['event'][] = [
                    'listen' => 'test',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            'const token = pm.response.headers.get("Authorization") || pm.response.json().token;',
                            '',
                            'if (token) {',
                            '    pm.environment.set("token", token);',
                            '}',
                        ],
                    ],
                ];
            }

            $groups[$folder][] = $request;
        }

        $items = [];

        foreach ($groups as $folder => $requests) {
            $items[] = [
                'name' => ucfirst($folder),
                'item' => $requests,
            ];
        }

        $collection = [
            'info' => [
                'name' => $collectionName,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $items,
            'variable' => [
                ['key' => 'base_url', 'value' => $baseUrl],
                ['key' => 'token', 'value' => ''],
            ],
        ];

        $path = storage_path('app/postman_collection.json');

        file_put_contents($path, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Collection gerada com sucesso em: $path");
    }
}


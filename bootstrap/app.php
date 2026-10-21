<?php

use App\Exceptions\Handlers\DatabaseExceptionHandler;
use App\Exceptions\Siga\SigaException;
use App\Helpers\ApiResponse;
use App\Http\Middleware\XssCleanMiddleware;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(XssCleanMiddleware::class);
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception): JsonResponse {
            $erro = $exception->validator->errors()->all();

            Log::error($exception->getMessage());
            return response()->json([
                'message' => 'Dados inválidos',
                'error' => $erro,
            ], 422);
        });
        $exceptions->render(function (LazyLoadingViolationException $exception, Request $request) {
            Log::error("Lazy Loading Violation: " . $exception->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Query N+1 detectada! Faltou o eager loading (carregamento prévio) na consulta.',
                    'developer_hint' => sprintf(
                        "Você deve usar ->with('%s') para carregar esta relação no model %s antes de acessá-la.",
                        $exception->relation,
                        class_basename($exception->model)
                    ),
                    'error_details' => "A relação [{$exception->relation}] não foi carregada. Lazy loading está desativado.",
                ], 500);
            }
        });
        $exceptions->render(function (QueryException $exception, Request $request) {
            return DatabaseExceptionHandler::handle($exception, $request);
        });
        $exceptions->render(function (SigaException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return;
            }

            Log::error('SigaException: ' . $exception->getMessage());

            $status = $exception->getStatusCode();

            // Erros de infraestrutura (5xx) recebem mensagem genérica em produção.
            $message = ($status >= 500 && app()->environment('production'))
                ? 'Serviço do SIGA indisponível no momento. Tente novamente mais tarde.'
                : $exception->getMessage();

            return ApiResponse::error($message, $status);
        });
        $exceptions->render(function (\Throwable $exception): JsonResponse {
            Log::error("Final Exception: " . $exception->getMessage());
            // LOG::channel('telegram')->error("Final Exception: ".$exception->getMessage());
    
            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;
            return response()->json([
                'message' => 'Ocorreu um erro interno no servidor. Tente novamente mais tarde.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], $status);
        });
    })->create();

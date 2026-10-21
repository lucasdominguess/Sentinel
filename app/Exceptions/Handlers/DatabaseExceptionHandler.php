<?php

namespace App\Exceptions\Handlers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DatabaseExceptionHandler
{
    /**
     * Analisa e trata exceções do PostgreSQL de forma centralizada.
     */
    public static function handle(QueryException $exception, Request $request): JsonResponse|false
    {
        Log::error("Database Error (Postgres): " . $exception->getMessage());

        if (!$request->expectsJson()) {
            return false;
        }

        
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();

        return match ($sqlState) {
            '23505' => response()->json([
                'message' => 'Registro duplicado. Já existe um dado com essas informações cadastrado no sistema.',
                'code' => 'unique_violation'
            ], 409),
            
            '23503' => response()->json([
                'message' => 'Violação de vínculo. Você está tentando referenciar um registro que não existe ou excluir um dado que está em uso por outras partes do sistema.',
                'code' => 'foreign_key_violation'
            ], 422), 
            
            '23502' => response()->json([
                'message' => 'Falha de integridade: Um campo obrigatório não foi preenchido ou foi enviado como nulo.',
                'code' => 'not_null_violation'
            ], 422),

            '22001' => response()->json([
                'message' => 'O valor enviado excede o limite de caracteres permitido para o campo.',
                'code' => 'string_data_right_truncation'
            ], 422),
            
            '08006', '08001', '08004' => response()->json([
                'message' => 'O banco de dados está indisponível no momento. Nossa equipe já foi notificada.',
                'code' => 'connection_exception'
            ], 500),
            
            default => response()->json([
                'message' => 'Ocorreu um erro interno no banco de dados.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
                'sql_state' => config('app.debug') ? $sqlState : null,
            ], 500),
        };
    }
}
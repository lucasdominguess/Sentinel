<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Resposta de sucesso genérica
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operação realizada com sucesso',
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode, $headers);
    }

    /**
     * Resposta de erro genérica
     */
    public static function error(
        string $message = 'Ocorreu um erro',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Resposta de recurso criado
     */
    public static function created(
        mixed $data = null,
        string $message = 'Recurso criado com sucesso'
    ): JsonResponse {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Resposta de recurso não encontrado
     */
    public static function notFound(
        string $message = 'Recurso não encontrado'
    ): JsonResponse {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Resposta de não autorizado
     */
    public static function unauthorized(
        string $message = 'Acesso não autorizado'
    ): JsonResponse {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Resposta de acesso proibido
     */
    public static function forbidden(
        string $message = 'Acesso proibido'
    ): JsonResponse {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Resposta de validação
     */
    public static function validationError(
        mixed $errors,
        string $message = 'Erro de validação'
    ): JsonResponse {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Resposta de erro interno do servidor
     */
    public static function serverError(
        string $message = 'Erro interno do servidor'
    ): JsonResponse {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Resposta sem conteúdo
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Resposta paginada
     */
    public static function paginated(
        mixed $data,
        string $message = 'Operação realizada com sucesso'
    ): JsonResponse {
        return self::success([
            'items' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ], $message);
    }
}

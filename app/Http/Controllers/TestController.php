<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;


class TestController extends Controller
{
    public function index()
    {

        $resposta = Http::withHeaders([
            'x-api-key' => env('ANTHROPIC_API_KEY'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-opus-5-5',
                    'max_tokens' => 1024,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Explique o que é injeção de dependência em uma frase.'],
                    ],
                ]);

        // Se a API respondeu com erro (4xx/5xx), mostra a mensagem real
        // em vez de devolver null silenciosamente.
        if ($resposta->failed()) {
            return ApiResponse::error(
                $resposta->json('error.message') ?? 'Erro desconhecido ao chamar a API da Anthropic',
                $resposta->status(),
            );
        }

        // O texto da resposta fica em content[0].text
        $texto = $resposta->json('content.0.text');

        return ApiResponse::success($texto, 'Teste de integração com a API da Anthropic realizado com sucesso');
    }
}

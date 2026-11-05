<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function Test2()
    {


        $resposta = Http::withHeaders([
            'x-goog-api-key' => env('GEMINI_API_KEY'),
            'content-type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent', [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Explique o que é a linguagem php em uma frase.'],
                            ],
                        ],
                    ],
                    // Os modelos Gemini 3.x "pensam" antes de responder e gastam tokens nisso.
                    // Sem folga aqui a resposta volta vazia com finishReason = MAX_TOKENS.
                    'generationConfig' => [
                        'maxOutputTokens' => 800,
                    ],
                ]);

        // Mesma boa prática: nunca engolir o erro em silêncio
        if ($resposta->failed()) {
            return ApiResponse::error(
                $resposta->json('error.message') ?? 'Erro ao chamar a API do Gemini',
                $resposta->status(),
            );
        }

        // No Gemini o texto fica em candidates[0].content.parts[0].text
        $texto = $resposta->json('candidates.0.content.parts.0.text');

        // Se o modelo parou por limite de tokens antes de escrever a resposta,
        // candidates[0].content.parts vem vazio -> texto null. Avisa em vez de retornar null mudo.
        if ($texto === null) {
            return ApiResponse::error(
                'A API respondeu, mas sem texto (finishReason: '
                    . $resposta->json('candidates.0.finishReason', 'desconhecido')
                    . '). Aumente maxOutputTokens.',
                422,
            );
        }

        return ApiResponse::success($texto, 'Teste de integração com o Gemini realizado com sucesso');
    }
}

<?php

namespace App\Traits;

trait NormalizarTexto
{
    public static function normalize(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        // Remove acentos e cedilha
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);

        // Remove qualquer coisa que não seja letra ou espaço
        $value = preg_replace('/[^A-Z a-z]/', '', $value);

        // Remove espaços duplicados
        $value = preg_replace('/\s+/', ' ', $value);

        return mb_strtoupper(trim($value));
    }

    // Normaliza o ticker para o formato canônico (maiúsculo, sem espaços) e
    // garante o sufixo ".SA" da B3 quando não há sufixo de bolsa.
    // Ex.: " vale3 " -> "VALE3.SA"; "VGIR11.SA" -> "VGIR11.SA" (mantém).
    public static function normalizeTicker(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $ticker = mb_strtoupper(preg_replace('/\s+/', '', trim($value)));

        // Sem ponto = sem sufixo de bolsa → assume B3 e adiciona .SA
        if ($ticker !== '' && !str_contains($ticker, '.')) {
            $ticker .= '.SA';
        }

        return $ticker;
    }
}

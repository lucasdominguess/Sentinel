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
}

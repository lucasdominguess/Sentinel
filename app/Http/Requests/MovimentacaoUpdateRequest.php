<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AporteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acao_id' => ['sometimes', 'integer', 'exists:acoes,id'],
            'preco_pago' => ['sometimes', 'numeric', 'gt:0'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'data' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }
}

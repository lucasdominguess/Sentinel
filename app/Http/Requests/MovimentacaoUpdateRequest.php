<?php

namespace App\Http\Requests;

use App\Enums\TipoMovimentacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentacaoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acao_id' => ['sometimes', 'integer', 'exists:acoes,id'],
            'tipo' => ['sometimes', Rule::enum(TipoMovimentacao::class)],
            'preco' => ['sometimes', 'numeric', 'gt:0'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'data' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.enum' => 'Tipo de movimentação inválido.',
            'preco.gt' => 'O preço deve ser maior que zero.',
            'quantidade.min' => 'A quantidade deve ser de ao menos 1 cota.',
            'data.before_or_equal' => 'A data da movimentação não pode ser futura.',
        ];
    }
}

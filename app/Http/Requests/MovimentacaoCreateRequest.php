<?php

namespace App\Http\Requests;

use App\Enums\TipoMovimentacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MovimentacaoCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Normaliza ANTES de validar: sem data informada, assume a data atual; sem tipo, compra.
    protected function prepareForValidation(): void
    {
        $data = $this->input('data');

        $this->merge([
            'data' => ($data === null || $data === '') ? Carbon::today()->toDateString() : $data,
            'tipo' => $this->input('tipo') ?: TipoMovimentacao::COMPRA->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'acao_id' => ['required', 'integer', 'exists:acoes,id'],
            'tipo' => ['required', Rule::enum(TipoMovimentacao::class)],
            'preco' => ['required', 'numeric', 'gt:0'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'acao_id.required' => 'Selecione o ativo.',
            'acao_id.exists' => 'Ativo inválido.',
            'tipo.enum' => 'Tipo de movimentação inválido.',
            'preco.gt' => 'O preço deve ser maior que zero.',
            'quantidade.min' => 'A quantidade deve ser de ao menos 1 cota.',
            'data.before_or_equal' => 'A data da movimentação não pode ser futura.',
        ];
    }
}

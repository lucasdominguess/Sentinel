<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AporteCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Normaliza ANTES de validar: sem data informada, assume a data atual.
    protected function prepareForValidation(): void
    {
        $data = $this->input('data');

        $this->merge([
            'data' => ($data === null || $data === '') ? Carbon::today()->toDateString() : $data,
        ]);
    }

    public function rules(): array
    {
        return [
            'acao_id' => ['required', 'integer', 'exists:acoes,id'],
            'preco_pago' => ['required', 'numeric', 'gt:0'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'data' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'acao_id.required' => 'Selecione o ativo.',
            'acao_id.exists' => 'Ativo inválido.',
            'preco_pago.gt' => 'O preço pago deve ser maior que zero.',
            'quantidade.min' => 'A quantidade deve ser de ao menos 1 cota.',
            'data.before_or_equal' => 'A data da compra não pode ser futura.',
        ];
    }
}

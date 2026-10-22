<?php

namespace App\Http\Requests;

use App\Enums\TipoAtivo;
use App\Traits\NormalizarTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdicionarTicketRequest extends FormRequest
{
    use NormalizarTexto;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // normaliza ANTES de validar → unique roda sobre o valor canônico
        $this->merge([
            'ticker' => self::normalizeTicker($this->input('ticker')),
        ]);
    }

    public function rules(): array
    {
        return [
            'ticker' => ['required', 'string', 'max:20', 'unique:acoes,ticker'],
            'tipo' => ['required', Rule::enum(TipoAtivo::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'ticker.required' => 'Informe o ticker (ex.: VALE3.SA).',
            'ticker.unique' => 'Esse ticker já está cadastrado.',
            'tipo.required' => 'Selecione o tipo do ativo.',
        ];
    }
}

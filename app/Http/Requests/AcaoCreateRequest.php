<?php

namespace App\Http\Requests;

use App\Enums\TipoAtivo;
use App\Traits\NormalizarTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcaoCreateRequest extends FormRequest
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
            'nome' => ['nullable', 'string', 'max:255'],
            'setor' => ['nullable', 'string', 'max:255'],
            'segmento' => ['nullable', 'string', 'max:255'],
            'moeda' => ['nullable', 'string', 'size:3'],
            'tipo' => ['required', Rule::enum(TipoAtivo::class)],
            'ativo' => ['boolean'],
        ];
    }
}

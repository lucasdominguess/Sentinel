<?php

namespace App\Http\Requests;

use App\Enums\TipoAtivo;
use App\Traits\NormalizarTexto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcaoUpdateRequest extends FormRequest
{
    use NormalizarTexto;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ticker')) {
            $this->merge([
                'ticker' => self::normalizeTicker($this->input('ticker')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'ticker' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('acoes', 'ticker')->ignore($this->route('acao'))],
            'nome' => ['nullable', 'string', 'max:255'],
            'setor' => ['nullable', 'string', 'max:255'],
            'segmento' => ['nullable', 'string', 'max:255'],
            'moeda' => ['nullable', 'string', 'size:3'],
            'tipo' => ['sometimes', Rule::enum(TipoAtivo::class)],
            'ativo' => ['boolean'],
        ];
    }
}

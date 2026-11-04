<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Meus ativos em uma data: sem `data` (ou com hoje) é a posição ao vivo
class CarteiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.date_format' => 'Data inválida.',
            'data.before_or_equal' => 'A data não pode ser futura.',
        ];
    }
}

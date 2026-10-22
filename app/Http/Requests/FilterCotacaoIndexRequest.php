<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterCotacaoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $perPage = $this->query('perPage');
        $page = $this->query('page');

        $this->merge([
            'perPage' => ($perPage === '' || $perPage === null) ? 90 : (int) $perPage,
            'page' => ($page === '' || $page === null) ? 1 : (int) $page,
        ]);
    }

    public function rules(): array
    {
        return [
            'perPage' => ['integer', 'min:1', 'max:1000'],
            'page' => ['integer', 'min:1'],
        ];
    }
}

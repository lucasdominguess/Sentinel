<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterAcaoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reorganiza a query antes da validação: tudo que não é perPage/page vai para 'filters'.
     * Ex: ?perPage=10&page=1&name=lucas&email=teste -> filters: ['name'=>'lucas','email'=>'teste']
     */
    protected function prepareForValidation(): void
    {
        $reserved = ['perPage', 'page'];

        $filters = collect($this->query())
            ->except($reserved)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $perPage = $this->query('perPage');
        $page    = $this->query('page');

        $this->merge([
            'perPage' => ($perPage === '' || $perPage === null) ? 15 : (int) $perPage,
            'page'    => ($page === '' || $page === null) ? 1 : (int) $page,
            'filters' => $filters,
        ]);
    }

    public function rules(): array
    {
        return [
            'perPage' => ['integer', 'min:1', 'max:100'],
            'page'    => ['integer', 'min:1'],
            'filters' => ['array'],
        ];
    }
}

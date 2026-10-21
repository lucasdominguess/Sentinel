<?php

namespace App\Http\Requests\Siga;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BuscarPessoaSigaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Remove pontuação de CPF/CNS antes de validar o tamanho.
        $this->merge([
            'cpf' => filled($this->cpf) ? preg_replace('/\D/', '', $this->cpf) : null,
            'cns' => filled($this->cns) ? preg_replace('/\D/', '', $this->cns) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'cpf' => 'nullable|string|size:11',
            'cns' => 'nullable|string|size:15',
            'nome' => 'nullable|string|min:3|max:70',
            'nome_mae' => 'nullable|string|min:3|max:70',
            'data_nascimento' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $temData = filled($this->data_nascimento);
            $temNome = filled($this->nome);
            $temNomeMae = filled($this->nome_mae);

            // Nome (ou nome da mãe) só valem como critério junto da data de nascimento.
            if ($temNome && !$temData) {
                $validator->errors()->add('data_nascimento', 'Informe a data de nascimento ao pesquisar por nome.');
            }

            if ($temNomeMae && !$temData) {
                $validator->errors()->add('data_nascimento', 'Informe a data de nascimento ao pesquisar por nome da mãe.');
            }

            $temCpf = filled($this->cpf);
            $temCns = filled($this->cns);
            $criterioNome = $temNome && $temData;
            $criterioNomeMae = $temNomeMae && $temData;

            if (!$temCpf && !$temCns && !$criterioNome && !$criterioNomeMae) {
                $validator->errors()->add(
                    'criterio',
                    'Informe CPF, CNS, Nome + Data de Nascimento ou Nome da Mãe + Data de Nascimento.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'cpf.size' => 'O CPF deve conter exatamente 11 dígitos.',
            'cns.size' => 'O CNS deve conter exatamente 15 dígitos.',
            'nome.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'nome.max' => 'O nome não pode exceder 70 caracteres.',
            'nome_mae.min' => 'O nome da mãe deve ter no mínimo 3 caracteres.',
            'nome_mae.max' => 'O nome da mãe não pode exceder 70 caracteres.',
            'data_nascimento.date_format' => 'A data de nascimento deve estar no formato AAAA-MM-DD.',
        ];
    }
}

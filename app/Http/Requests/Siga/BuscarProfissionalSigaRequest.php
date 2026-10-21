<?php

namespace App\Http\Requests\Siga;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BuscarProfissionalSigaRequest extends FormRequest
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
            'cmes' => 'required|string',
            'cpf' => 'nullable|string|size:11',
            'cns' => 'nullable|string|size:15',
            'data_inicio' => 'nullable|date_format:d/m/Y',
            'data_fim' => 'nullable|date_format:d/m/Y|required_with:data_inicio',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $temCpf = filled($this->cpf);
            $temCns = filled($this->cns);
            $temPeriodo = filled($this->data_inicio) && filled($this->data_fim);

            // Regra da doc: ao menos um critério (CPF, CNS ou o par de datas).
            if (!$temCpf && !$temCns && !$temPeriodo) {
                $validator->errors()->add(
                    'criterio',
                    'Informe o CPF, o CNS ou o período (data_inicio e data_fim) para a pesquisa.'
                );
            }

            if ($temPeriodo) {
                $inicio = Carbon::createFromFormat('d/m/Y', $this->data_inicio)->startOfDay();
                $fim = Carbon::createFromFormat('d/m/Y', $this->data_fim)->startOfDay();

                if ($fim->lt($inicio)) {
                    $validator->errors()->add(
                        'data_fim',
                        'A data final não pode ser anterior à data inicial.'
                    );
                } elseif (abs($inicio->diffInDays($fim)) > 31) {
                    // Limite máximo do período conforme a doc.
                    $validator->errors()->add(
                        'data_fim',
                        'O período da pesquisa não pode exceder 31 dias.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'cmes.required' => 'O CMES do estabelecimento é obrigatório.',
            'cmes.size' => 'O CMES deve conter exatamente 10 caracteres.',
            'cpf.size' => 'O CPF deve conter exatamente 11 dígitos.',
            'cns.size' => 'O CNS deve conter exatamente 15 dígitos.',
            'data_inicio.date_format' => 'A data inicial deve estar no formato DD/MM/AAAA.',
            'data_fim.date_format' => 'A data final deve estar no formato DD/MM/AAAA.',
            'data_fim.required_with' => 'Informe também a data final do período.',
        ];
    }
}

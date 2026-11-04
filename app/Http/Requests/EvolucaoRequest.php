<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

// Evolução do patrimônio: um atalho de período ou um intervalo personalizado (de/ate)
class EvolucaoRequest extends FormRequest
{
    public const PERIODOS = [
        'semana' => 'Semana',
        'mes' => 'Mês',
        'mes-anterior' => 'Mês anterior',
        'ano' => 'No ano',
        '12m' => '12 meses',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo' => ['nullable', 'in:' . implode(',', array_keys(self::PERIODOS))],
            'de' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'required_with:ate'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:de', 'required_with:de'],
        ];
    }

    public function messages(): array
    {
        return [
            'de.before_or_equal' => 'A data inicial não pode ser futura.',
            'ate.before_or_equal' => 'A data final não pode ser futura.',
            'ate.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
        ];
    }

    /**
     * Intervalo pedido: de/ate explícitos vencem o atalho; sem nada, o mês corrente.
     *
     * @return array{de: string, ate: string, periodo: ?string}
     */
    public function intervalo(): array
    {
        $dados = $this->validated();

        if (!empty($dados['de'])) {
            return ['de' => $dados['de'], 'ate' => $dados['ate'], 'periodo' => null];
        }

        $hoje = Carbon::today();
        $periodo = $dados['periodo'] ?? 'mes';

        [$de, $ate] = match ($periodo) {
            'semana' => [$hoje->copy()->startOfWeek(), $hoje],
            'mes-anterior' => [$hoje->copy()->subMonthNoOverflow()->startOfMonth(), $hoje->copy()->subMonthNoOverflow()->endOfMonth()],
            'ano' => [$hoje->copy()->startOfYear(), $hoje],
            '12m' => [$hoje->copy()->subYear()->addDay(), $hoje],
            default => [$hoje->copy()->startOfMonth(), $hoje],
        };

        return ['de' => $de->toDateString(), 'ate' => $ate->toDateString(), 'periodo' => $periodo];
    }
}

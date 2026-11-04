<?php

namespace App\Repositories;

use App\Contracts\Repositories\CotacaoDiariaRepositoryInterface;
use App\Models\CotacaoDiaria;
use Illuminate\Support\Collection;

// Leitura da série de fechamentos diários (cotacoes_diarias), base da foto
// da carteira em uma data e da evolução do patrimônio.
class CotacaoDiariaRepository implements CotacaoDiariaRepositoryInterface
{
    public function __construct(
        private CotacaoDiaria $cotacaoDiariaModel,
    ) {}

    // Os N últimos fechamentos de cada ativo com data <= $ate (mais recente primeiro):
    // o 1º é o preço do dia (ou do último pregão antes dele), o 2º o pregão anterior.
    public function ultimosFechamentos(array $acaoIds, string $ate, int $quantos = 2): Collection
    {
        if (empty($acaoIds)) {
            return collect();
        }

        $ranqueados = $this->cotacaoDiariaModel->query()
            ->select('acao_id', 'data', 'fechamento')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY acao_id ORDER BY data DESC) as ordem')
            ->whereIn('acao_id', $acaoIds)
            ->whereDate('data', '<=', $ate)
            ->whereNotNull('fechamento');

        return $this->cotacaoDiariaModel->newQuery()
            ->fromSub($ranqueados, 'r')
            ->where('ordem', '<=', $quantos)
            ->orderBy('acao_id')
            ->orderBy('ordem')
            ->get();
    }

    // Fechamentos dos ativos no intervalo (inclusive), em ordem cronológica
    public function fechamentosEntre(array $acaoIds, string $de, string $ate): Collection
    {
        if (empty($acaoIds)) {
            return collect();
        }

        return $this->cotacaoDiariaModel->query()
            ->select('acao_id', 'data', 'fechamento')
            ->whereIn('acao_id', $acaoIds)
            ->whereBetween('data', [$de, $ate])
            ->whereNotNull('fechamento')
            ->orderBy('data')
            ->get();
    }

    // Dias de pregão no intervalo: datas com fechamento de qualquer ativo
    public function pregoes(string $de, string $ate): array
    {
        return $this->cotacaoDiariaModel->query()
            ->whereBetween('data', [$de, $ate])
            ->distinct()
            ->orderBy('data')
            ->pluck('data')
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->toDateString())
            ->all();
    }
}

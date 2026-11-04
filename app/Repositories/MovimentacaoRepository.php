<?php

namespace App\Repositories;

use App\Contracts\Repositories\AporteRepositoryInterface;
use App\Models\Aporte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AporteRepository implements AporteRepositoryInterface
{
    public function __construct(
        private Aporte $aporteModel,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        $query = $this->aporteModel->query()->with($this->withRelations());

        if (!empty($filters['acao_id'])) {
            $query->where('acao_id', $filters['acao_id']);
        }

        // Compras mais recentes primeiro; empate pelo id mais novo
        return $query
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function show(Aporte $aporte): Aporte
    {
        return $aporte->load($this->withRelations());
    }

    public function store(array $data): Aporte
    {
        $aporte = $this->aporteModel->create($data);

        return $aporte->load($this->withRelations());
    }

    public function update(Aporte $aporte, array $data): Aporte
    {
        $aporte->update($data);

        return $aporte->load($this->withRelations());
    }

    public function delete(Aporte $aporte)
    {
        return $aporte->delete();
    }

    // Posição consolidada por ativo: soma quantidade e total investido e já traz
    // o ativo com a última cotação para o Service derivar preço médio/valorização.
    public function posicoes(): Collection
    {
        return $this->aporteModel->query()
            ->selectRaw('acao_id, SUM(quantidade) as quantidade, SUM(total_investido) as total_investido')
            ->groupBy('acao_id')
            ->with('acao.ultimaCotacao')
            ->get();
    }

    // Relações carregadas em toda projeção de Aporte — consistência entre os métodos
    private function withRelations(): array
    {
        return ['acao'];
    }
}

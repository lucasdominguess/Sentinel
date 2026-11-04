<?php

namespace App\Repositories;

use App\Contracts\Repositories\MovimentacaoRepositoryInterface;
use App\Models\Movimentacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MovimentacaoRepository implements MovimentacaoRepositoryInterface
{
    public function __construct(
        private Movimentacao $movimentacaoModel,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        $query = $this->movimentacaoModel->query()->with($this->withRelations());

        if (!empty($filters['acao_id'])) {
            $query->where('acao_id', $filters['acao_id']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        // Movimentações mais recentes primeiro; empate pelo id mais novo
        return $query
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function show(Movimentacao $movimentacao): Movimentacao
    {
        return $movimentacao->load($this->withRelations());
    }

    public function store(array $data): Movimentacao
    {
        $movimentacao = $this->movimentacaoModel->create($data);

        return $movimentacao->load($this->withRelations());
    }

    public function update(Movimentacao $movimentacao, array $data): Movimentacao
    {
        $movimentacao->update($data);

        return $movimentacao->load($this->withRelations());
    }

    public function delete(Movimentacao $movimentacao)
    {
        return $movimentacao->delete();
    }

    // Todas as movimentações de um ativo, em ordem cronológica — base da validação de saldo
    public function doAtivo(int $acaoId): Collection
    {
        return $this->movimentacaoModel->query()
            ->where('acao_id', $acaoId)
            ->orderBy('data')
            ->orderBy('id')
            ->get();
    }

    // Movimentações da carteira até a data (inclusive), com o ativo e a última cotação,
    // para o Service consolidar as posições. Sem data: todo o histórico.
    public function daCarteira(?string $ate = null): Collection
    {
        return $this->movimentacaoModel->query()
            ->when($ate, fn ($q) => $q->whereDate('data', '<=', $ate))
            ->with('acao.ultimaCotacao')
            ->orderBy('data')
            ->orderBy('id')
            ->get();
    }

    // Data da primeira movimentação: antes dela a carteira não existe
    public function primeiraData(): ?string
    {
        $data = $this->movimentacaoModel->query()->min('data');

        return $data ? \Illuminate\Support\Carbon::parse($data)->toDateString() : null;
    }

    // Relações carregadas em toda projeção de Movimentacao — consistência entre os métodos
    private function withRelations(): array
    {
        return ['acao'];
    }
}

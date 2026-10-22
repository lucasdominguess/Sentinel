<?php

namespace App\Repositories;

use App\Contracts\Repositories\AcaoRepositoryInterface;
use App\Models\Acao;
use App\Models\Cotacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AcaoRepository implements AcaoRepositoryInterface
{
    public function __construct(
        private Acao $acaoModel,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        // Variação do dia da última cotação de cada ação (subconsulta correlacionada)
        $ultimaVariacao = Cotacao::query()
            ->select('variacao_dia_pct')
            ->whereColumn('acao_id', 'acoes.id')
            ->orderByDesc('capturado_em')
            ->limit(1);

        $query = $this->acaoModel->query()
            ->select('acoes.*')
            ->selectSub($ultimaVariacao, 'ultima_variacao')
            ->with($this->withRelations());

        if (!empty($filters['ticker'])) {
            $query->where('ticker', 'like', '%' . mb_strtoupper($filters['ticker']) . '%');
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['setor'])) {
            $query->where('setor', $filters['setor']);
        }

        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $query->where('ativo', filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN));
        }

        // Maiores altas do dia primeiro; ações sem cotação por último; empate alfabético
        return $query
            ->orderByRaw('ultima_variacao DESC NULLS LAST')
            ->orderBy('ticker')
            ->paginate($perPage);
    }

    public function show(Acao $acao): Acao
    {
        return $acao->load($this->withRelations());
    }

    public function store(array $data): Acao
    {
        $acao = $this->acaoModel->create($data);

        return $acao->load($this->withRelations());
    }

    public function update(Acao $acao, array $data): Acao
    {
        $acao->update($data);

        return $acao->load($this->withRelations());
    }

    public function delete(Acao $acao)
    {
        return $acao->delete();
    }

    public function historicoCotacoes(Acao $acao, int $perPage = 90, int $page = 1): LengthAwarePaginator
    {
        // Ordem cronológica (mais antiga -> mais recente) para alimentar o gráfico de evolução
        return $acao->cotacoes()
            ->orderBy('capturado_em')
            ->paginate($perPage);
    }

    // Relações carregadas em toda projeção de Acao — garante consistência entre os métodos
    private function withRelations(): array
    {
        return ['ultimaCotacao'];
    }
}

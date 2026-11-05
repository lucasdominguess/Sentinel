<?php

namespace App\Services;

use App\Contracts\Repositories\MovimentacaoRepositoryInterface;
use App\Contracts\Services\MovimentacaoServiceInterface;
use App\DTOs\MovimentacaoDTO;
use App\DTOs\MovimentacaoResponseDTO;
use App\DTOs\PosicaoResponseDTO;
use App\Enums\TipoMovimentacao;
use App\Models\Movimentacao;
use App\Services\Carteira\LivroCarteira;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MovimentacaoService implements MovimentacaoServiceInterface
{
    public function __construct(
        private MovimentacaoRepositoryInterface $repository,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        $paginator = $this->repository->index($perPage, $page, $filters);

        $paginator->getCollection()->transform(function ($item) {
            return MovimentacaoResponseDTO::fromModel($item)->toArray();
        });

        return $paginator;
    }

    public function show(Movimentacao $movimentacao): MovimentacaoResponseDTO
    {
        $movimentacao = $this->repository->show($movimentacao);

        return MovimentacaoResponseDTO::fromModel($movimentacao);
    }

    public function store(MovimentacaoDTO $dto): MovimentacaoResponseDTO
    {
        $data = $dto->toArray();

        // Regra de negócio: total é derivado de preço x quantidade
        $data['total'] = $this->calcularTotal($data['preco'], $data['quantidade']);

        // Uma venda não pode deixar o saldo negativo em nenhuma data
        $nova = new Movimentacao($data);
        $this->validarSaldo($this->repository->doAtivo($nova->acao_id)->push($nova));

        $movimentacao = $this->repository->store($data);

        return MovimentacaoResponseDTO::fromModel($movimentacao);
    }

    public function update(Movimentacao $movimentacao, MovimentacaoDTO $dto): MovimentacaoResponseDTO
    {
        $data = $dto->toArray();

        // Recalcula o total quando preço ou quantidade mudam (usa o valor atual como base)
        if (isset($data['preco']) || isset($data['quantidade'])) {
            $data['total'] = $this->calcularTotal(
                $data['preco'] ?? $movimentacao->preco,
                $data['quantidade'] ?? $movimentacao->quantidade,
            );
        }

        // Simula a alteração antes de gravar: o ativo de destino recebe a versão nova
        // e, se o ativo mudou, o de origem perde a movimentação — os dois precisam fechar.
        $editada = (clone $movimentacao)->fill($data);
        $semEsta = fn (int $acaoId) => $this->repository->doAtivo($acaoId)
            ->reject(fn (Movimentacao $m) => $m->id === $movimentacao->id);

        $this->validarSaldo($semEsta($editada->acao_id)->push($editada));
        if ($editada->acao_id !== $movimentacao->acao_id) {
            $this->validarSaldo($semEsta($movimentacao->acao_id));
        }

        $movimentacao = $this->repository->update($movimentacao, $data);

        return MovimentacaoResponseDTO::fromModel($movimentacao);
    }

    public function delete(Movimentacao $movimentacao)
    {
        // Excluir uma compra pode deixar uma venda posterior sem cotas
        $this->validarSaldo(
            $this->repository->doAtivo($movimentacao->acao_id)
                ->reject(fn (Movimentacao $m) => $m->id === $movimentacao->id)
        );

        return $this->repository->delete($movimentacao);
    }

    // Posição consolidada por ativo na data (inclusive); sem data, a posição atual.
    // $precos ([acao_id => ['preco' => ?float, 'anterior' => ?float, 'data' => ?'Y-m-d']])
    // fixa o preço de referência — o fechamento de um dia passado; sem ele, vale a
    // última cotação. 'data' é o pregão do preço: o que foi movimentado nele é fluxo
    // do dia na variação. Ativos zerados por venda não aparecem.
    public function posicoes(?string $ate = null, ?array $precos = null): Collection
    {
        $movs = $this->repository->daCarteira($ate);
        $acoes = $movs->pluck('acao', 'acao_id');

        $refs = $acoes->map(fn ($acao, $acaoId) => $precos !== null
            ? ($precos[$acaoId] ?? ['preco' => null, 'anterior' => null, 'data' => null])
            : [
                'preco' => $acao->ultimaCotacao?->preco,
                'anterior' => $acao->ultimaCotacao?->fechamento_anterior,
                'data' => $acao->ultimaCotacao?->capturado_em?->toDateString(),
            ]);
        $dias = $this->movimentadoNoPregao($movs, $refs);

        return collect(LivroCarteira::consolidar($movs))
            ->filter(fn ($pos) => $pos['quantidade'] > 0)
            ->map(function ($pos, $acaoId) use ($acoes, $refs, $dias) {
                $ref = $refs[$acaoId];
                $anterior = $ref['anterior'] !== null && (float) $ref['anterior'] > 0 ? (float) $ref['anterior'] : null;

                return PosicaoResponseDTO::fromConsolidacao(
                    $acoes[$acaoId],
                    $pos['quantidade'],
                    $pos['custo'],
                    $pos['lucro_realizado'],
                    $ref['preco'] !== null ? (float) $ref['preco'] : null,
                    isset($dias[$acaoId]) ? $dias[$acaoId] + ['anterior' => $anterior] : null,
                )->toArray();
            })
            ->sortByDesc('total_investido')
            ->values();
    }

    // Por ativo: cotas que já estavam na carteira antes do pregão de referência e o
    // que foi comprado/vendido nele (ou depois, até a data pedida). Ativo sem data de
    // referência não tem variação do dia.
    private function movimentadoNoPregao(Collection $movs, Collection $refs): array
    {
        $dias = [];
        foreach ($movs as $m) {
            $pregao = $refs[$m->acao_id]['data'] ?? null;
            if ($pregao === null) {
                continue;
            }

            $d = &$dias[$m->acao_id];
            $d ??= ['cotas_antes' => 0, 'compras' => 0.0, 'vendas' => 0.0];
            $compra = $m->tipo === TipoMovimentacao::COMPRA;
            if ($m->data->toDateString() < $pregao) {
                $d['cotas_antes'] += $compra ? $m->quantidade : -$m->quantidade;
            } else {
                $d[$compra ? 'compras' : 'vendas'] += (float) $m->total;
            }
            unset($d);
        }

        return $dias;
    }

    // Percorre as movimentações do ativo na ordem do tempo e barra a primeira venda
    // que consumir mais cotas do que havia naquela data.
    private function validarSaldo(Collection $movs): void
    {
        $saldo = 0;

        foreach (LivroCarteira::cronologica($movs) as $m) {
            if ($m->tipo === TipoMovimentacao::COMPRA) {
                $saldo += $m->quantidade;
                continue;
            }

            if ($m->quantidade > $saldo) {
                $ticker = $m->acao?->ticker ?? 'o ativo';
                throw ValidationException::withMessages([
                    'quantidade' => "A venda de {$m->quantidade} cota(s) em {$m->data->format('d/m/Y')} excede o saldo de {$saldo} cota(s) de {$ticker} nessa data.",
                ]);
            }

            $saldo -= $m->quantidade;
        }
    }

    private function calcularTotal(float|string $preco, int $quantidade): float
    {
        return round((float) $preco * $quantidade, 2);
    }
}

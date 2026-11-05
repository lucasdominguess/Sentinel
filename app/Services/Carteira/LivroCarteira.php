<?php

namespace App\Services\Carteira;

use App\Enums\TipoMovimentacao;
use App\Models\Movimentacao;
use Illuminate\Support\Collection;

/**
 * Livro da carteira: aplica movimentações em ordem e mantém, por ativo, as cotas,
 * o custo da posição aberta e o lucro realizado.
 *
 * Custo médio ponderado (regra da Receita): a compra soma cotas e custo; a venda
 * baixa as cotas ao preço médio vigente — não altera o preço médio — e a
 * diferença para o preço de venda é lucro (ou prejuízo) realizado.
 *
 * É stateful de propósito: a evolução diária aplica as movimentações aos poucos,
 * dia a dia, sem reprocessar o histórico inteiro a cada pregão.
 */
class LivroCarteira
{
    /** @var array<int, array{quantidade: int, custo: float, lucro_realizado: float}> */
    private array $posicoes = [];

    // Ordem do livro: data; no mesmo dia, compras antes de vendas; depois ordem de registro
    // (movimentação ainda não gravada fica por último no seu dia).
    public static function cronologica(Collection $movs): Collection
    {
        return $movs->sort(function (Movimentacao $a, Movimentacao $b) {
            return [$a->data->toDateString(), $a->tipo === TipoMovimentacao::VENDA, $a->id ?? PHP_INT_MAX]
                <=> [$b->data->toDateString(), $b->tipo === TipoMovimentacao::VENDA, $b->id ?? PHP_INT_MAX];
        })->values();
    }

    // Atalho: consolida um conjunto de movimentações de uma vez
    public static function consolidar(Collection $movs): array
    {
        $livro = new self();
        foreach (self::cronologica($movs) as $m) {
            $livro->aplicar($m);
        }

        return $livro->posicoes();
    }

    // Aplica uma movimentação e devolve o lucro realizado por ela (0 em compras)
    public function aplicar(Movimentacao $m): float
    {
        $p = $this->posicoes[$m->acao_id] ?? ['quantidade' => 0, 'custo' => 0.0, 'lucro_realizado' => 0.0];

        if ($m->tipo === TipoMovimentacao::COMPRA) {
            $p['quantidade'] += $m->quantidade;
            $p['custo'] += (float) $m->total;
            $this->posicoes[$m->acao_id] = $p;

            return 0.0;
        }

        // Realizado sobre o total da nota (o mesmo valor que a evolução conta como
        // resgate), não sobre preço × quantidade sem arredondar
        $precoMedio = $p['quantidade'] > 0 ? $p['custo'] / $p['quantidade'] : 0.0;
        $recebido = $m->total !== null ? (float) $m->total : (float) $m->preco * $m->quantidade;
        $realizado = $recebido - $precoMedio * $m->quantidade;

        $p['lucro_realizado'] += $realizado;
        $p['custo'] -= $precoMedio * $m->quantidade;
        $p['quantidade'] -= $m->quantidade;
        if ($p['quantidade'] <= 0) {
            $p['custo'] = 0.0; // zera resíduo de arredondamento
        }
        $this->posicoes[$m->acao_id] = $p;

        return $realizado;
    }

    /** @return array<int, array{quantidade: int, custo: float, lucro_realizado: float}> */
    public function posicoes(): array
    {
        return $this->posicoes;
    }
}

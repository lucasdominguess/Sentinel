<?php

namespace App\DTOs;

use App\Models\Acao;

// Projeção consolidada de um ativo na carteira (resultado das movimentações).
// Recebe a posição já consolidada pelo MovimentacaoService (cotas, custo da
// posição aberta e lucro realizado) e o preço de referência, e deriva preço
// médio e valorização. O preço é parâmetro para servir à data de hoje ou a
// um fechamento passado.
//
// Valores em R$ saem arredondados em centavos e a valorização é a diferença
// dos valores já arredondados: o que a tela mostra fecha (valor − investido =
// valorização). Os percentuais usam os preços sem arredondar.
class PosicaoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $acaoId,
        public readonly ?string $ticker,
        public readonly ?string $nome,
        public readonly ?string $tipo,
        public readonly int $quantidade,
        public readonly float $totalInvestido,
        public readonly float $precoMedio,
        public readonly ?float $precoAtual,
        public readonly ?float $valorAtual,
        public readonly ?float $valorizacaoReais,
        public readonly ?float $valorizacaoPct,
        public readonly float $lucroRealizado,
        public readonly ?float $variacaoDiaReais,
        public readonly ?float $variacaoDiaPct,
        public readonly ?float $variacaoDiaBase,
    ) {}

    /**
     * $dia: o pregão de referência do preço, para a variação do dia —
     * ['anterior' => ?float fechamento do pregão anterior, 'cotas_antes' => int cotas
     *  antes do pregão, 'compras' => float, 'vendas' => float movimentado no pregão].
     */
    public static function fromConsolidacao(
        Acao $acao,
        int $quantidade,
        float $custo,
        float $lucroRealizado,
        ?float $precoAtual,
        ?array $dia = null,
    ): self {
        $precoMedio = $quantidade > 0 ? $custo / $quantidade : 0.0;
        $totalInvestido = round($custo, 2);

        // Sem cotação não há como calcular valorização
        $valorAtual = $precoAtual !== null ? round($precoAtual * $quantidade, 2) : null;
        $valorizacaoReais = $valorAtual !== null ? round($valorAtual - $totalInvestido, 2) : null;
        $valorizacaoPct = ($precoAtual !== null && $precoMedio > 0)
            ? ($precoAtual / $precoMedio - 1) * 100
            : null;

        [$variacaoDia, $variacaoDiaPct, $base] = self::variacaoDoDia($valorAtual, $dia);

        return new self(
            acaoId: $acao->id,
            ticker: $acao->ticker,
            nome: $acao->nome,
            tipo: $acao->tipo,
            quantidade: $quantidade,
            totalInvestido: $totalInvestido,
            precoMedio: $precoMedio,
            precoAtual: $precoAtual,
            valorAtual: $valorAtual,
            valorizacaoReais: $valorizacaoReais,
            valorizacaoPct: $valorizacaoPct,
            lucroRealizado: round($lucroRealizado, 2),
            variacaoDiaReais: $variacaoDia,
            variacaoDiaPct: $variacaoDiaPct,
            variacaoDiaBase: $base,
        );
    }

    // Mesma conta da evolução para um pregão: compras do dia entram pelo preço
    // pago (não pelo fechamento anterior) e vendas do dia saem pelo valor recebido.
    //   variação = valor + vendas − (cotas de antes × fech. anterior + compras)
    //   %        = variação ÷ (cotas de antes × fech. anterior + compras)
    private static function variacaoDoDia(?float $valorAtual, ?array $dia): array
    {
        if ($valorAtual === null || $dia === null) {
            return [null, null, null];
        }

        $cotasAntes = (int) ($dia['cotas_antes'] ?? 0);
        $anterior = $dia['anterior'] ?? null;
        if ($cotasAntes > 0 && $anterior === null) {
            return [null, null, null];
        }

        $base = round(($cotasAntes > 0 ? $cotasAntes * $anterior : 0.0) + ($dia['compras'] ?? 0.0), 2);
        if ($base <= 0) {
            return [null, null, null];
        }

        $variacao = round($valorAtual + ($dia['vendas'] ?? 0.0) - $base, 2);

        return [$variacao, $variacao / $base * 100, $base];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'acao_id' => $this->acaoId,
            'ticker' => $this->ticker,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'quantidade' => $this->quantidade,
            'total_investido' => $this->totalInvestido,
            'preco_medio' => $this->precoMedio,
            'preco_atual' => $this->precoAtual,
            'valor_atual' => $this->valorAtual,
            'valorizacao_reais' => $this->valorizacaoReais,
            'valorizacao_pct' => $this->valorizacaoPct,
            'lucro_realizado' => $this->lucroRealizado,
            'variacao_dia_reais' => $this->variacaoDiaReais,
            'variacao_dia_pct' => $this->variacaoDiaPct,
            'variacao_dia_base' => $this->variacaoDiaBase,
        ];
    }
}

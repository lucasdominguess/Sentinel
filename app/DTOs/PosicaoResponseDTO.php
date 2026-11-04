<?php

namespace App\DTOs;

use App\Models\Acao;

// Projeção consolidada de um ativo na carteira (resultado das movimentações).
// Recebe a posição já consolidada pelo MovimentacaoService (cotas, custo da
// posição aberta e lucro realizado) e o preço de referência, e deriva preço
// médio e valorização. O preço é parâmetro para servir à data de hoje ou a
// um fechamento passado.
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
    ) {}

    public static function fromConsolidacao(
        Acao $acao,
        int $quantidade,
        float $custo,
        float $lucroRealizado,
        ?float $precoAtual,
        ?float $precoAnterior = null,
    ): self {
        $precoMedio = $quantidade > 0 ? $custo / $quantidade : 0.0;

        // Sem cotação não há como calcular valorização
        $valorAtual = $precoAtual !== null ? $precoAtual * $quantidade : null;
        $valorizacaoReais = $valorAtual !== null ? $valorAtual - $custo : null;
        $valorizacaoPct = ($precoAtual !== null && $precoMedio > 0)
            ? ($precoAtual / $precoMedio - 1) * 100
            : null;

        // Variação do dia: preço de referência contra o fechamento do pregão anterior
        $temDia = $precoAtual !== null && $precoAnterior !== null;

        return new self(
            acaoId: $acao->id,
            ticker: $acao->ticker,
            nome: $acao->nome,
            tipo: $acao->tipo,
            quantidade: $quantidade,
            totalInvestido: round($custo, 2),
            precoMedio: $precoMedio,
            precoAtual: $precoAtual,
            valorAtual: $valorAtual,
            valorizacaoReais: $valorizacaoReais,
            valorizacaoPct: $valorizacaoPct,
            lucroRealizado: round($lucroRealizado, 2),
            variacaoDiaReais: $temDia ? ($precoAtual - $precoAnterior) * $quantidade : null,
            variacaoDiaPct: $temDia ? ($precoAtual / $precoAnterior - 1) * 100 : null,
        );
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
        ];
    }
}

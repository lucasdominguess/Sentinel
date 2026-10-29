<?php

namespace App\DTOs;

use App\Models\Aporte;

// Projeção consolidada de um ativo na carteira (agregado de vários aportes).
// Recebe uma linha agregada (SUM de quantidade e total_investido) com o ativo
// e a última cotação já carregados, e deriva preço médio e valorização.
class PosicaoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $acaoId,
        public readonly ?string $ticker,
        public readonly ?string $nome,
        public readonly int $quantidade,
        public readonly float $totalInvestido,
        public readonly float $precoMedio,
        public readonly ?float $precoAtual,
        public readonly ?float $valorAtual,
        public readonly ?float $valorizacaoReais,
        public readonly ?float $valorizacaoPct,
    ) {}

    public static function fromModel(Aporte $model): self
    {
        $quantidade = (int) $model->quantidade;
        $totalInvestido = (float) $model->total_investido;
        $precoMedio = $quantidade > 0 ? $totalInvestido / $quantidade : 0.0;

        $precoAtual = $model->acao?->ultimaCotacao?->preco;
        $precoAtual = $precoAtual !== null ? (float) $precoAtual : null;

        // Sem cotação atual não há como calcular valorização
        $valorAtual = $precoAtual !== null ? $precoAtual * $quantidade : null;
        $valorizacaoReais = $valorAtual !== null ? $valorAtual - $totalInvestido : null;
        $valorizacaoPct = ($precoAtual !== null && $precoMedio > 0)
            ? ($precoAtual / $precoMedio - 1) * 100
            : null;

        return new self(
            acaoId: $model->acao_id,
            ticker: $model->acao?->ticker,
            nome: $model->acao?->nome,
            quantidade: $quantidade,
            totalInvestido: $totalInvestido,
            precoMedio: $precoMedio,
            precoAtual: $precoAtual,
            valorAtual: $valorAtual,
            valorizacaoReais: $valorizacaoReais,
            valorizacaoPct: $valorizacaoPct,
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
            'quantidade' => $this->quantidade,
            'total_investido' => $this->totalInvestido,
            'preco_medio' => $this->precoMedio,
            'preco_atual' => $this->precoAtual,
            'valor_atual' => $this->valorAtual,
            'valorizacao_reais' => $this->valorizacaoReais,
            'valorizacao_pct' => $this->valorizacaoPct,
        ];
    }
}

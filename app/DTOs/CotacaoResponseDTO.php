<?php

namespace App\DTOs;

use App\Models\Cotacao;

class CotacaoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $preco,
        public readonly ?string $fechamentoAnterior,
        public readonly ?string $abertura,
        public readonly ?string $minimaDia,
        public readonly ?string $maximaDia,
        public readonly ?string $variacaoDiaPct,
        public readonly ?string $minima52s,
        public readonly ?string $maxima52s,
        public readonly ?int $volume,
        public readonly ?int $volumeMedio,
        public readonly ?string $valorMercado,
        public readonly ?string $dividendYield,
        public readonly ?string $ultimoDividendo,
        public readonly ?string $ultimoDividendoData,
        public readonly ?string $precoLucro,
        public readonly ?string $estadoMercado,
        public readonly ?string $capturadoEm,
    ) {}

    public static function fromModel(Cotacao $model): self
    {
        return new self(
            id: $model->id,
            preco: $model->preco,
            fechamentoAnterior: $model->fechamento_anterior,
            abertura: $model->abertura,
            minimaDia: $model->minima_dia,
            maximaDia: $model->maxima_dia,
            variacaoDiaPct: $model->variacao_dia_pct,
            minima52s: $model->minima_52s,
            maxima52s: $model->maxima_52s,
            volume: $model->volume,
            volumeMedio: $model->volume_medio,
            valorMercado: $model->valor_mercado,
            dividendYield: $model->dividend_yield,
            ultimoDividendo: $model->ultimo_dividendo,
            ultimoDividendoData: $model->ultimo_dividendo_data?->format('Y-m-d'),
            precoLucro: $model->preco_lucro,
            estadoMercado: $model->estado_mercado,
            capturadoEm: $model->capturado_em?->toIso8601String(),
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'preco' => $this->preco,
            'fechamento_anterior' => $this->fechamentoAnterior,
            'abertura' => $this->abertura,
            'minima_dia' => $this->minimaDia,
            'maxima_dia' => $this->maximaDia,
            'variacao_dia_pct' => $this->variacaoDiaPct,
            'minima_52s' => $this->minima52s,
            'maxima_52s' => $this->maxima52s,
            'volume' => $this->volume,
            'volume_medio' => $this->volumeMedio,
            'valor_mercado' => $this->valorMercado,
            'dividend_yield' => $this->dividendYield,
            'ultimo_dividendo' => $this->ultimoDividendo,
            'ultimo_dividendo_data' => $this->ultimoDividendoData,
            'preco_lucro' => $this->precoLucro,
            'estado_mercado' => $this->estadoMercado,
            'capturado_em' => $this->capturadoEm,
        ];
    }
}

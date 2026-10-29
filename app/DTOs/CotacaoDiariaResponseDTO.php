<?php

namespace App\DTOs;

use App\Models\CotacaoDiaria;

class CotacaoDiariaResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $data,
        public readonly ?string $abertura,
        public readonly ?string $maxima,
        public readonly ?string $minima,
        public readonly ?string $fechamento,
        public readonly ?string $fechamentoAjustado,
        public readonly ?int $volume,
    ) {}

    public static function fromModel(CotacaoDiaria $model): self
    {
        return new self(
            id: $model->id,
            data: $model->data?->format('Y-m-d'),
            abertura: $model->abertura,
            maxima: $model->maxima,
            minima: $model->minima,
            fechamento: $model->fechamento,
            fechamentoAjustado: $model->fechamento_ajustado,
            volume: $model->volume,
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
            'data' => $this->data,
            'abertura' => $this->abertura,
            'maxima' => $this->maxima,
            'minima' => $this->minima,
            'fechamento' => $this->fechamento,
            'fechamento_ajustado' => $this->fechamentoAjustado,
            'volume' => $this->volume,
        ];
    }
}

<?php

namespace App\DTOs;

use App\Models\Aporte;

class AporteResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $acaoId,
        public readonly ?string $ticker,
        public readonly ?string $nome,
        public readonly string $precoPago,
        public readonly int $quantidade,
        public readonly string $totalInvestido,
        public readonly ?string $data,
    ) {}

    public static function fromModel(Aporte $model): self
    {
        return new self(
            id: $model->id,
            acaoId: $model->acao_id,
            ticker: $model->acao?->ticker,
            nome: $model->acao?->nome,
            precoPago: $model->preco_pago,
            quantidade: $model->quantidade,
            totalInvestido: $model->total_investido,
            data: $model->data?->format('Y-m-d'),
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
            'acao_id' => $this->acaoId,
            'ticker' => $this->ticker,
            'nome' => $this->nome,
            'preco_pago' => $this->precoPago,
            'quantidade' => $this->quantidade,
            'total_investido' => $this->totalInvestido,
            'data' => $this->data,
        ];
    }
}

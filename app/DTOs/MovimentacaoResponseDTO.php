<?php

namespace App\DTOs;

use App\Models\Movimentacao;

class MovimentacaoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $acaoId,
        public readonly ?string $ticker,
        public readonly ?string $nome,
        public readonly string $tipo,
        public readonly string $preco,
        public readonly int $quantidade,
        public readonly string $total,
        public readonly ?string $data,
    ) {}

    public static function fromModel(Movimentacao $model): self
    {
        return new self(
            id: $model->id,
            acaoId: $model->acao_id,
            ticker: $model->acao?->ticker,
            nome: $model->acao?->nome,
            tipo: $model->tipo->value,
            preco: $model->preco,
            quantidade: $model->quantidade,
            total: $model->total,
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
            'tipo' => $this->tipo,
            'preco' => $this->preco,
            'quantidade' => $this->quantidade,
            'total' => $this->total,
            'data' => $this->data,
        ];
    }
}

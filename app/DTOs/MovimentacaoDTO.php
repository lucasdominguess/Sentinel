<?php

namespace App\DTOs;

class AporteDTO
{
    public function __construct(
        public readonly ?int $acaoId = null,
        public readonly ?string $precoPago = null,
        public readonly ?int $quantidade = null,
        public readonly ?string $data = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            acaoId: $data['acao_id'] ?? null,
            precoPago: isset($data['preco_pago']) ? (string) $data['preco_pago'] : null,
            quantidade: isset($data['quantidade']) ? (int) $data['quantidade'] : null,
            data: $data['data'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'acao_id' => $this->acaoId,
            'preco_pago' => $this->precoPago,
            'quantidade' => $this->quantidade,
            'data' => $this->data,
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace App\DTOs;

class MovimentacaoDTO
{
    public function __construct(
        public readonly ?int $acaoId = null,
        public readonly ?string $tipo = null,
        public readonly ?string $preco = null,
        public readonly ?int $quantidade = null,
        public readonly ?string $data = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            acaoId: $data['acao_id'] ?? null,
            tipo: $data['tipo'] ?? null,
            preco: isset($data['preco']) ? (string) $data['preco'] : null,
            quantidade: isset($data['quantidade']) ? (int) $data['quantidade'] : null,
            data: $data['data'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'acao_id' => $this->acaoId,
            'tipo' => $this->tipo,
            'preco' => $this->preco,
            'quantidade' => $this->quantidade,
            'data' => $this->data,
        ], fn ($value) => $value !== null);
    }
}

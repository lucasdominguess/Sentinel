<?php

namespace App\DTOs;

class AcaoDTO
{
    public function __construct(
        public readonly ?string $ticker = null,
        public readonly ?string $nome = null,
        public readonly ?string $setor = null,
        public readonly ?string $segmento = null,
        public readonly ?string $moeda = null,
        public readonly ?string $tipo = null,
        public readonly ?bool $ativo = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            ticker: $data['ticker'] ?? null,
            nome: $data['nome'] ?? null,
            setor: $data['setor'] ?? null,
            segmento: $data['segmento'] ?? null,
            moeda: $data['moeda'] ?? null,
            tipo: $data['tipo'] ?? null,
            ativo: $data['ativo'] ?? null,
        );
    }

    public function toArray(): array
    {
        // Remove apenas nulos (não false/0) → permite updates parciais sem perder ativo=false
        return array_filter([
            'ticker' => $this->ticker,
            'nome' => $this->nome,
            'setor' => $this->setor,
            'segmento' => $this->segmento,
            'moeda' => $this->moeda,
            'tipo' => $this->tipo,
            'ativo' => $this->ativo,
        ], fn ($value) => $value !== null);
    }
}

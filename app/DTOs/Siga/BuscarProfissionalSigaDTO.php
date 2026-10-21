<?php

namespace App\DTOs\Siga;

class BuscarProfissionalSigaDTO
{
    public function __construct(
        public readonly string $cmes,
        public readonly ?string $cpf = null,
        public readonly ?string $cns = null,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            cmes: $validated['cmes'],
            cpf: $validated['cpf'] ?? null,
            cns: $validated['cns'] ?? null,
            dataInicio: $validated['data_inicio'] ?? null,
            dataFim: $validated['data_fim'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'cmes' => $this->cmes,
            'cpf' => $this->cpf,
            'cns' => $this->cns,
            'data_inicio' => $this->dataInicio,
            'data_fim' => $this->dataFim,
        ], fn ($value) => $value !== null && $value !== '');
    }
}

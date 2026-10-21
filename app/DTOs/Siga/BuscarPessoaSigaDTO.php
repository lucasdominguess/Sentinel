<?php

namespace App\DTOs\Siga;

use App\Traits\NormalizarTexto;

class BuscarPessoaSigaDTO
{
    use NormalizarTexto;

    public function __construct(
        public readonly ?string $cpf = null,
        public readonly ?string $cns = null,
        public readonly ?string $nome = null,
        public readonly ?string $nomeMae = null,
        public readonly ?string $dataNascimento = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            cpf: $validated['cpf'] ?? null,
            cns: $validated['cns'] ?? null,
            nome: self::normalize($validated['nome'] ?? null),
            nomeMae: self::normalize($validated['nome_mae'] ?? null),
            dataNascimento: $validated['data_nascimento'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'cpf' => $this->cpf,
            'cns' => $this->cns,
            'nome' => $this->nome,
            'nomeMae' => $this->nomeMae,
            'dataNascimento' => $this->dataNascimento,
        ], fn ($value) => $value !== null && $value !== '');
    }
}

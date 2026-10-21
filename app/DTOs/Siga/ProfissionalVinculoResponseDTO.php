<?php

namespace App\DTOs\Siga;

class ProfissionalVinculoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $cnsProfissional,
        public readonly ?string $cpfProfissional,
        public readonly ?string $nomeProfissional,
        public readonly ?string $codigoCboSus,
        public readonly ?string $descricaoCboSus,
        public readonly ?string $codigoVinculo,
        public readonly ?string $dataInicio,
        public readonly ?string $dataFim,
        public readonly ?string $dataUltimaAlteracao,
        public readonly ?string $codigoEquipe,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cnsProfissional: $data['cnsProfissional'] ?? null,
            cpfProfissional: $data['cpfProfissional'] ?? null,
            nomeProfissional: $data['nomeProfissional'] ?? null,
            codigoCboSus: $data['codigoCboSus'] ?? null,
            descricaoCboSus: $data['descricaoCboSus'] ?? null,
            codigoVinculo: $data['codigoVinculo'] ?? null,
            dataInicio: $data['dataInicio'] ?? null,
            dataFim: $data['dataFim'] ?? null,
            dataUltimaAlteracao: $data['dataUltimaAlteracao'] ?? null,
            codigoEquipe: $data['codigoEquipe'] ?? null,
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'cns_profissional' => $this->cnsProfissional,
            'cpf_profissional' => $this->cpfProfissional,
            'nome_profissional' => $this->nomeProfissional,
            'codigo_cbo_sus' => $this->codigoCboSus,
            'descricao_cbo_sus' => $this->descricaoCboSus,
            'codigo_vinculo' => $this->codigoVinculo,
            'data_inicio' => $this->dataInicio,
            'data_fim' => $this->dataFim,
            'data_ultima_alteracao' => $this->dataUltimaAlteracao,
            'codigo_equipe' => $this->codigoEquipe,
        ];
    }
}

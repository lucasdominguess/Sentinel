<?php

namespace App\DTOs\Siga;

class PessoaSigaResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $nome,
        public readonly ?string $nomeSocial,
        public readonly ?string $nomeMae,
        public readonly ?string $nomePai,
        public readonly ?string $cpf,
        public readonly ?string $cns,
        public readonly ?string $dataNascimento,
        public readonly ?string $sexo,
        public readonly ?string $email,
        public readonly ?string $telefoneCelular,
        public readonly ?string $telefoneResidencial,
        public readonly ?array $endereco,
    ) {}

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'nome_social' => $this->nomeSocial,
            'nome_mae' => $this->nomeMae,
            'nome_pai' => $this->nomePai,
            'cpf' => $this->cpf,
            'cns' => $this->cns,
            'data_nascimento' => $this->dataNascimento,
            'sexo' => $this->sexo,
            'email' => $this->email,
            'telefone_celular' => $this->telefoneCelular,
            'telefone_residencial' => $this->telefoneResidencial,
            'endereco' => $this->endereco,
        ];
    }
}

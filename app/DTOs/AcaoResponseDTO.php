<?php

namespace App\DTOs;

use App\Models\Acao;

class AcaoResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $ticker,
        public readonly ?string $nome,
        public readonly ?string $setor,
        public readonly ?string $segmento,
        public readonly ?string $moeda,
        public readonly string $tipo,
        public readonly bool $ativo,
        public readonly ?CotacaoResponseDTO $ultimaCotacao,
    ) {}

    public static function fromModel(Acao $model): self
    {
        $ultima = $model->ultimaCotacao;

        return new self(
            id: $model->id,
            ticker: $model->ticker,
            nome: $model->nome,
            setor: $model->setor,
            segmento: $model->segmento,
            moeda: $model->moeda,
            tipo: $model->tipo,
            ativo: $model->ativo,
            ultimaCotacao: $ultima ? CotacaoResponseDTO::fromModel($ultima) : null,
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
            'ticker' => $this->ticker,
            'nome' => $this->nome,
            'setor' => $this->setor,
            'segmento' => $this->segmento,
            'moeda' => $this->moeda,
            'tipo' => $this->tipo,
            'ativo' => $this->ativo,
            'ultima_cotacao' => $this->ultimaCotacao?->toArray(),
        ];
    }
}

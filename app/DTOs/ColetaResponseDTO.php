<?php

namespace App\DTOs;

use App\Models\Coleta;

class ColetaResponseDTO implements \JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $origem,
        public readonly ?string $inicio,
        public readonly ?string $fim,
        public readonly int $ativosOk,
        public readonly int $ativosFalha,
        public readonly int $registros,
        public readonly ?string $erro,
        public readonly bool $concluida,
        public readonly bool $comFalha,
        public readonly ?int $duracaoSegundos,
    ) {}

    public static function fromModel(Coleta $model): self
    {
        // fim nulo = execução que não terminou (processo morto, máquina desligada).
        // É o sinal que o painel precisa mostrar em vez de simplesmente nada.
        $concluida = $model->fim !== null;

        return new self(
            id: $model->id,
            origem: $model->origem,
            inicio: $model->inicio?->toIso8601String(),
            fim: $model->fim?->toIso8601String(),
            ativosOk: (int) $model->ativos_ok,
            ativosFalha: (int) $model->ativos_falha,
            registros: (int) $model->registros,
            erro: $model->erro,
            concluida: $concluida,
            comFalha: $model->erro !== null || (int) $model->ativos_falha > 0,
            duracaoSegundos: $concluida ? $model->inicio?->diffInSeconds($model->fim) : null,
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
            'origem' => $this->origem,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'ativos_ok' => $this->ativosOk,
            'ativos_falha' => $this->ativosFalha,
            'registros' => $this->registros,
            'erro' => $this->erro,
            'concluida' => $this->concluida,
            'com_falha' => $this->comFalha,
            'duracao_segundos' => $this->duracaoSegundos,
        ];
    }
}

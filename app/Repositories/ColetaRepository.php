<?php

namespace App\Repositories;

use App\Contracts\Repositories\ColetaRepositoryInterface;
use App\Models\Coleta;

class ColetaRepository implements ColetaRepositoryInterface
{
    public function __construct(
        private Coleta $coletaModel,
    ) {}

    public function ultima(): ?Coleta
    {
        return $this->coletaModel->query()
            ->orderByDesc('inicio')
            ->orderByDesc('id')
            ->first();
    }
}

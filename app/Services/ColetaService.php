<?php

namespace App\Services;

use App\Contracts\Repositories\ColetaRepositoryInterface;
use App\Contracts\Services\ColetaServiceInterface;
use App\DTOs\ColetaResponseDTO;

class ColetaService implements ColetaServiceInterface
{
    public function __construct(
        private ColetaRepositoryInterface $repository,
    ) {}

    public function ultima(): ?ColetaResponseDTO
    {
        $coleta = $this->repository->ultima();

        return $coleta ? ColetaResponseDTO::fromModel($coleta) : null;
    }
}

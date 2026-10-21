<?php

namespace App\Contracts\Services\Siga;

use App\DTOs\Siga\BuscarProfissionalSigaDTO;

interface SigaProfissionalServiceInterface
{
    public function buscarProfissional(BuscarProfissionalSigaDTO $dto): array;
}

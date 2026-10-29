<?php

namespace App\Contracts\Services;

use App\DTOs\ColetaResponseDTO;

interface ColetaServiceInterface
{
    public function ultima(): ?ColetaResponseDTO;
}

<?php

namespace App\Contracts\Repositories;

use App\Models\Coleta;

interface ColetaRepositoryInterface
{
    public function ultima(): ?Coleta;
}

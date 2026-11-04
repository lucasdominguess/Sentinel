<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;

interface CotacaoDiariaRepositoryInterface
{
    public function ultimosFechamentos(array $acaoIds, string $ate, int $quantos = 2): Collection;

    public function fechamentosEntre(array $acaoIds, string $de, string $ate): Collection;

    public function pregoes(string $de, string $ate): array;
}

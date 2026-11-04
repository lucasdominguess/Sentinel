<?php

namespace App\Contracts\Services;

use App\DTOs\AporteDTO;
use App\DTOs\AporteResponseDTO;
use App\Models\Aporte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AporteServiceInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Aporte $aporte): AporteResponseDTO;

    public function store(AporteDTO $dto): AporteResponseDTO;

    public function update(Aporte $aporte, AporteDTO $dto): AporteResponseDTO;

    public function delete(Aporte $aporte);

    public function posicoes(): Collection;
}

<?php

namespace App\Contracts\Repositories;

use App\Models\Aporte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AporteRepositoryInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Aporte $aporte): Aporte;

    public function store(array $data): Aporte;

    public function update(Aporte $aporte, array $data): Aporte;

    public function delete(Aporte $aporte);

    public function posicoes(): Collection;
}

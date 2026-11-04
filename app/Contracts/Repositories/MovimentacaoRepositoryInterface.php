<?php

namespace App\Contracts\Repositories;

use App\Models\Movimentacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MovimentacaoRepositoryInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Movimentacao $movimentacao): Movimentacao;

    public function store(array $data): Movimentacao;

    public function update(Movimentacao $movimentacao, array $data): Movimentacao;

    public function delete(Movimentacao $movimentacao);

    public function doAtivo(int $acaoId): Collection;

    public function daCarteira(?string $ate = null): Collection;

    public function primeiraData(): ?string;
}

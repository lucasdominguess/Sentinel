<?php

namespace App\Contracts\Services;

use App\DTOs\MovimentacaoDTO;
use App\DTOs\MovimentacaoResponseDTO;
use App\Models\Movimentacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MovimentacaoServiceInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Movimentacao $movimentacao): MovimentacaoResponseDTO;

    public function store(MovimentacaoDTO $dto): MovimentacaoResponseDTO;

    public function update(Movimentacao $movimentacao, MovimentacaoDTO $dto): MovimentacaoResponseDTO;

    public function delete(Movimentacao $movimentacao);

    public function posicoes(?string $ate = null, ?array $precos = null): Collection;
}

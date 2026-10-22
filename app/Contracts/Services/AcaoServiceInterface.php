<?php

namespace App\Contracts\Services;

use App\DTOs\AcaoDTO;
use App\DTOs\AcaoResponseDTO;
use App\Models\Acao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AcaoServiceInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Acao $acao): AcaoResponseDTO;

    public function store(AcaoDTO $dto): AcaoResponseDTO;

    public function update(Acao $acao, AcaoDTO $dto): AcaoResponseDTO;

    public function delete(Acao $acao);

    public function historicoCotacoes(Acao $acao, int $perPage = 90, int $page = 1): LengthAwarePaginator;
}

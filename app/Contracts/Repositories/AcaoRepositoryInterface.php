<?php

namespace App\Contracts\Repositories;

use App\Models\Acao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AcaoRepositoryInterface
{
    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator;

    public function show(Acao $acao): Acao;

    public function store(array $data): Acao;

    public function update(Acao $acao, array $data): Acao;

    public function delete(Acao $acao);

    public function historicoCotacoes(Acao $acao, int $perPage = 90, int $page = 1): LengthAwarePaginator;

    public function historicoDiario(Acao $acao, int $perPage = 500, int $page = 1): LengthAwarePaginator;
}

<?php

namespace App\Services;

use App\Contracts\Repositories\AcaoRepositoryInterface;
use App\Contracts\Services\AcaoServiceInterface;
use App\DTOs\AcaoDTO;
use App\DTOs\AcaoResponseDTO;
use App\DTOs\CotacaoResponseDTO;
use App\Models\Acao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AcaoService implements AcaoServiceInterface
{
    public function __construct(
        private AcaoRepositoryInterface $repository,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        $paginator = $this->repository->index($perPage, $page, $filters);

        $paginator->getCollection()->transform(function ($item) {
            return AcaoResponseDTO::fromModel($item)->toArray();
        });

        return $paginator;
    }

    public function show(Acao $acao): AcaoResponseDTO
    {
        $acao = $this->repository->show($acao);

        return AcaoResponseDTO::fromModel($acao);
    }

    public function store(AcaoDTO $dto): AcaoResponseDTO
    {
        $acao = $this->repository->store($dto->toArray());

        return AcaoResponseDTO::fromModel($acao);
    }

    public function update(Acao $acao, AcaoDTO $dto): AcaoResponseDTO
    {
        $acao = $this->repository->update($acao, $dto->toArray());

        return AcaoResponseDTO::fromModel($acao);
    }

    public function delete(Acao $acao)
    {
        return $this->repository->delete($acao);
    }

    public function historicoCotacoes(Acao $acao, int $perPage = 90, int $page = 1): LengthAwarePaginator
    {
        $paginator = $this->repository->historicoCotacoes($acao, $perPage, $page);

        $paginator->getCollection()->transform(function ($item) {
            return CotacaoResponseDTO::fromModel($item)->toArray();
        });

        return $paginator;
    }
}

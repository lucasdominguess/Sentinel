<?php

namespace App\Services;

use App\Contracts\Repositories\AporteRepositoryInterface;
use App\Contracts\Services\AporteServiceInterface;
use App\DTOs\AporteDTO;
use App\DTOs\AporteResponseDTO;
use App\DTOs\PosicaoResponseDTO;
use App\Models\Aporte;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AporteService implements AporteServiceInterface
{
    public function __construct(
        private AporteRepositoryInterface $repository,
    ) {}

    public function index(int $perPage = 15, int $page = 1, ?array $filters = []): LengthAwarePaginator
    {
        $paginator = $this->repository->index($perPage, $page, $filters);

        $paginator->getCollection()->transform(function ($item) {
            return AporteResponseDTO::fromModel($item)->toArray();
        });

        return $paginator;
    }

    public function show(Aporte $aporte): AporteResponseDTO
    {
        $aporte = $this->repository->show($aporte);

        return AporteResponseDTO::fromModel($aporte);
    }

    public function store(AporteDTO $dto): AporteResponseDTO
    {
        $data = $dto->toArray();

        // Regra de negócio: total investido é derivado de preço x quantidade
        $data['total_investido'] = $this->calcularTotal($data['preco_pago'], $data['quantidade']);

        $aporte = $this->repository->store($data);

        return AporteResponseDTO::fromModel($aporte);
    }

    public function update(Aporte $aporte, AporteDTO $dto): AporteResponseDTO
    {
        $data = $dto->toArray();

        // Recalcula o total quando preço ou quantidade mudam (usa o valor atual como base)
        if (isset($data['preco_pago']) || isset($data['quantidade'])) {
            $data['total_investido'] = $this->calcularTotal(
                $data['preco_pago'] ?? $aporte->preco_pago,
                $data['quantidade'] ?? $aporte->quantidade,
            );
        }

        $aporte = $this->repository->update($aporte, $data);

        return AporteResponseDTO::fromModel($aporte);
    }

    public function delete(Aporte $aporte)
    {
        return $this->repository->delete($aporte);
    }

    public function posicoes(): Collection
    {
        return $this->repository->posicoes()
            ->map(fn (Aporte $item) => PosicaoResponseDTO::fromModel($item)->toArray())
            ->sortByDesc('total_investido')
            ->values();
    }

    private function calcularTotal(float|string $preco, int $quantidade): float
    {
        return round((float) $preco * $quantidade, 2);
    }
}

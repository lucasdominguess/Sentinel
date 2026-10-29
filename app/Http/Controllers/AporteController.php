<?php

namespace App\Http\Controllers;

use App\Contracts\Services\AporteServiceInterface;
use App\DTOs\AporteDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\AporteCreateRequest;
use App\Http\Requests\AporteUpdateRequest;
use App\Http\Requests\FilterAporteIndexRequest;
use App\Models\Aporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AporteController extends Controller
{
    public function __construct(
        private AporteServiceInterface $service,
    ) {}

    public function index(FilterAporteIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $paginator = $this->service->index($data['perPage'], $data['page'], $data['filters']);

        return ApiResponse::paginated($paginator, 'Aportes recuperados com sucesso');
    }

    public function show(Aporte $aporte): JsonResponse
    {
        $aporteResponse = $this->service->show($aporte);

        return ApiResponse::success($aporteResponse, 'Aporte recuperado com sucesso');
    }

    public function store(AporteCreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $aporteResponse = $this->service->store(AporteDTO::fromRequest($data));

        return ApiResponse::created($aporteResponse, 'Aporte criado com sucesso');
    }

    public function update(Aporte $aporte, AporteUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $aporteResponse = $this->service->update($aporte, AporteDTO::fromRequest($data));

        return ApiResponse::success($aporteResponse, 'Aporte atualizado com sucesso');
    }

    public function destroy(Aporte $aporte): JsonResponse
    {
        $this->service->delete($aporte);

        return ApiResponse::success(null, 'Aporte deletado com sucesso');
    }
}

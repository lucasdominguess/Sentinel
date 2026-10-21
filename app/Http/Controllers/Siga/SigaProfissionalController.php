<?php

namespace App\Http\Controllers\Siga;

use App\Contracts\Services\Siga\SigaProfissionalServiceInterface;
use App\DTOs\Siga\BuscarProfissionalSigaDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siga\BuscarProfissionalSigaRequest;
use Illuminate\Http\JsonResponse;

class SigaProfissionalController extends Controller
{
    public function __construct(
        private SigaProfissionalServiceInterface $service
    ) {}

    public function buscarProfissional(BuscarProfissionalSigaRequest $request): JsonResponse
    {
        $dto = BuscarProfissionalSigaDTO::fromRequest($request->validated());

        $vinculos = $this->service->buscarProfissional($dto);

        return ApiResponse::success($vinculos, 'Consulta realizada com sucesso');
    }
}

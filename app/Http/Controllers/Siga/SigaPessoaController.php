<?php

namespace App\Http\Controllers\Siga;

use App\Contracts\Services\Siga\SigaPessoaServiceInterface;
use App\DTOs\Siga\BuscarPessoaSigaDTO;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Siga\BuscarPessoaSigaRequest;
use Illuminate\Http\JsonResponse;

class SigaPessoaController extends Controller
{
    public function __construct(
        private SigaPessoaServiceInterface $service
    ) {}

    public function buscarPessoa(BuscarPessoaSigaRequest $request): JsonResponse
    {
        $dto = BuscarPessoaSigaDTO::fromRequest($request->validated());

        $pessoas = $this->service->buscarPessoa($dto);

        return ApiResponse::success($pessoas, 'Consulta realizada com sucesso');
    }
}

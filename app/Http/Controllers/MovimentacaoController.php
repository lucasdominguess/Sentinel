<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MovimentacaoServiceInterface;
use App\DTOs\MovimentacaoDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\MovimentacaoCreateRequest;
use App\Http\Requests\MovimentacaoUpdateRequest;
use App\Http\Requests\FilterMovimentacaoIndexRequest;
use App\Models\Movimentacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class MovimentacaoController extends Controller
{
    public function __construct(
        private MovimentacaoServiceInterface $service,
    ) {}

    public function index(FilterMovimentacaoIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $paginator = $this->service->index($data['perPage'], $data['page'], $data['filters']);

        return ApiResponse::paginated($paginator, 'Movimentações recuperadas com sucesso');
    }

    public function show(Movimentacao $movimentacao): JsonResponse
    {
        $movimentacaoResponse = $this->service->show($movimentacao);

        return ApiResponse::success($movimentacaoResponse, 'Movimentação recuperada com sucesso');
    }

    public function store(MovimentacaoCreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $movimentacaoResponse = $this->service->store(MovimentacaoDTO::fromRequest($data));

        return ApiResponse::created($movimentacaoResponse, 'Movimentação criada com sucesso');
    }

    public function update(Movimentacao $movimentacao, MovimentacaoUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $movimentacaoResponse = $this->service->update($movimentacao, MovimentacaoDTO::fromRequest($data));

        return ApiResponse::success($movimentacaoResponse, 'Movimentação atualizada com sucesso');
    }

    public function destroy(Movimentacao $movimentacao): JsonResponse
    {
        $this->service->delete($movimentacao);

        return ApiResponse::success(null, 'Movimentação deletada com sucesso');
    }
}

<?php

namespace App\Http\Controllers;

use App\Contracts\Services\AcaoServiceInterface;
use App\DTOs\AcaoDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\AcaoCreateRequest;
use App\Http\Requests\AcaoUpdateRequest;
use App\Http\Requests\FilterAcaoIndexRequest;
use App\Http\Requests\FilterCotacaoIndexRequest;
use App\Models\Acao;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AcaoController extends Controller
{
    public function __construct(
        private AcaoServiceInterface $service,
    ) {}

    public function index(FilterAcaoIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $paginator = $this->service->index($data['perPage'], $data['page'], $data['filters']);

        return ApiResponse::paginated($paginator, 'Acaos recuperados com sucesso');
    }

    public function show(Acao $acao): JsonResponse
    {
        $acaoResponse = $this->service->show($acao);

        return ApiResponse::success($acaoResponse, 'Acao recuperado com sucesso');
    }

    public function store(AcaoCreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $acaoResponse = $this->service->store(AcaoDTO::fromRequest($data));

        return ApiResponse::created($acaoResponse, 'Acao criado com sucesso');
    }

    public function update(Acao $acao, AcaoUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $acaoResponse = $this->service->update($acao, AcaoDTO::fromRequest($data));

        return ApiResponse::success($acaoResponse, 'Acao atualizado com sucesso');
    }

    public function destroy(Acao $acao): JsonResponse
    {
        $this->service->delete($acao);

        return ApiResponse::success(null, 'Acao deletado com sucesso');
    }

    public function cotacoes(Acao $acao, FilterCotacaoIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $paginator = $this->service->historicoCotacoes($acao, $data['perPage'], $data['page']);

        return ApiResponse::paginated($paginator, 'Histórico de cotações recuperado com sucesso');
    }

    public function historico(Acao $acao, FilterCotacaoIndexRequest $request): JsonResponse
    {
        $data = $request->validated();
        $paginator = $this->service->historicoDiario($acao, $data['perPage'], $data['page']);

        return ApiResponse::paginated($paginator, 'Histórico diário recuperado com sucesso');
    }
}

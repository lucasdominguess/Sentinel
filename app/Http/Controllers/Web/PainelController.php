<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Services\AcaoServiceInterface;
use App\Contracts\Services\AporteServiceInterface;
use App\Contracts\Services\ColetaServiceInterface;
use App\DTOs\AcaoDTO;
use App\DTOs\AporteDTO;
use App\Enums\TipoAtivo;
use App\Http\Requests\AcaoUpdateRequest;
use App\Http\Requests\AdicionarTicketRequest;
use App\Http\Requests\AporteCreateRequest;
use App\Http\Requests\AporteUpdateRequest;
use App\Models\Acao;
use App\Models\Aporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

// Controller web (server-rendered). Reaproveita os Services do fluxo canônico —
// sem lógica de negócio aqui.
class PainelController extends Controller
{
    public function __construct(
        private AcaoServiceInterface $service,
        private AporteServiceInterface $aporteService,
        private ColetaServiceInterface $coletaService,
    ) {}

    public function index(): View
    {
        return $this->listar(TipoAtivo::ACAO, 'Ações monitoradas');
    }

    public function fiis(): View
    {
        return $this->listar(TipoAtivo::FII, 'FIIs monitorados');
    }

    public function etfs(): View
    {
        return $this->listar(TipoAtivo::ETF, 'ETFs monitorados');
    }

    public function show(Acao $acao): View
    {
        $dto = $this->service->show($acao);

        return view('acoes.show', ['acao' => $dto->toArray()]);
    }

    public function calculoCotas(): View
    {
        // Todos os ativos cadastrados, com a última cotação, para o select da calculadora
        $paginator = $this->service->index(1000, 1, []);

        $ativos = collect($paginator->items())
            ->map(fn ($a) => [
                'ticker' => $a['ticker'],
                'nome' => $a['nome'],
                'preco' => $a['ultima_cotacao']['preco'] ?? null,
                'dividendo' => $a['ultima_cotacao']['ultimo_dividendo'] ?? null,
            ])
            ->sortBy('ticker')
            ->values()
            ->all();

        return view('acoes.calculo', ['ativos' => $ativos]);
    }

    public function porcentagem(): View
    {
        return view('acoes.porcentagem');
    }

    public function formAporte(): View
    {
        return view('aportes.registrar', ['ativos' => $this->ativosParaSelect()]);
    }

    public function storeAporte(AporteCreateRequest $request): RedirectResponse
    {
        $this->aporteService->store(AporteDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.aportes.create')
            ->with('sucesso', 'Aporte registrado com sucesso.');
    }

    public function editAporte(Aporte $aporte): View
    {
        return view('aportes.editar', [
            'aporte' => $this->aporteService->show($aporte)->toArray(),
            'ativos' => $this->ativosParaSelect(),
        ]);
    }

    public function updateAporte(Aporte $aporte, AporteUpdateRequest $request): RedirectResponse
    {
        $this->aporteService->update($aporte, AporteDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.aportes.index')
            ->with('sucesso', 'Aporte atualizado com sucesso.');
    }

    public function destroyAporte(Aporte $aporte): RedirectResponse
    {
        $this->aporteService->delete($aporte);

        return redirect()
            ->route('painel.aportes.index')
            ->with('sucesso', 'Aporte excluído com sucesso.');
    }

    public function meusAtivos(): View
    {
        $posicoes = $this->aporteService->posicoes();

        return view('aportes.meus-ativos', ['posicoes' => $posicoes]);
    }

    public function aportesIndex(): View
    {
        $aportes = $this->aporteService->index(15, (int) request('page', 1));

        return view('aportes.registros', ['aportes' => $aportes]);
    }

    public function formAdicionar(): View
    {
        return view('acoes.adicionar');
    }

    public function adicionar(AdicionarTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['ativo'] = true;

        $this->service->store(AcaoDTO::fromRequest($data));

        return redirect()
            ->route('painel.adicionar')
            ->with('sucesso', "Ticker {$data['ticker']} adicionado. Será buscado no próximo update do coletor.");
    }

    public function ativosIndex(): View
    {
        // Todos os ativos cadastrados (monitorados ou não), em ordem alfabética para gestão
        $paginator = $this->service->index(1000, 1, []);

        $ativos = collect($paginator->items())
            ->sortBy('ticker')
            ->values()
            ->all();

        return view('acoes.cadastro', ['ativos' => $ativos]);
    }

    public function detalhesAtivo(Acao $acao): View
    {
        return view('acoes.detalhes', [
            'ativo' => $this->service->show($acao)->toArray(),
            'criadoEm' => $acao->created_at,
            'atualizadoEm' => $acao->updated_at,
        ]);
    }

    public function editAtivo(Acao $acao): View
    {
        return view('acoes.editar', ['ativo' => $this->service->show($acao)->toArray()]);
    }

    public function updateAtivo(Acao $acao, AcaoUpdateRequest $request): RedirectResponse
    {
        $this->service->update($acao, AcaoDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.ativos.index')
            ->with('sucesso', "Ativo {$acao->ticker} atualizado com sucesso.");
    }

    public function destroyAtivo(Acao $acao): RedirectResponse
    {
        $ticker = $acao->ticker;
        $this->service->delete($acao);

        return redirect()
            ->route('painel.ativos.index')
            ->with('sucesso', "Ativo {$ticker} excluído com sucesso.");
    }

    // Lista de ativos para o <select> dos formulários de aporte: value é o id (gravado no banco)
    private function ativosParaSelect(): array
    {
        $paginator = $this->service->index(1000, 1, []);

        return collect($paginator->items())
            ->map(fn ($a) => [
                'id' => $a['id'],
                'ticker' => $a['ticker'],
                'nome' => $a['nome'],
                'preco' => $a['ultima_cotacao']['preco'] ?? null,
            ])
            ->sortBy('ticker')
            ->values()
            ->all();
    }

    private function listar(TipoAtivo $tipo, string $titulo): View
    {
        $paginator = $this->service->index(100, 1, ['tipo' => $tipo->value]);
        $acoes = $paginator->items();

        // Horário da coleta mais recente (maior capturado_em entre os ativos exibidos)
        $ultimaColeta = collect($acoes)
            ->pluck('ultima_cotacao.capturado_em')
            ->filter()
            ->max();

        // Última execução do coletor: mostra também a que não terminou (fim nulo)
        $coleta = $this->coletaService->ultima()?->toArray();

        return view('acoes.index', [
            'acoes' => $acoes,
            'ultimaColeta' => $ultimaColeta,
            'coleta' => $coleta,
            'titulo' => $titulo,
            'tipo' => $tipo,
        ]);
    }
}

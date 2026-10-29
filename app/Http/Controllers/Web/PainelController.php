<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Services\AcaoServiceInterface;
use App\Contracts\Services\AporteServiceInterface;
use App\Contracts\Services\ColetaServiceInterface;
use App\DTOs\AcaoDTO;
use App\DTOs\AporteDTO;
use App\Enums\TipoAtivo;
use App\Http\Requests\AdicionarTicketRequest;
use App\Http\Requests\AporteCreateRequest;
use App\Models\Acao;
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
        // Mesma lista suspensa do cálculo de cotas, mas o value é o id do ativo (gravado no banco)
        $paginator = $this->service->index(1000, 1, []);

        $ativos = collect($paginator->items())
            ->map(fn ($a) => [
                'id' => $a['id'],
                'ticker' => $a['ticker'],
                'nome' => $a['nome'],
                'preco' => $a['ultima_cotacao']['preco'] ?? null,
            ])
            ->sortBy('ticker')
            ->values()
            ->all();

        return view('aportes.registrar', ['ativos' => $ativos]);
    }

    public function storeAporte(AporteCreateRequest $request): RedirectResponse
    {
        $this->aporteService->store(AporteDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.aportes.create')
            ->with('sucesso', 'Aporte registrado com sucesso.');
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

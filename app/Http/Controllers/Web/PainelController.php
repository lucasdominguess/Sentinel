<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Services\AcaoServiceInterface;
use App\DTOs\AcaoDTO;
use App\Enums\TipoAtivo;
use App\Http\Requests\AdicionarTicketRequest;
use App\Models\Acao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

// Controller web (server-rendered). Reaproveita o Service do fluxo canônico —
// sem lógica de negócio aqui.
class PainelController extends Controller
{
    public function __construct(
        private AcaoServiceInterface $service,
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

        return view('acoes.index', [
            'acoes' => $acoes,
            'ultimaColeta' => $ultimaColeta,
            'titulo' => $titulo,
            'tipo' => $tipo,
        ]);
    }
}

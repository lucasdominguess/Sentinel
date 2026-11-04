<?php

namespace App\Http\Controllers\Web;

use App\Contracts\Services\AcaoServiceInterface;
use App\Contracts\Services\CarteiraServiceInterface;
use App\Contracts\Services\MovimentacaoServiceInterface;
use App\Contracts\Services\ColetaServiceInterface;
use App\DTOs\AcaoDTO;
use App\DTOs\MovimentacaoDTO;
use App\Enums\TipoAtivo;
use App\Enums\TipoMovimentacao;
use App\Http\Requests\AcaoUpdateRequest;
use App\Http\Requests\AdicionarTicketRequest;
use App\Http\Requests\CarteiraRequest;
use App\Http\Requests\EvolucaoRequest;
use App\Http\Requests\MovimentacaoCreateRequest;
use App\Http\Requests\MovimentacaoUpdateRequest;
use App\Models\Acao;
use App\Models\Movimentacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

// Controller web (server-rendered). Reaproveita os Services do fluxo canônico —
// sem lógica de negócio aqui.
class PainelController extends Controller
{
    public function __construct(
        private AcaoServiceInterface $service,
        private MovimentacaoServiceInterface $movimentacaoService,
        private ColetaServiceInterface $coletaService,
        private CarteiraServiceInterface $carteiraService,
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

    public function formMovimentacao(): View
    {
        return view('movimentacoes.registrar', ['ativos' => $this->ativosParaSelect()]);
    }

    public function storeMovimentacao(MovimentacaoCreateRequest $request): RedirectResponse
    {
        $dto = $this->movimentacaoService->store(MovimentacaoDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.movimentacoes.create')
            ->with('sucesso', TipoMovimentacao::from($dto->tipo)->label() . " de {$dto->ticker} registrada com sucesso.");
    }

    public function editMovimentacao(Movimentacao $movimentacao): View
    {
        return view('movimentacoes.editar', [
            'movimentacao' => $this->movimentacaoService->show($movimentacao)->toArray(),
            'ativos' => $this->ativosParaSelect(),
        ]);
    }

    public function updateMovimentacao(Movimentacao $movimentacao, MovimentacaoUpdateRequest $request): RedirectResponse
    {
        $this->movimentacaoService->update($movimentacao, MovimentacaoDTO::fromRequest($request->validated()));

        return redirect()
            ->route('painel.movimentacoes.index')
            ->with('sucesso', 'Movimentação atualizada com sucesso.');
    }

    public function destroyMovimentacao(Movimentacao $movimentacao): RedirectResponse
    {
        $this->movimentacaoService->delete($movimentacao);

        return redirect()
            ->route('painel.movimentacoes.index')
            ->with('sucesso', 'Movimentação excluída com sucesso.');
    }

    public function carteira(CarteiraRequest $request): View
    {
        // Data passada: foto no fechamento daquele dia; sem data (ou hoje): ao vivo
        $data = $request->validated('data');
        $foto = $data && $data < today()->toDateString()
            ? $this->carteiraService->fotoDoDia($data)
            : null;

        return view('carteira.index', [
            'posicoes' => $foto['posicoes'] ?? $this->movimentacaoService->posicoes(),
            'data' => $foto ? $data : null,
            'pregao' => $foto['pregao'] ?? null,
            'calendario' => $this->carteiraService->calendario(),
        ]);
    }

    public function evolucao(EvolucaoRequest $request): View
    {
        $intervalo = $request->intervalo();

        return view('carteira.evolucao', [
            'evolucao' => $this->carteiraService->evolucao($intervalo['de'], $intervalo['ate']),
            'periodo' => $intervalo['periodo'],
            'periodos' => EvolucaoRequest::PERIODOS,
            'pedido' => $intervalo,
            'calendario' => $this->carteiraService->calendario(),
        ]);
    }

    public function movimentacoesIndex(): View
    {
        $movimentacoes = $this->movimentacaoService->index(15, (int) request('page', 1));

        return view('movimentacoes.index', ['movimentacoes' => $movimentacoes]);
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

    // Lista de ativos para o <select> dos formulários de movimentação: value é o id (gravado
    // no banco); 'cotas' é o saldo atual, mostrado como referência ao registrar uma venda
    private function ativosParaSelect(): array
    {
        $paginator = $this->service->index(1000, 1, []);
        $cotas = $this->movimentacaoService->posicoes()->pluck('quantidade', 'acao_id');

        return collect($paginator->items())
            ->map(fn ($a) => [
                'id' => $a['id'],
                'ticker' => $a['ticker'],
                'nome' => $a['nome'],
                'preco' => $a['ultima_cotacao']['preco'] ?? null,
                'cotas' => (int) ($cotas[$a['id']] ?? 0),
            ])
            ->sortBy('ticker')
            ->values()
            ->all();
    }

    private function listar(TipoAtivo $tipo, string $titulo): View
    {
        $paginator = $this->service->index(100, 1, ['tipo' => $tipo->value]);
        $acoes = $paginator->items();

        // Série intradia (snapshots do coletor) para o sparkline de cada card/linha
        $series = $this->service->seriesIntradia(array_column($acoes, 'id'));
        $acoes = array_map(fn ($a) => $a + ['serie' => $series[$a['id']] ?? []], $acoes);

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

{{--
  Meus ativos — posição consolidada da carteira.
  Recebe do PainelController@meusAtivos:
    $posicoes  Collection de arrays (PosicaoResponseDTO::toArray):
               ['acao_id','ticker','nome','tipo','quantidade','total_investido','preco_medio',
                'preco_atual','valor_atual','valorizacao_reais','valorizacao_pct']
  Resumo, alocação, cor e peso são derivados aqui (o controller não os envia).
--}}
@extends('layouts.monitor')

@section('title', 'Meus ativos · Sentinel')

@push('styles')
    @vite('resources/css/pages/carteira.css')
@endpush

@section('content')
    @php
        $brl = fn ($v) => number_format((float) $v, 2, ',', '.');
        $mil = fn ($v) => 'R$ ' . number_format($v / 1000, 1, ',', '.') . ' mil';

        $posicoes = collect($posicoes);
        // Sem cotação atual, o valor atual cai no total investido (igual à tela antiga)
        $atualDe = fn ($p) => $p['valor_atual'] ?? $p['total_investido'];

        $investido = (float) $posicoes->sum('total_investido');
        $atual = (float) $posicoes->sum($atualDe);
        $val = $atual - $investido;
        $pct = $investido > 0 ? $val / $investido * 100 : 0;
        $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
        $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];

        // Ranking por valor atual (desc): top 5 recebem cat-1..5; o resto, cat-rest
        $ranked = $posicoes->sortByDesc($atualDe)->values();
        $cats = ['var(--cat-1)', 'var(--cat-2)', 'var(--cat-3)', 'var(--cat-4)', 'var(--cat-5)'];
        $corDe = [];
        foreach ($ranked as $i => $p) {
            $corDe[$p['acao_id']] = $i < 5 ? $cats[$i] : 'var(--cat-rest)';
        }

        // Alocação para o donut: top 5 individuais + "Outros"
        $alocacao = [];
        foreach ($ranked as $i => $p) {
            if ($i < 5) {
                $alocacao[] = ['label' => $p['ticker'], 'value' => (float) $atualDe($p), 'color' => $cats[$i]];
            }
        }
        $restoTotal = (float) $ranked->slice(5)->sum($atualDe);
        if ($restoTotal > 0) {
            $alocacao[] = ['label' => 'Outros', 'value' => $restoTotal, 'color' => 'var(--cat-rest)'];
        }

        // Alocação por classe (FIIs, Ações, ETFs) — mesmas cores categóricas, na ordem do enum
        $alocacaoClasse = [];
        foreach ([\App\Enums\TipoAtivo::FII, \App\Enums\TipoAtivo::ACAO, \App\Enums\TipoAtivo::ETF] as $i => $t) {
            $v = (float) $posicoes->where('tipo', $t->value)->sum($atualDe);
            if ($v > 0) {
                $alocacaoClasse[] = ['label' => ['fii' => 'FIIs', 'acao' => 'Ações', 'etf' => 'ETFs'][$t->value], 'value' => $v, 'color' => $cats[$i]];
            }
        }

        $pesoDe = fn ($p) => $atual > 0 ? $atualDe($p) / $atual * 100 : 0;

        // Abas de classe com contagem
        $classes = ['' => 'Todos', 'acao' => 'Ações', 'fii' => 'FIIs', 'etf' => 'ETFs'];
        $contagem = fn ($k) => $k === '' ? $posicoes->count() : $posicoes->where('tipo', $k)->count();
    @endphp

    <section class="page-header">
        <div class="page-header__title">
            <span class="kicker">Aportes · Carteira</span>
            <h1 class="h1">Meus ativos</h1>
            <p class="page-header__lead">Posição consolidada da carteira — preço médio pago e valorização com base na última cotação.</p>
        </div>
        <div class="page-header__actions">
            <a class="btn btn-ghost" href="{{ route('painel.aportes.index') }}">Registros</a>
            <a class="btn btn-primary" href="{{ route('painel.aportes.create') }}"><span aria-hidden="true">+</span> Registrar aporte</a>
        </div>
    </section>

    @if ($posicoes->isEmpty())
        <div class="empty" style="margin-top:28px">
            <div class="empty__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9v9z"/><path d="M12 3a9 9 0 0 1 9 9h-9z"/></svg>
            </div>
            <h2>Nenhum ativo na carteira</h2>
            <p>Registre seu primeiro aporte para acompanhar preço médio, valor atual e valorização.</p>
            <a class="btn btn-primary" href="{{ route('painel.aportes.create') }}">Registrar aporte</a>
        </div>
    @else
        <div class="portfolio-summary">
            <section class="card summary" aria-label="Resumo da carteira">
                <dl style="margin:0">
                    <div class="kpi kpi--hero"><dt>Valor atual</dt><dd><span class="price__cur">R$</span>{{ $brl($atual) }}</dd></div>
                </dl>
                <dl class="summary__split">
                    <div class="kpi"><dt>Total investido</dt><dd>R$ {{ $brl($investido) }}</dd></div>
                    <div class="kpi"><dt>Valorização</dt><dd class="tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($val)) }} <x-badge-var :pct="$pct" /></dd></div>
                </dl>
                <x-cost-bar size="lg" :cost="$investido" :value="$atual"
                            left="Valor atual = {{ number_format($atual / max($investido, 1e-9) * 100, 1, ',', '.') }}% do custo" right="Custo" />
            </section>

            <section class="card allocation" aria-label="Alocação da carteira">
                <div class="allocation__head">
                    <span class="label">Alocação</span>
                    <nav class="seg seg--sm" aria-label="Agrupar alocação">
                        <button type="button" data-group="ativo" aria-pressed="true">Ativo</button>
                        <button type="button" data-group="classe" aria-pressed="false">Classe</button>
                    </nav>
                </div>
                <div data-group-pane="ativo">
                    <x-donut :segments="$alocacao" :center-value="$mil($atual)" center-label="Valor atual" label="Alocação por ativo" />
                </div>
                <div data-group-pane="classe" hidden>
                    <x-donut :segments="$alocacaoClasse" :center-value="$mil($atual)" center-label="Valor atual" label="Alocação por classe" />
                </div>
            </section>
        </div>

        <div class="toolbar toolbar--spaced">
            <nav class="seg" aria-label="Filtrar por classe">
                @foreach ($classes as $k => $label)
                    <button type="button" data-classe="{{ $k }}" aria-pressed="{{ $k === '' ? 'true' : 'false' }}" @if($k !== '' && !$contagem($k)) disabled @endif>{{ $label }}<span class="seg__count">{{ $contagem($k) }}</span></button>
                @endforeach
            </nav>
            <div class="toolbar__end toolbar__end--sort">
                <label class="select">
                    <span class="select__prefix">Ordenar</span>
                    <select aria-label="Ordenar posições" data-order>
                        <option value="posicao">Maior posição</option>
                        <option value="pior">Pior valorização</option>
                        <option value="melhor">Melhor valorização</option>
                        <option value="az">Ticker A–Z</option>
                    </select>
                </label>
                <x-view-toggle />
            </div>
        </div>

        <p class="results-meta" data-count-pos>{{ $posicoes->count() }} {{ $posicoes->count() === 1 ? 'posição' : 'posições' }}</p>

        <div class="asset-grid" id="grid">
            @foreach ($ranked as $p)
                <x-holding-card :p="$p" :cor="$corDe[$p['acao_id']]" :peso="$pesoDe($p)" />
            @endforeach
        </div>

        <div class="card asset-table" role="table" aria-label="Posições">
            <div class="asset-row asset-row--head holding-row" role="row">
                <span role="columnheader">Ativo</span>
                <span role="columnheader">Classe</span>
                <span role="columnheader" class="r">Qtd.</span>
                <span role="columnheader" class="r">Preço médio</span>
                <span role="columnheader" class="r">Preço atual</span>
                <span role="columnheader" class="r">Investido</span>
                <span role="columnheader" class="r">Valor atual</span>
                <span role="columnheader" class="r">Valorização</span>
                <span role="columnheader">Peso</span>
            </div>
            @foreach ($ranked as $p)
                <x-holding-row :p="$p" :cor="$corDe[$p['acao_id']]" :peso="$pesoDe($p)" />
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/carteira.js')
@endpush

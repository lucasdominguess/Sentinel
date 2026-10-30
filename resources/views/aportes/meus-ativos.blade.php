{{--
  Meus ativos — posição consolidada da carteira.
  Recebe do PainelController@meusAtivos:
    $posicoes  Collection de arrays (PosicaoResponseDTO::toArray):
               ['acao_id','ticker','nome','quantidade','total_investido','preco_medio',
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

        $pesoDe = fn ($p) => $atual > 0 ? $atualDe($p) / $atual * 100 : 0;
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
                <div class="allocation__head"><span class="label">Alocação por ativo</span></div>
                <x-donut :segments="$alocacao" :center-value="$mil($atual)" center-label="Valor atual" />
            </section>
        </div>

        <div class="toolbar toolbar--spaced">
            <nav class="seg" aria-label="Ordenar posições">
                <button type="button" data-sort="posicao" aria-pressed="true">Maior posição</button>
                <button type="button" data-sort="pior" aria-pressed="false">Pior valorização</button>
                <button type="button" data-sort="melhor" aria-pressed="false">Melhor valorização</button>
                <button type="button" data-sort="az" aria-pressed="false">Ticker A–Z</button>
            </nav>
        </div>

        <p class="results-meta">{{ $posicoes->count() }} {{ $posicoes->count() === 1 ? 'posição' : 'posições' }}</p>

        <div class="asset-grid" id="grid">
            @foreach ($ranked as $p)
                <x-holding-card :p="$p" :cor="$corDe[$p['acao_id']]" :peso="$pesoDe($p)" />
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/carteira.js')
@endpush

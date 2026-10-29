@extends('layouts.app')

@section('title', $titulo . ' — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-index.css')
@endpush

@section('content')
    @php
        // Resumo do topo: quantos ativos em alta / queda no dia
        $upCount = 0;
        $downCount = 0;
        foreach ($acoes as $a) {
            $p = $a['ultima_cotacao']['variacao_dia_pct'] ?? null;
            if ($p !== null) {
                if ((float) $p > 0) {
                    $upCount++;
                } elseif ((float) $p < 0) {
                    $downCount++;
                }
            }
        }
        $atualizadoEm = $coleta['inicio'] ?? $ultimaColeta ?? null;
        $atualizadoTxt = $atualizadoEm ? \Illuminate\Support\Carbon::parse($atualizadoEm)->format('d/m · H:i') : '—';
    @endphp

    <div class="page-head">
        <div>
            <div class="kicker">Monitor · Renda variável</div>
            <h1>{{ $titulo }}</h1>
        </div>
        <div class="head-stats">
            <div class="head-stat">
                <span class="k">Ativos</span>
                <span class="v">{{ count($acoes) }}</span>
            </div>
            <div class="head-stat">
                <span class="k">Alta / Queda</span>
                <span class="v"><span class="up">{{ $upCount }}</span> / <span class="down">{{ $downCount }}</span></span>
            </div>
            <div class="head-stat">
                <span class="k">Atualizado</span>
                <span class="v">
                    {{ $atualizadoTxt }}
                    @if ($coleta)
                        @if (!$coleta['concluida'])
                            <span class="badge badge--down" title="A execução começou e nunca registrou o fim">interrompida</span>
                        @elseif ($coleta['com_falha'])
                            <span class="badge badge--down">{{ $coleta['ativos_falha'] }} falha(s)</span>
                        @else
                            <span class="badge badge--up">{{ $coleta['ativos_ok'] }} ok</span>
                        @endif
                    @endif
                </span>
            </div>
        </div>
    </div>

    @if (count($acoes) === 0)
        <div class="card empty">
            <p>Nenhum ativo cadastrado neste monitor.</p>
            <p class="muted">Use <a href="{{ route('painel.adicionar') }}">Adicionar ticket</a> e rode o coletor (<code>fetch_stocks.py</code>) para buscar as cotações.</p>
        </div>
    @else
        <div class="toolbar">
            <div class="seg sort-pills" role="group" aria-label="Ordenar ativos">
                <button type="button" class="seg-opt pill is-active" data-sort="alta">Maior alta %</button>
                <button type="button" class="seg-opt pill" data-sort="queda">Maior queda %</button>
                <button type="button" class="seg-opt pill" data-sort="valor">Maior valor</button>
                <button type="button" class="seg-opt pill" data-sort="menor">Menor valor</button>
            </div>
            <span class="toolbar__spacer"></span>
            <div class="search-wrap">
                <svg class="search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="busca" class="input search" type="search" placeholder="Filtrar por ticker ou nome…" autocomplete="off">
            </div>
        </div>

        <div class="acoes-grid" id="grid">
            @foreach ($acoes as $acao)
                @php
                    $c = $acao['ultima_cotacao'];
                    $pct = isset($c['variacao_dia_pct']) ? (float) $c['variacao_dia_pct'] : null;
                    $cls = $pct === null ? 'badge--flat' : ($pct > 0 ? 'badge--up' : ($pct < 0 ? 'badge--down' : 'badge--flat'));
                    $arrow = $pct === null ? '' : ($pct > 0 ? '▲' : ($pct < 0 ? '▼' : '•'));
                    $varRs = isset($c['variacao_dia']) ? (float) $c['variacao_dia'] : null;
                    $fmt = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.');
                    // 0/null = sem dado (ex.: coleta pré-pregão) → exibe "—"
                    $stat = fn($v) => $v === null || (float) $v == 0.0 ? '—' : 'R$ ' . number_format((float) $v, 2, ',', '.');

                    // Barra de faixa do dia (mín/máx) com marca de abertura e bolinha do preço
                    $minDia = isset($c['minima_dia']) ? (float) $c['minima_dia'] : 0.0;
                    $maxDia = isset($c['maxima_dia']) ? (float) $c['maxima_dia'] : 0.0;
                    $temFaixa = $c && $maxDia > $minDia && $minDia > 0;
                    $posPct = function ($v) use ($minDia, $maxDia) {
                        if ($v === null || $maxDia <= $minDia) return 50.0;
                        return max(0.0, min(100.0, (((float) $v - $minDia) / ($maxDia - $minDia)) * 100.0));
                    };
                @endphp
                <a class="card acao-card"
                   href="{{ route('painel.show', $acao['id']) }}"
                   data-search="{{ mb_strtolower($acao['ticker'] . ' ' . ($acao['nome'] ?? '')) }}"
                   data-variacao="{{ $pct ?? '' }}"
                   data-preco="{{ $c ? $c['preco'] : '' }}"
                   data-min-mes="{{ $c ? $c['minima_mes'] : '' }}"
                   data-min-mes-data="{{ $c ? $c['minima_mes_data'] : '' }}"
                   data-max-mes="{{ $c ? $c['maxima_mes'] : '' }}"
                   data-max-mes-data="{{ $c ? $c['maxima_mes_data'] : '' }}">
                    <div class="acao-card__top">
                        <span class="acao-card__ticker">{{ $acao['ticker'] }}</span>
                        <span class="badge {{ $cls }}">{{ $arrow }} {{ $pct !== null ? $fmt($pct) . '%' : '—' }}</span>
                    </div>
                    <div class="acao-card__nome" title="{{ $acao['nome'] ?? '' }}">{{ $acao['nome'] ?? '—' }}</div>
                    <div class="acao-card__preco">
                        @if ($c)
                            <span class="valor"><span class="cifrao">R$</span>{{ $fmt($c['preco']) }}</span>
                            @if ($varRs !== null)
                                <span class="var-rs {{ $varRs > 0 ? 'up' : ($varRs < 0 ? 'down' : '') }}">{{ $varRs > 0 ? '+' : ($varRs < 0 ? '−' : '') }}R$ {{ number_format(abs($varRs), 2, ',', '.') }}</span>
                            @endif
                        @else
                            <span class="muted">sem cotação</span>
                        @endif
                    </div>

                    @if ($temFaixa)
                        <div class="acao-card__range">
                            <div class="range-track">
                                @if ($c['abertura'] !== null && (float) $c['abertura'] > 0)
                                    <span class="range-open" style="left:{{ $posPct($c['abertura']) }}%" title="Abertura"></span>
                                @endif
                                <span class="range-dot" style="left:{{ $posPct($c['preco']) }}%"></span>
                            </div>
                            <div class="range-labels">
                                <span>Mín. {{ $stat($c['minima_dia']) }}</span>
                                <span>Máx. {{ $stat($c['maxima_dia']) }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($c)
                        <div class="acao-card__foot">
                            <div><span class="k">Fech. ant.</span><span class="v">{{ $stat($c['fechamento_anterior']) }}</span></div>
                            <div><span class="k">Abertura</span><span class="v">{{ $stat($c['abertura']) }}</span></div>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
        <p class="no-results muted" id="no-results" hidden>Nenhum ativo corresponde à busca.</p>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-index.js')
@endpush

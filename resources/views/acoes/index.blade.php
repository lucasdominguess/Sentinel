@extends('layouts.app')

@section('title', $titulo . ' — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-index.css')
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $titulo }}</h1>
            <p class="muted">
                {{ count($acoes) }} ativo(s)
                @if ($ultimaColeta)
                    • 🕒 atualizado em {{ \Illuminate\Support\Carbon::parse($ultimaColeta)->format('d/m/Y H:i') }}
                @endif
            </p>
        </div>
    </div>

    @if (count($acoes) > 0)
        <div class="toolbar">
            <div class="sort-pills" role="group" aria-label="Ordenar ativos">
                <button type="button" class="pill is-active" data-sort="alta">Maior alta %</button>
                <button type="button" class="pill" data-sort="queda">Maior queda %</button>
                <button type="button" class="pill" data-sort="valor">Maior valor</button>
                <button type="button" class="pill" data-sort="menor">Menor valor</button>
            </div>
            <input id="busca" class="search" type="search" placeholder="Filtrar por ticker ou nome…" autocomplete="off">
        </div>
    @endif

    @if (count($acoes) === 0)
        <div class="card empty">
            <p>Nenhum ativo cadastrado neste monitor.</p>
            <p class="muted">Use <a href="{{ route('painel.adicionar') }}">Adicionar ticket</a> e rode o coletor (<code>fetch_stocks.py</code>) para buscar as cotações.</p>
        </div>
    @else
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
                        <span class="acao-card__var">
                            <span class="badge {{ $cls }}">{{ $arrow }} {{ $pct !== null ? $fmt($pct) . '%' : '—' }}</span>
                            @if ($varRs !== null)
                                <span class="var-rs {{ $varRs > 0 ? 'up' : ($varRs < 0 ? 'down' : '') }}">{{ $varRs > 0 ? '+' : ($varRs < 0 ? '−' : '') }}R$ {{ number_format(abs($varRs), 2, ',', '.') }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="acao-card__nome">{{ $acao['nome'] ?? '—' }}</div>
                    <div class="acao-card__preco">
                        @if ($c)
                            <span class="valor">R$ {{ $fmt($c['preco']) }}</span>
                        @else
                            <span class="muted">sem cotação</span>
                        @endif
                    </div>
                    @if ($c)
                        <div class="acao-card__stats">
                            <div><span class="k">Fech. ant.</span><span class="v">{{ $stat($c['fechamento_anterior']) }}</span></div>
                            <div><span class="k">Abertura</span><span class="v">{{ $stat($c['abertura']) }}</span></div>
                            <div><span class="k">Mín. dia</span><span class="v">{{ $stat($c['minima_dia']) }}</span></div>
                            <div><span class="k">Máx. dia</span><span class="v">{{ $stat($c['maxima_dia']) }}</span></div>
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

@extends('layouts.app')

@section('title', $acao['ticker'] . ' — Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-show.css')
@endpush

@section('content')
    @php
        $c = $acao['ultima_cotacao'];
        $pct = isset($c['variacao_dia_pct']) ? (float) $c['variacao_dia_pct'] : null;
        $cls = $pct === null ? 'badge--flat' : ($pct > 0 ? 'badge--up' : ($pct < 0 ? 'badge--down' : 'badge--flat'));
        $arrow = $pct === null ? '' : ($pct > 0 ? '▲' : ($pct < 0 ? '▼' : '•'));
        $fmt = fn($v, $d = 2) => $v === null ? '—' : number_format((float) $v, $d, ',', '.');
        $int = fn($v) => $v === null ? '—' : number_format((int) $v, 0, ',', '.');
    @endphp

    <a href="{{ route('painel.index') }}" class="back-link">← Ações</a>

    <header class="acao-header">
        <div class="acao-header__id">
            <h1>{{ $acao['ticker'] }}</h1>
            <p class="nome">{{ $acao['nome'] ?? '—' }}</p>
            <p class="muted small">{{ $acao['setor'] ?? '—' }} · {{ $acao['segmento'] ?? '—' }}</p>
        </div>
        <div class="acao-header__price">
            @if ($c)
                <span class="preco">R$ {{ $fmt($c['preco']) }}</span>
                <span class="badge {{ $cls }}">{{ $arrow }} {{ $pct !== null ? $fmt($pct) . '%' : '—' }}</span>
                <span class="muted small">{{ $c['estado_mercado'] ?? '' }}</span>
            @else
                <span class="muted">Sem cotação coletada</span>
            @endif
        </div>
    </header>

    <section class="card chart-card">
        <div class="chart-card__head">
            <h2>Evolução do preço</h2>
            <span class="muted small" id="chart-range"></span>
        </div>
        <div id="chart" class="chart" data-endpoint="{{ url('api/acao/' . $acao['id'] . '/cotacoes') }}"></div>
        <p id="chart-empty" class="muted chart-empty" hidden>
            Sem histórico suficiente ainda — o gráfico se preenche conforme o coletor roda ao longo do tempo.
        </p>
    </section>

    @if ($c)
        <section class="card detalhes">
            <h2>Última cotação</h2>
            <dl class="detalhes__grid">
                <div><dt>Abertura</dt><dd>R$ {{ $fmt($c['abertura']) }}</dd></div>
                <div><dt>Fech. anterior</dt><dd>R$ {{ $fmt($c['fechamento_anterior']) }}</dd></div>
                <div><dt>Mínima do dia</dt><dd>R$ {{ $fmt($c['minima_dia']) }}</dd></div>
                <div><dt>Máxima do dia</dt><dd>R$ {{ $fmt($c['maxima_dia']) }}</dd></div>
                <div><dt>Mínima 52s</dt><dd>R$ {{ $fmt($c['minima_52s']) }}</dd></div>
                <div><dt>Máxima 52s</dt><dd>R$ {{ $fmt($c['maxima_52s']) }}</dd></div>
                <div><dt>Mínima do mês</dt><dd>{{ $c['minima_mes'] !== null ? 'R$ ' . $fmt($c['minima_mes']) : '—' }}@if ($c['minima_mes_data']) <span class="muted">({{ \Illuminate\Support\Carbon::parse($c['minima_mes_data'])->format('d/m') }})</span>@endif</dd></div>
                <div><dt>Máxima do mês</dt><dd>{{ $c['maxima_mes'] !== null ? 'R$ ' . $fmt($c['maxima_mes']) : '—' }}@if ($c['maxima_mes_data']) <span class="muted">({{ \Illuminate\Support\Carbon::parse($c['maxima_mes_data'])->format('d/m') }})</span>@endif</dd></div>
                <div><dt>Volume</dt><dd>{{ $int($c['volume']) }}</dd></div>
                <div><dt>Volume médio</dt><dd>{{ $int($c['volume_medio']) }}</dd></div>
                <div><dt>Valor de mercado</dt><dd>R$ {{ $fmt($c['valor_mercado']) }}</dd></div>
                <div><dt>Dividend Yield</dt><dd>{{ $fmt($c['dividend_yield']) }}%</dd></div>
                <div><dt>Último provento</dt><dd>{{ $c['ultimo_dividendo'] !== null ? 'R$ ' . $fmt($c['ultimo_dividendo']) : '—' }}</dd></div>
                <div><dt>Data do provento</dt><dd>{{ $c['ultimo_dividendo_data'] ? \Illuminate\Support\Carbon::parse($c['ultimo_dividendo_data'])->format('d/m/Y') : '—' }}</dd></div>
                <div><dt>P/L</dt><dd>{{ $fmt($c['preco_lucro']) }}</dd></div>
                <div><dt>Coletado em</dt><dd>{{ $c['capturado_em'] ? \Illuminate\Support\Carbon::parse($c['capturado_em'])->format('d/m/Y H:i') : '—' }}</dd></div>
            </dl>
        </section>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-show.js')
@endpush

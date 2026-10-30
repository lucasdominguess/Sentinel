{{--
  Lista de ativos monitorados (Ações / FIIs / ETFs — mesma view, $tipo define o título).
  Recebe do PainelController@listar:
    $acoes        array de ativos: ['id','ticker','nome','ultima_cotacao'=>[...]]
    $coleta       ?array da última execução do coletor: ['inicio','concluida','com_falha','ativos_ok','ativos_falha']
    $ultimaColeta ?string maior capturado_em entre os ativos
    $titulo       string
    $tipo         App\Enums\TipoAtivo
--}}
@extends('layouts.monitor')

@section('title', $titulo . ' · Sentinel')

@push('styles')
    @vite('resources/css/pages/acoes-monitor.css')
@endpush

@section('content')
    @php
        $total = count($acoes);

        // Resumo: quantos em alta / queda no dia
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
        $atualizado = $atualizadoEm ? \Illuminate\Support\Carbon::parse($atualizadoEm) : null;

        $kicker = match ($tipo) {
            \App\Enums\TipoAtivo::FII => 'Monitor · Fundos imobiliários',
            \App\Enums\TipoAtivo::ETF => 'Monitor · ETFs',
            default => 'Monitor · Renda variável',
        };
    @endphp

    <section class="page-header">
        <div class="page-header__title">
            <span class="kicker">{{ $kicker }}</span>
            <h1 class="h1">{{ $titulo }}</h1>
        </div>
        <dl class="stats">
            <div class="stat"><dt>Ativos</dt><dd>{{ $total }}</dd></div>
            <div class="stat">
                <dt>Alta / Queda</dt>
                <dd>
                    <span class="tone-up"><span class="badge__arrow" aria-hidden="true">▲</span> {{ $upCount }}<span class="sr-only"> em alta</span></span>
                    <span class="tone-flat" aria-hidden="true">/</span>
                    <span class="tone-down"><span class="badge__arrow" aria-hidden="true">▼</span> {{ $downCount }}<span class="sr-only"> em queda</span></span>
                </dd>
                <div class="breadth" aria-hidden="true">
                    <span class="breadth__up" style="flex: {{ $upCount }}"></span>
                    <span class="breadth__down" style="flex: {{ $downCount }}"></span>
                </div>
            </div>
            <div class="stat">
                <dt>Atualizado</dt>
                <dd>
                    @if ($atualizado)
                        <time datetime="{{ $atualizado->toIso8601String() }}">{{ $atualizado->format('d/m · H:i') }}</time>
                    @else
                        <span class="tone-flat">—</span>
                    @endif
                    @if ($coleta)
                        @if (!$coleta['concluida'])
                            <span class="chip down" title="A execução começou e nunca registrou o fim">interrompida</span>
                        @elseif ($coleta['com_falha'])
                            <span class="chip down">{{ $coleta['ativos_falha'] }} falha(s)</span>
                        @else
                            <span class="chip up" title="{{ $coleta['ativos_ok'] }} cotações atualizadas">{{ $coleta['ativos_ok'] }} ok</span>
                        @endif
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    @if ($total === 0)
        <div class="empty">
            <div class="empty__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 16 16"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <h2>Nenhum ativo cadastrado neste monitor</h2>
            <p>Use <strong>Adicionar ticket</strong> e rode o coletor para buscar as cotações.</p>
            <a class="btn btn-primary" href="{{ route('painel.adicionar') }}">Adicionar ticket</a>
        </div>
    @else
        <div class="toolbar">
            <nav class="seg" aria-label="Ordenar ativos">
                <button type="button" data-sort="alta" aria-pressed="true">Maior alta %</button>
                <button type="button" data-sort="queda" aria-pressed="false">Maior queda %</button>
                <button type="button" data-sort="valor" aria-pressed="false">Maior valor</button>
                <button type="button" data-sort="menor" aria-pressed="false">Menor valor</button>
            </nav>
            <div class="toolbar__end">
                <div class="search">
                    <svg class="search__icon" width="15" height="15" viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <input type="search" placeholder="Filtrar por ticker ou nome…" aria-label="Filtrar ativos" autocomplete="off" data-filter>
                    <kbd class="kbd" aria-hidden="true">/</kbd>
                </div>
            </div>
        </div>

        <p class="results-meta" data-count data-total="{{ $total }}">{{ $total }} de {{ $total }} ativos</p>

        {{-- Vazio de busca: o sentinel.js alterna a visibilidade conforme o filtro --}}
        <div class="empty" data-empty hidden>
            <div class="empty__icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 16 16"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <h2>Nenhum ativo encontrado</h2>
            <p>Nada corresponde a “<span data-empty-term></span>”. Confira o ticker ou limpe o filtro.</p>
            <button class="btn btn-primary" type="button" data-filter-clear>Limpar filtro</button>
        </div>

        <div class="asset-grid" id="grid">
            @foreach ($acoes as $acao)
                <x-asset-card :a="$acao" />
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/pages/acoes-monitor.js')
@endpush

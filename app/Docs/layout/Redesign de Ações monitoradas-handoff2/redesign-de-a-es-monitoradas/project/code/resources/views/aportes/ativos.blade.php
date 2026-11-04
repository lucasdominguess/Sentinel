{{--
  Espera do controller:
  $posicoes   Collection (ver holding-card) — já com 'cor' e 'peso' calculados
  $resumo     ['investido'=>21067.56, 'atual'=>18737.74]
  $alocacao   ['ativo' => [[label,value,color],…], 'classe' => [...]]   (top 5 + "Outros")
  $classe     'todos'|'acoes'|'fiis'|'etfs'     $contagem ['todos'=>8,'acoes'=>2,'fiis'=>4,'etfs'=>2]
  $ordem      'posicao'|'pior'|'melhor'|'az'    $view 'grade'|'lista'    $agrupar 'ativo'|'classe'
--}}
@extends('layouts.app')
@section('title', 'Meus ativos · Sentinel')

@section('content')
@php
  $brl = fn ($v) => number_format($v, 2, ',', '.');
  $val = $resumo['atual'] - $resumo['investido'];
  $pct = $resumo['investido'] > 0 ? $val / $resumo['investido'] * 100 : 0;
  $trend = $val > 0.004 ? 'up' : ($val < -0.004 ? 'down' : 'flat');
  $sinal = ['up' => '+', 'down' => '−', 'flat' => ''][$trend];
  $qs = fn (array $p) => request()->fullUrlWithQuery($p);
  $classes = ['todos' => 'Todos', 'acoes' => 'Ações', 'fiis' => 'FIIs', 'etfs' => 'ETFs'];
  $ordens = ['posicao' => 'Maior posição', 'pior' => 'Pior valorização', 'melhor' => 'Melhor valorização', 'az' => 'Ticker A–Z'];
  $mil = fn ($v) => 'R$ ' . number_format($v / 1000, 1, ',', '.') . ' mil';
@endphp

<section class="page-header">
  <div class="page-header__title">
    <span class="kicker">Aportes · Carteira</span>
    <h1 class="h1">Meus ativos</h1>
    <p class="page-header__lead">Posição consolidada da carteira — preço médio pago e valorização com base na última cotação.</p>
  </div>
  <div class="page-header__actions">
    <a class="btn btn-ghost" href="{{ route('aportes.index') }}">Registros</a>
    <a class="btn btn-primary" href="{{ route('aportes.create') }}"><span aria-hidden="true">+</span> Registrar aporte</a>
  </div>
</section>

@if ($posicoes->isEmpty() && $classe === 'todos')
  <div class="empty" style="margin-top:28px">
    <div class="empty__icon" aria-hidden="true"></div>
    <h2>Nenhum ativo na carteira</h2>
    <p>Registre seu primeiro aporte para acompanhar preço médio, valor atual e valorização.</p>
    <a class="btn btn-primary" href="{{ route('aportes.create') }}">Registrar aporte</a>
  </div>
@else
  <div class="portfolio-summary">
    <section class="card summary" aria-label="Resumo">
      <dl style="margin:0">
        <div class="kpi kpi--hero"><dt>Valor atual</dt><dd><span class="price__cur">R$</span>{{ $brl($resumo['atual']) }}</dd></div>
      </dl>
      <dl class="summary__split">
        <div class="kpi"><dt>Total investido</dt><dd>R$ {{ $brl($resumo['investido']) }}</dd></div>
        <div class="kpi"><dt>Valorização</dt><dd class="tone-{{ $trend }}">{{ $sinal }}R$ {{ $brl(abs($val)) }} <x-badge-var :pct="$pct" /></dd></div>
      </dl>
      <x-cost-bar size="lg" :cost="$resumo['investido']" :value="$resumo['atual']"
                  left="Valor atual = {{ number_format($resumo['atual'] / max($resumo['investido'], 1e-9) * 100, 1, ',', '.') }}% do custo" right="Custo" />
    </section>

    <section class="card allocation" aria-label="Alocação">
      <div class="allocation__head">
        <span class="label">Alocação</span>
        <nav class="seg seg--sm" aria-label="Agrupar alocação">
          <a href="{{ $qs(['agrupar' => 'ativo']) }}" @if($agrupar === 'ativo') aria-current="true" @endif>Ativo</a>
          <a href="{{ $qs(['agrupar' => 'classe']) }}" @if($agrupar === 'classe') aria-current="true" @endif>Classe</a>
        </nav>
      </div>
      <x-donut :segments="$alocacao[$agrupar]" :center-value="$mil($resumo['atual'])" center-label="Valor atual" />
    </section>
  </div>

  <div class="toolbar toolbar--spaced">
    <nav class="seg" aria-label="Filtrar por classe">
      @foreach ($classes as $k => $label)
        <a href="{{ $qs(['classe' => $k]) }}" @if($classe === $k) aria-current="true" @endif>{{ $label }}<span class="seg__count">{{ $contagem[$k] }}</span></a>
      @endforeach
    </nav>
    <div class="toolbar__end toolbar__end--sort">
      <form class="select" method="GET">
        @foreach (request()->except('ordem') as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
        <span class="select__prefix">Ordenar</span>
        <select name="ordem" aria-label="Ordenar posições" onchange="this.form.submit()">
          @foreach ($ordens as $k => $label)
            <option value="{{ $k }}" @selected($ordem === $k)>{{ $label }}</option>
          @endforeach
        </select>
      </form>
      <div class="seg seg--icon" role="group" aria-label="Visualização" style="display:none" data-desktop-only>
        <a href="{{ $qs(['view' => 'grade']) }}" aria-label="Grade" @if($view === 'grade') aria-current="true" @endif>
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="1" width="5" height="5" rx="1.2"/><rect x="8" y="1" width="5" height="5" rx="1.2"/><rect x="1" y="8" width="5" height="5" rx="1.2"/><rect x="8" y="8" width="5" height="5" rx="1.2"/></svg>
        </a>
        <a href="{{ $qs(['view' => 'lista']) }}" aria-label="Lista" @if($view === 'lista') aria-current="true" @endif>
          <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="2" width="12" height="1.6" rx=".8"/><rect x="1" y="6.2" width="12" height="1.6" rx=".8"/><rect x="1" y="10.4" width="12" height="1.6" rx=".8"/></svg>
        </a>
      </div>
    </div>
  </div>

  <p class="results-meta">{{ $posicoes->count() }} {{ $posicoes->count() === 1 ? 'posição' : 'posições' }}</p>

  @if ($view === 'lista')
    <div class="card asset-table" role="table" aria-label="Posições">
      <div class="asset-row asset-row--head holding-row" role="row">
        <span role="columnheader">Ativo</span><span role="columnheader">Classe</span>
        <span class="r" role="columnheader">Qtd.</span><span class="r" role="columnheader">Preço médio</span><span class="r" role="columnheader">Preço atual</span>
        <span class="r" role="columnheader">Investido</span><span class="r" role="columnheader">Valor atual</span><span class="r" role="columnheader">Valorização</span>
        <span role="columnheader">Peso</span>
      </div>
      @foreach ($posicoes as $p) <x-holding-row :p="$p" /> @endforeach
    </div>
  @endif

  <div class="asset-grid" @if($view === 'lista') data-desktop-hidden @endif>
    @foreach ($posicoes as $p) <x-holding-card :p="$p" /> @endforeach
  </div>
@endif

<style>
  @media (min-width: 1024px) { [data-desktop-only] { display: flex !important; } [data-desktop-hidden] { display: none; } }
</style>
@endsection

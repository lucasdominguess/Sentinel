{{--
  Espera do controller:
  $ativos      Collection de objetos: ticker, nome, preco, variacao, variacao_pct, min, max, fech_ant, abertura, serie (array<float>)
  $resumo      ['total'=>45, 'alta'=>11, 'queda'=>34, 'atualizado'=>Carbon, 'ok'=>53, 'status'=>'ok'|'erro'|'carregando']
  $ordem       'alta'|'queda'|'maior'|'menor'
  $view        'grade'|'lista'
  $q           string
--}}
@extends('layouts.app')
@section('title', 'Ações monitoradas · Sentinel')

@section('content')
@php
  $ordens = ['alta' => 'Maior alta %', 'queda' => 'Maior queda %', 'maior' => 'Maior valor', 'menor' => 'Menor valor'];
  $qs = fn (array $p) => request()->fullUrlWithQuery($p);
@endphp

<section class="page-header">
  <div class="page-header__title">
    <span class="kicker">Monitor · Renda variável</span>
    <h1 class="h1">Ações monitoradas</h1>
  </div>
  <dl class="stats">
    <div class="stat"><dt>Ativos</dt><dd>{{ $resumo['total'] }}</dd></div>
    <div class="stat">
      <dt>Alta / Queda</dt>
      <dd>
        <span class="tone-up"><span class="badge__arrow" aria-hidden="true">▲</span> {{ $resumo['alta'] }}<span class="sr-only"> em alta</span></span>
        <span class="tone-flat" aria-hidden="true">/</span>
        <span class="tone-down"><span class="badge__arrow" aria-hidden="true">▼</span> {{ $resumo['queda'] }}<span class="sr-only"> em queda</span></span>
      </dd>
      <div class="breadth" aria-hidden="true">
        <span class="breadth__up" style="flex: {{ $resumo['alta'] }}"></span>
        <span class="breadth__down" style="flex: {{ $resumo['queda'] }}"></span>
      </div>
    </div>
    <div class="stat">
      <dt>Atualizado</dt>
      <dd>
        <time datetime="{{ $resumo['atualizado']->toIso8601String() }}">{{ $resumo['atualizado']->format('d/m · H:i') }}</time>
        @switch($resumo['status'])
          @case('erro') <span class="chip down">falha</span> @break
          @case('carregando') <span class="chip accent">atualizando</span> @break
          @default <span class="chip up" title="{{ $resumo['ok'] }} cotações atualizadas">{{ $resumo['ok'] }} ok</span>
        @endswitch
      </dd>
    </div>
  </dl>
</section>

<div class="toolbar">
  <nav class="seg" aria-label="Ordenar por">
    @foreach ($ordens as $k => $label)
      <a href="{{ $qs(['ordem' => $k]) }}" @if($ordem === $k) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
  </nav>
  <div class="toolbar__end">
    <div class="seg seg--icon" role="group" aria-label="Visualização" style="display:none" data-desktop-only>
      <a href="{{ $qs(['view' => 'grade']) }}" aria-label="Grade" @if($view === 'grade') aria-current="true" @endif>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="1" width="5" height="5" rx="1.2"/><rect x="8" y="1" width="5" height="5" rx="1.2"/><rect x="1" y="8" width="5" height="5" rx="1.2"/><rect x="8" y="8" width="5" height="5" rx="1.2"/></svg>
      </a>
      <a href="{{ $qs(['view' => 'lista']) }}" aria-label="Lista" @if($view === 'lista') aria-current="true" @endif>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="1" y="2" width="12" height="1.6" rx=".8"/><rect x="1" y="6.2" width="12" height="1.6" rx=".8"/><rect x="1" y="10.4" width="12" height="1.6" rx=".8"/></svg>
      </a>
    </div>
    <form class="search" role="search" method="GET">
      <input type="hidden" name="ordem" value="{{ $ordem }}">
      <svg class="search__icon" width="15" height="15" viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      <input type="search" name="q" value="{{ $q }}" placeholder="Filtrar por ticker ou nome…" aria-label="Filtrar ações" autocomplete="off" data-filter>
      <kbd class="kbd" aria-hidden="true">/</kbd>
    </form>
  </div>
</div>

@if ($resumo['status'] === 'erro')
  <div class="alert alert--error" role="alert">
    <span class="alert__icon" aria-hidden="true">!</span>
    <p><strong>Falha ao atualizar cotações.</strong> <span class="tone-flat">Exibindo os últimos valores de {{ $resumo['atualizado']->format('d/m · H:i') }}.</span></p>
    <a class="btn btn-ghost btn--sm" href="{{ request()->fullUrl() }}">Tentar novamente</a>
  </div>
@endif

<p class="results-meta" data-count data-total="{{ $resumo['total'] }}">{{ $ativos->count() }} de {{ $resumo['total'] }} ativos · {{ $ordens[$ordem] }}</p>

@if ($resumo['status'] === 'carregando')
  <div class="asset-grid" aria-busy="true" aria-label="Carregando cotações">
    @for ($i = 0; $i < 8; $i++)
      <div class="card asset-card" aria-hidden="true">
        <div class="asset-card__head"><span class="skeleton" style="width:72px;height:16px"></span><span class="skeleton" style="width:60px;height:22px"></span></div>
        <span class="skeleton" style="width:140px;height:11px;margin-top:10px"></span>
        <span class="skeleton" style="width:120px;height:28px;margin-top:18px"></span>
        <span class="skeleton" style="height:52px;margin-top:14px"></span>
        <span class="skeleton" style="height:4px;margin-top:14px"></span>
      </div>
    @endfor
  </div>
@else
  <div class="empty" data-empty @if($ativos->isNotEmpty()) hidden @endif>
    <div class="empty__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 16 16"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></div>
    <h2>Nenhum ativo encontrado</h2>
    <p>Nenhuma ação monitorada corresponde a “<span data-empty-term>{{ $q }}</span>”. Confira o ticker ou limpe o filtro.</p>
    <a class="btn btn-primary" href="{{ $qs(['q' => null]) }}" data-filter-clear>Limpar filtro</a>
  </div>

  @if ($view === 'lista')
    <div class="card asset-table" role="table" aria-label="Ações monitoradas">
      <div class="asset-row asset-row--head" role="row">
        <span role="columnheader">Ativo</span><span role="columnheader">Intradia</span>
        <span class="r" role="columnheader">Preço</span><span class="r" role="columnheader">Var. R$</span><span class="r" role="columnheader">Var. %</span>
        <span role="columnheader">Faixa do dia</span><span class="r" role="columnheader">Fech. ant.</span><span class="r" role="columnheader">Abertura</span>
      </div>
      @foreach ($ativos as $a) <x-asset-row :a="$a" /> @endforeach
    </div>
  @endif

  {{-- Grade sempre presente: no mobile a lista é ocultada via CSS e a grade assume --}}
  <div class="asset-grid" @if($view === 'lista') data-desktop-hidden @endif>
    @foreach ($ativos as $a) <x-asset-card :a="$a" /> @endforeach
  </div>
@endif

<style>
  @media (min-width: 1024px) { [data-desktop-only] { display: flex !important; } [data-desktop-hidden] { display: none; } }
</style>
@endsection

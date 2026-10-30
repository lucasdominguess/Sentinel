<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Sentinel')</title>
  <script>
    (function () {
      var t = localStorage.getItem('sentinel-theme') || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      document.documentElement.dataset.theme = t;
    })();
  </script>
  @vite(['resources/css/tokens.css', 'resources/css/components.css', 'resources/css/acoes.css', 'resources/js/sentinel.js'])
</head>
<body>
@php
  $nav = [
    ['label' => 'Ações', 'route' => 'acoes.index'],
    ['label' => 'FIIs', 'route' => 'fiis.index'],
    ['label' => 'ETFs', 'route' => 'etfs.index'],
    ['label' => 'Aportes', 'route' => 'aportes.index'],
    ['label' => 'Ferramentas', 'route' => 'ferramentas.index'],
  ];
  $is = fn ($r) => request()->routeIs(\Illuminate\Support\Str::before($r, '.') . '.*');
@endphp

<header class="topbar">
  <button class="icon-btn icon-btn--bare topbar__menu" type="button" aria-label="Abrir menu" aria-controls="drawer" data-drawer-open>
    <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true"><rect x="2" y="4" width="14" height="1.6" rx=".8"/><rect x="2" y="8.2" width="14" height="1.6" rx=".8"/><rect x="2" y="12.4" width="9" height="1.6" rx=".8"/></svg>
  </button>
  <a class="brand" href="{{ route('acoes.index') }}">Sentinel</a>
  <nav class="nav" aria-label="Principal">
    @foreach ($nav as $item)
      <a href="{{ route($item['route']) }}" @if($is($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
    @endforeach
  </nav>
  <div class="topbar__end">
    <button class="icon-btn" type="button" aria-label="Alternar tema" data-theme-toggle>
      <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6.2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 1.8a6.2 6.2 0 0 1 0 12.4z" fill="currentColor"/></svg>
    </button>
  </div>
</header>

<div class="drawer" id="drawer" inert>
  <div class="drawer__scrim" data-drawer-close></div>
  <aside class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="drawer__head">
      <span class="brand">Sentinel</span>
      <button class="icon-btn icon-btn--bare" type="button" aria-label="Fechar menu" data-drawer-close>
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </button>
    </div>
    <nav class="drawer__nav" aria-label="Principal">
      <div class="drawer__group">
        <span class="drawer__title">Monitor</span>
        <a href="{{ route('acoes.index') }}" @if($is('acoes.index')) aria-current="page" @endif>Ações <span class="drawer__count">{{ $totais['acoes'] ?? '' }}</span></a>
        <a href="{{ route('fiis.index') }}" @if($is('fiis.index')) aria-current="page" @endif>FIIs <span class="drawer__count">{{ $totais['fiis'] ?? '' }}</span></a>
        <a href="{{ route('etfs.index') }}" @if($is('etfs.index')) aria-current="page" @endif>ETFs <span class="drawer__count">{{ $totais['etfs'] ?? '' }}</span></a>
      </div>
      <div class="drawer__group">
        <span class="drawer__title">Aportes</span>
        <a href="{{ route('aportes.create') }}">Registrar aporte</a>
        <a href="{{ route('aportes.ativos') }}">Meus ativos</a>
        <a href="{{ route('aportes.index') }}">Registros</a>
      </div>
      <div class="drawer__group">
        <span class="drawer__title">Ferramentas</span>
        <a href="{{ route('ferramentas.cotas') }}">Cálculo de cotas</a>
        <a href="{{ route('ferramentas.porcentagem') }}">Porcentagem</a>
      </div>
    </nav>
  </aside>
</div>

<main class="page" id="conteudo">
  @yield('content')
</main>
</body>
</html>

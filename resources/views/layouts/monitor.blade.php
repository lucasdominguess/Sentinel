<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Sentinel')</title>

  {{-- Anti-FOUC: aplica o tema salvo (ou a preferência do sistema) antes do paint.
       Mesma chave de localStorage do layout legado, então o tema é consistente
       ao navegar entre telas já migradas e as antigas. --}}
  <script>
    (function () {
      try {
        var t = localStorage.getItem('sentinel-theme')
          || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = t;
      } catch (e) {}
    })();
  </script>

  @vite(['resources/css/tokens.css', 'resources/css/components.css', 'resources/js/sentinel.js'])
  @stack('styles')
</head>
<body>
@php
  $is = fn ($name) => request()->routeIs($name);
@endphp

<header class="topbar">
  <button class="icon-btn icon-btn--bare topbar__menu" type="button" aria-label="Abrir menu" aria-controls="drawer" data-drawer-open>
    <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true"><rect x="2" y="4" width="14" height="1.6" rx=".8"/><rect x="2" y="8.2" width="14" height="1.6" rx=".8"/><rect x="2" y="12.4" width="9" height="1.6" rx=".8"/></svg>
  </button>
  <a class="brand" href="{{ route('painel.index') }}">Sentinel</a>
  <nav class="nav" aria-label="Monitores">
    <a href="{{ route('painel.index') }}" @if($is('painel.index') || $is('painel.show')) aria-current="page" @endif>Ações</a>
    <a href="{{ route('painel.fiis') }}" @if($is('painel.fiis')) aria-current="page" @endif>FIIs</a>
    <a href="{{ route('painel.etfs') }}" @if($is('painel.etfs')) aria-current="page" @endif>ETFs</a>
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
        <span class="drawer__title">Monitores</span>
        <a href="{{ route('painel.index') }}" @if($is('painel.index') || $is('painel.show')) aria-current="page" @endif>Ações</a>
        <a href="{{ route('painel.fiis') }}" @if($is('painel.fiis')) aria-current="page" @endif>FIIs</a>
        <a href="{{ route('painel.etfs') }}" @if($is('painel.etfs')) aria-current="page" @endif>ETFs</a>
      </div>
      <div class="drawer__group">
        <span class="drawer__title">Ferramentas</span>
        <a href="{{ route('painel.calculo') }}" @if($is('painel.calculo')) aria-current="page" @endif>Cálculo de cotas</a>
        <a href="{{ route('painel.porcentagem') }}" @if($is('painel.porcentagem')) aria-current="page" @endif>Cálculo de porcentagem</a>
      </div>
      <div class="drawer__group">
        <span class="drawer__title">Aportes</span>
        <a href="{{ route('painel.aportes.create') }}" @if($is('painel.aportes.create')) aria-current="page" @endif>Registrar aporte</a>
        <a href="{{ route('painel.aportes.meus') }}" @if($is('painel.aportes.meus')) aria-current="page" @endif>Meus ativos</a>
        <a href="{{ route('painel.aportes.index') }}" @if($is('painel.aportes.index')) aria-current="page" @endif>Registros</a>
      </div>
      <div class="drawer__group">
        <span class="drawer__title">Cadastro</span>
        <a href="{{ route('painel.adicionar') }}" @if($is('painel.adicionar')) aria-current="page" @endif>Adicionar ticket</a>
        <a href="{{ route('painel.ativos.index') }}" @if($is('painel.ativos.*')) aria-current="page" @endif>Gerenciar ativos</a>
      </div>
    </nav>
  </aside>
</div>

<main class="page" id="conteudo">
  @yield('content')
</main>

@stack('scripts')
</body>
</html>

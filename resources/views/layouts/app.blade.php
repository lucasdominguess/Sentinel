<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sentinel')</title>

    {{-- Anti-FOUC: aplica o tema salvo antes do paint (exceção permitida pela skill) --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('sentinel-theme');
                if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    {{-- Tipografia padrão: usa a fonte nativa do sistema (definida em global.css) --}}
    @vite(['resources/css/global.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>
    <header class="topbar">
        <button id="nav-toggle" class="btn-icon" type="button" aria-label="Abrir menu" aria-controls="drawer" aria-expanded="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="topbar__brand" href="{{ route('painel.index') }}">
            Sentinel<span class="dot"></span>
        </a>
        <nav class="topbar__nav" aria-label="Monitores">
            <a href="{{ route('painel.index') }}" class="{{ request()->routeIs('painel.index', 'painel.show') ? 'is-active' : '' }}">Ações</a>
            <a href="{{ route('painel.fiis') }}" class="{{ request()->routeIs('painel.fiis') ? 'is-active' : '' }}">FIIs</a>
            <a href="{{ route('painel.etfs') }}" class="{{ request()->routeIs('painel.etfs') ? 'is-active' : '' }}">ETFs</a>
        </nav>
        <span class="topbar__spacer"></span>
        <button id="theme-toggle" class="btn-icon" type="button" title="Alternar tema" aria-label="Alternar tema"></button>
    </header>

    <div id="overlay" class="overlay" hidden></div>

    <nav id="drawer" class="drawer" aria-label="Navegação principal">
        <div class="drawer__header">
            <span class="drawer__brand">Sentinel</span>
            <button id="nav-close" class="btn-icon" type="button" aria-label="Fechar menu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="drawer__group">
            <span class="drawer__group-title">Monitores</span>
            <a href="{{ route('painel.index') }}" class="drawer__link {{ request()->routeIs('painel.index', 'painel.show') ? 'is-active' : '' }}">Ações</a>
            <a href="{{ route('painel.fiis') }}" class="drawer__link {{ request()->routeIs('painel.fiis') ? 'is-active' : '' }}">FIIs</a>
            <a href="{{ route('painel.etfs') }}" class="drawer__link {{ request()->routeIs('painel.etfs') ? 'is-active' : '' }}">ETFs</a>
        </div>

        <div class="drawer__group">
            <span class="drawer__group-title">Ferramentas</span>
            <a href="{{ route('painel.calculo') }}" class="drawer__link {{ request()->routeIs('painel.calculo') ? 'is-active' : '' }}">Cálculo de cotas</a>
            <a href="{{ route('painel.porcentagem') }}" class="drawer__link {{ request()->routeIs('painel.porcentagem') ? 'is-active' : '' }}">Cálculo de porcentagem</a>
        </div>

        <div class="drawer__group">
            <span class="drawer__group-title">Aportes</span>
            <a href="{{ route('painel.aportes.create') }}" class="drawer__link {{ request()->routeIs('painel.aportes.create') ? 'is-active' : '' }}">Registrar aporte</a>
            <a href="{{ route('painel.aportes.meus') }}" class="drawer__link {{ request()->routeIs('painel.aportes.meus') ? 'is-active' : '' }}">Meus ativos</a>
            <a href="{{ route('painel.aportes.index') }}" class="drawer__link {{ request()->routeIs('painel.aportes.index') ? 'is-active' : '' }}">Registros</a>
        </div>

        <div class="drawer__group">
            <span class="drawer__group-title">Cadastro</span>
            <a href="{{ route('painel.adicionar') }}" class="drawer__link {{ request()->routeIs('painel.adicionar') ? 'is-active' : '' }}">Adicionar ticket</a>
        </div>
    </nav>

    <main class="container">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>

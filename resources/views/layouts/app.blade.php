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

    @vite(['resources/css/global.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>
    <header class="topbar">
        <button id="nav-toggle" class="btn-icon" type="button" aria-label="Abrir menu" aria-controls="drawer" aria-expanded="false">☰</button>
        <a class="topbar__brand" href="{{ route('painel.index') }}">
            <span class="dot"></span> Sentinel
        </a>
        <span class="topbar__spacer"></span>
        <button id="theme-toggle" class="btn-icon" type="button" title="Alternar tema" aria-label="Alternar tema">🌙</button>
    </header>

    <div id="overlay" class="overlay" hidden></div>

    <nav id="drawer" class="drawer" aria-label="Navegação principal">
        <div class="drawer__header">
            <span class="topbar__brand"><span class="dot"></span> Sentinel</span>
            <button id="nav-close" class="btn-icon" type="button" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="drawer__nav">
            <li><a href="{{ route('painel.index') }}" class="drawer__link {{ request()->routeIs('painel.index') ? 'is-active' : '' }}">📈 Monitor de Ações</a></li>
            <li><a href="{{ route('painel.fiis') }}" class="drawer__link {{ request()->routeIs('painel.fiis') ? 'is-active' : '' }}">🏢 Monitor de FIIs</a></li>
            <li><a href="{{ route('painel.etfs') }}" class="drawer__link {{ request()->routeIs('painel.etfs') ? 'is-active' : '' }}">📊 Monitor de ETFs</a></li>
            <li class="drawer__divider" aria-hidden="true"></li>
            <li><a href="{{ route('painel.calculo') }}" class="drawer__link {{ request()->routeIs('painel.calculo') ? 'is-active' : '' }}">🧮 Cálculo de cotas</a></li>
            <li><a href="{{ route('painel.porcentagem') }}" class="drawer__link {{ request()->routeIs('painel.porcentagem') ? 'is-active' : '' }}">➗ Cálculo de porcentagem</a></li>
            <li><a href="{{ route('painel.adicionar') }}" class="drawer__link {{ request()->routeIs('painel.adicionar') ? 'is-active' : '' }}">➕ Adicionar ticket</a></li>
        </ul>
    </nav>

    <main class="container">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>

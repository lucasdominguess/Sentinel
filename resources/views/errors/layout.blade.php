<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') — Sentinel</title>

    {{-- Anti-FOUC: aplica o tema salvo. Estilos inline de propósito: a página de
         erro não pode depender do build (Vite), senão quebraria junto. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('sentinel-theme');
                if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <style>
        :root {
            --bg: #f4f6f9;
            --surface: #ffffff;
            --border: #e2e6ee;
            --text: #1a1d24;
            --text-muted: #5b6472;
            --primary: #2563eb;
            --primary-contrast: #ffffff;
            --shadow: 0 1px 3px rgba(16, 24, 40, 0.08), 0 1px 2px rgba(16, 24, 40, 0.04);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0f1115;
                --surface: #171a21;
                --border: #2a2f3a;
                --text: #e6e9ef;
                --text-muted: #9aa3b2;
                --primary: #3b82f6;
                --shadow: 0 1px 3px rgba(0, 0, 0, 0.45);
            }
        }

        :root[data-theme="dark"] {
            --bg: #0f1115;
            --surface: #171a21;
            --border: #2a2f3a;
            --text: #e6e9ef;
            --text-muted: #9aa3b2;
            --primary: #3b82f6;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.45);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        .err {
            width: 100%;
            max-width: 440px;
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 40px 32px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 17px;
            margin-bottom: 24px;
        }

        .brand .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary);
        }

        .code {
            font-size: 68px;
            font-weight: 800;
            line-height: 1;
            color: var(--primary);
            letter-spacing: -1px;
        }

        .title {
            font-size: 21px;
            font-weight: 700;
            margin: 14px 0 8px;
        }

        .msg {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 9px;
            background: var(--primary);
            color: var(--primary-contrast);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }

        .btn:hover { filter: brightness(1.08); }
    </style>
</head>

<body>
    <div class="err">
        <div class="brand"><span class="dot"></span> Sentinel</div>
        <div class="code">@yield('code')</div>
        <div class="title">@yield('title')</div>
        <p class="msg">@yield('message')</p>
        <a class="btn" href="{{ url('/') }}">← Voltar ao início</a>
    </div>
</body>

</html>

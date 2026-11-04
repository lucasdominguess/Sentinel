import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/global.css',
                // Sistema visual v1 (layout monitor: Ações/FIIs/ETFs)
                'resources/css/tokens.css',
                'resources/css/components.css',
                'resources/js/sentinel.js',
                'resources/css/pages/acoes-monitor.css',
                'resources/js/pages/acoes-monitor.js',
                'resources/css/pages/carteira.css',
                'resources/js/pages/carteira.js',
                'resources/css/pages/acoes-index.css',
                'resources/js/pages/acoes-index.js',
                'resources/css/pages/acoes-show.css',
                'resources/js/pages/acoes-show.js',
                'resources/css/pages/acoes-adicionar.css',
                'resources/css/pages/acoes-cadastro.css',
                'resources/js/pages/acoes-cadastro.js',
                'resources/css/pages/acoes-detalhes.css',
                'resources/css/pages/acoes-calculo.css',
                'resources/js/pages/acoes-calculo.js',
                'resources/css/pages/acoes-porcentagem.css',
                'resources/js/pages/acoes-porcentagem.js',
                'resources/css/pages/aportes-registrar.css',
                'resources/js/pages/aportes-registrar.js',
                'resources/css/pages/aportes-meus.css',
                'resources/css/pages/aportes-registros.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

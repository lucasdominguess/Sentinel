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
                'resources/css/pages/acoes-index.css',
                'resources/js/pages/acoes-index.js',
                'resources/css/pages/acoes-show.css',
                'resources/js/pages/acoes-show.js',
                'resources/css/pages/acoes-adicionar.css',
                'resources/css/pages/acoes-calculo.css',
                'resources/js/pages/acoes-calculo.js',
                'resources/css/pages/acoes-porcentagem.css',
                'resources/js/pages/acoes-porcentagem.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

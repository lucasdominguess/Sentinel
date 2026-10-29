<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coletor Python
    |--------------------------------------------------------------------------
    | O coletor vive em scripts/python e é acionado pelos comandos
    | sentinel:collect e sentinel:backfill. O padrão aponta para o venv do
    | próprio projeto; defina SENTINEL_PYTHON no .env para usar outro
    | interpretador (python global, pyenv, container...).
    */

    'python' => env('SENTINEL_PYTHON', PHP_OS_FAMILY === 'Windows'
        ? base_path('scripts/python/.venv/Scripts/python.exe')
        : base_path('scripts/python/.venv/bin/python')),

    'scripts_path' => base_path('scripts/python'),

    /*
    |--------------------------------------------------------------------------
    | Agendamento
    |--------------------------------------------------------------------------
    | A aplicação roda em UTC, mas o pregão é B3. O agendamento em
    | routes/console.php usa este fuso para que "entre 10h e 18h30" signifique
    | horário de Brasília.
    */

    'timezone' => env('SENTINEL_TIMEZONE', 'America/Sao_Paulo'),

];

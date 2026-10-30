<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------------------------------------------------------
// Coleta diária
// ---------------------------------------------------------------------------
// A coleta NÃO é agendada pelo scheduler do Laravel. Ela roda direto no cron
// do servidor, chamando o script Python pelo interpretador do venv — veja o
// passo a passo e a janela de execução em scripts/python/README.md.
//
// Exemplo de entrada no crontab (dias úteis, de 30 em 30 min, fuso da B3):
//   CRON_TZ=America/Sao_Paulo
//   [*/30] 10-19 * * 1-5  /caminho/scripts/python/.venv/bin/python  /caminho/scripts/python/fetch_stocks.py

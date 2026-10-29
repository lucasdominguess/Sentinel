<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Coleta diária
|--------------------------------------------------------------------------
| O agendamento vive aqui, e não em crons soltos por script: o sistema
| operacional precisa de uma única entrada (schedule:run de minuto em
| minuto) e o resto é versionado junto com o código.
|
| Janela: dias úteis, de 30 em 30 minutos entre 10h e 18h30 (fuso da B3).
| A última execução acontece depois do fechamento, então o candle do dia
| termina com o valor definitivo. withoutOverlapping evita duas coletas
| concorrentes quando uma demora mais que o intervalo.
*/
Schedule::command('sentinel:collect')
    ->weekdays()
    ->everyThirtyMinutes()
    ->between('10:00', '18:30')
    ->timezone(config('sentinel.timezone'))
    ->withoutOverlapping(30)
    ->runInBackground();

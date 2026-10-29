<?php

namespace App\Console\Commands;

class SentinelBackfill extends SentinelPythonCommand
{
    protected $signature = 'sentinel:backfill
                            {tickers?* : Tickers específicos (padrão: os ativos cadastrados)}
                            {--period=5y : Janela do histórico (1y, 5y, 10y, max...)}
                            {--pausa=0.5 : Segundos de pausa entre ativos, para não tomar throttle}';

    protected $description = 'Carga histórica dos fechamentos diários (idempotente)';

    // Histórico longo de dezenas de ativos: sem limite de tempo.
    protected ?float $timeout = null;

    public function handle(): int
    {
        $args = $this->argument('tickers');
        $args[] = '--period=' . $this->option('period');
        $args[] = '--pausa=' . $this->option('pausa');

        return $this->runPython('backfill_history.py', $args);
    }
}

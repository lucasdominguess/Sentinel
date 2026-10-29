<?php

namespace App\Console\Commands;

class SentinelCollect extends SentinelPythonCommand
{
    protected $signature = 'sentinel:collect
                            {tickers?* : Tickers específicos (padrão: os ativos cadastrados)}
                            {--tipo=acao : Tipo usado só ao criar um ticker inédito}
                            {--no-save : Apenas exibe, sem gravar no banco}';

    protected $description = 'Coleta as cotações do momento e atualiza os fechamentos diários';

    protected ?float $timeout = 1800;

    public function handle(): int
    {
        $args = $this->argument('tickers');
        $args[] = '--tipo=' . $this->option('tipo');

        if ($this->option('no-save')) {
            $args[] = '--no-save';
        }

        return $this->runPython('fetch_stocks.py', $args);
    }
}

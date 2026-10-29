<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

// Base dos comandos que acionam o coletor Python. Os comandos concretos só
// dizem qual script rodar e com quais argumentos — a ponte PHP -> Python
// (interpretador, diretório, streaming da saída) mora aqui.
abstract class SentinelPythonCommand extends Command
{
    // null = sem limite de tempo (o backfill pode levar minutos)
    protected ?float $timeout = 1800;

    protected function runPython(string $script, array $args = []): int
    {
        $python = config('sentinel.python');
        $diretorio = config('sentinel.scripts_path');

        if (!is_file($python)) {
            $this->error("Interpretador Python não encontrado em: {$python}");
            $this->line('Crie o venv em scripts/python ou defina SENTINEL_PYTHON no .env.');

            return self::FAILURE;
        }

        if (!is_file($diretorio . DIRECTORY_SEPARATOR . $script)) {
            $this->error("Script não encontrado: {$script}");

            return self::FAILURE;
        }

        $process = new Process([$python, $script, ...$args], $diretorio, null, null, $this->timeout);

        try {
            // Saída em tempo real: a coleta é longa e o silêncio esconde problema.
            $process->run(fn ($tipo, $saida) => $this->output->write($saida));
        } catch (ProcessStartFailedException $e) {
            $this->error('Não foi possível iniciar o coletor: ' . $e->getMessage());

            return self::FAILURE;
        }

        return $process->getExitCode() === 0 ? self::SUCCESS : self::FAILURE;
    }
}

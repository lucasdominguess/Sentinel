<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanAll extends Command
{
    protected $signature = 'cls {--all : Limpeza completa com autoload do Composer} {--prod : Otimizado para produção}';
    protected $description = 'Limpa caches do Laravel. Use --all para limpeza completa com Composer';

    public function handle(): int
    {
        $isAll = $this->option('all');
        $isProd = $this->option('prod');

        $this->displayHeader($isAll, $isProd);
        $this->newLine();

        try {
            // Fase 1: Limpar todos os caches
            $this->info('📦 Limpando caches...');
            $this->call('cache:clear');
            $this->call('view:clear');
            $this->call('event:clear');
            $this->newLine();

            // Fase 2: Limpar configurações e rotas
            $this->info('⚙️ Limpando configurações...');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('clear-compiled');
            $this->newLine();

            // Fase 3: Limpar otimizações antigas
            $this->info('🧹 Removendo otimizações antigas...');
            $this->call('optimize:clear');
            $this->newLine();

            // Fase 4: Recriar caches com valores atualizados
            $this->info('🔄 Recriando caches...');
            $this->call('config:cache');
            $this->call('route:cache');
            $this->call('event:cache');
            $this->newLine();

            // Fase 5: Autoload do Composer (apenas com --all)
            if ($isAll) {
                $this->info('📚 Atualizando autoload do Composer...');
                $this->executeCommand('composer dump-autoload' . ($isProd ? ' -o' : ''));
                $this->newLine();
            }

            // Fase 6: Otimização para produção (apenas com --prod)
            if ($isProd) {
                $this->info('⚡ Otimizando para produção...');
                $this->call('optimize');
                $this->newLine();
            }

            $this->displaySuccess($isAll, $isProd);
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erro durante limpeza: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function displayHeader(bool $isAll, bool $isProd): void
    {
        $this->info('╔════════════════════════════════════════╗');

        if ($isProd) {
            $this->info('║    Limpeza para PRODUÇÃO               ║');
        } elseif ($isAll) {
            $this->info('║    Limpeza COMPLETA (com Composer)     ║');
        } else {
            $this->info('║    Limpeza BÁSICA (caches Laravel)     ║');
        }

        $this->info('╚════════════════════════════════════════╝');
    }

    private function displaySuccess(bool $isAll, bool $isProd): void
    {
        $this->info('✅ Limpeza concluída com sucesso!');
        $this->newLine();
        $this->line('<fg=cyan>Comandos disponíveis:</>');
        $this->line('  php artisan cls              - Limpeza básica (apenas caches)');
        $this->line('  php artisan cls --all        - Limpeza completa com Composer');
        $this->line('  php artisan cls --prod       - Otimizado para produção');
        $this->line('  php artisan cls --all --prod - Tudo + otimização produção');
    }

    private function executeCommand(string $command): void
    {
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Erro ao executar: $command");
        }

        foreach ($output as $line) {
            $this->line($line);
        }
    }
}

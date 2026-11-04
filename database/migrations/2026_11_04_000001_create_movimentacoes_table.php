<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Movimentações da carteira (compra e venda), substituindo `aportes`.
     *
     * É o livro de registros da carteira: a posição em qualquer data D sai da
     * soma das movimentações com data <= D (compras somam cotas, vendas
     * subtraem). Por isso toda movimentação tem data e nada é agregado aqui.
     * Os aportes existentes são migrados como compras.
     */
    public function up(): void
    {
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acao_id')
                ->constrained('acoes')
                ->cascadeOnDelete()
                ->comment('FK -> acoes.id (ativo negociado)');

            $table->string('tipo', 10)->comment('compra | venda (App\Enums\TipoMovimentacao)');
            $table->decimal('preco', 16, 6)->comment('Preço unitário da operação');
            $table->unsignedInteger('quantidade')->comment('Quantidade de cotas negociadas (sempre positiva)');
            $table->decimal('total', 20, 2)->comment('preco x quantidade (derivado no Service)');
            $table->date('data')->comment('Data da operação; default = data atual quando não informada');

            $table->timestamps();

            // Consulta típica: movimentações de um ativo em ordem cronológica
            $table->index(['acao_id', 'data']);
        });

        if (Schema::hasTable('aportes')) {
            DB::statement("
                INSERT INTO movimentacoes (acao_id, tipo, preco, quantidade, total, data, created_at, updated_at)
                SELECT acao_id, 'compra', preco_pago, quantidade, total_investido, data, created_at, updated_at
                FROM aportes
                ORDER BY data, id
            ");

            Schema::drop('aportes');
        }
    }

    public function down(): void
    {
        Schema::create('aportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acao_id')->constrained('acoes')->cascadeOnDelete();
            $table->decimal('preco_pago', 16, 6);
            $table->unsignedInteger('quantidade');
            $table->decimal('total_investido', 20, 2);
            $table->date('data');
            $table->timestamps();
            $table->index(['acao_id', 'data']);
        });

        // Só as compras têm equivalente em `aportes`; vendas se perdem no rollback
        DB::statement("
            INSERT INTO aportes (acao_id, preco_pago, quantidade, total_investido, data, created_at, updated_at)
            SELECT acao_id, preco, quantidade, total, data, created_at, updated_at
            FROM movimentacoes
            WHERE tipo = 'compra'
            ORDER BY data, id
        ");

        Schema::dropIfExists('movimentacoes');
    }
};

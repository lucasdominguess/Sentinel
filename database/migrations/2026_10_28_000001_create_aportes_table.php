<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de aportes (compras de ativos registradas pelo investidor).
     * Vários aportes podem apontar para o mesmo ativo em datas diferentes;
     * o preço médio é derivado da agregação destes registros.
     */
    public function up(): void
    {
        Schema::create('aportes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acao_id')
                ->constrained('acoes')
                ->cascadeOnDelete()
                ->comment('FK -> acoes.id (ativo comprado)');

            // Preço unitário pago na compra (casas de sobra como em cotacoes)
            $table->decimal('preco_pago', 16, 6)->comment('Preço unitário pago por cota');
            $table->unsignedInteger('quantidade')->comment('Quantidade de cotas compradas');
            $table->decimal('total_investido', 20, 2)->comment('preco_pago x quantidade (derivado no Service)');
            $table->date('data')->comment('Data da compra; default = data atual quando não informada');

            $table->timestamps();

            // Consulta típica: aportes de um ativo em ordem cronológica
            $table->index(['acao_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aportes');
    }
};

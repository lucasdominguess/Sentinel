<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fechamento diário consolidado (uma linha por ativo por dia de pregão).
     *
     * Complementa `cotacoes`, que é snapshot intradiário: aqui mora a série
     * histórica estável usada em gráfico, variação e evolução patrimonial.
     * Alimentada pelo history() do yfinance, que já vem ajustado por
     * desdobramentos e proventos.
     */
    public function up(): void
    {
        Schema::create('cotacoes_diarias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acao_id')
                ->constrained('acoes')
                ->cascadeOnDelete()
                ->comment('FK -> acoes.id');

            $table->date('data')->comment('Dia de pregão (data do candle)');

            $table->decimal('abertura', 16, 6)->nullable()->comment('Open');
            $table->decimal('maxima', 16, 6)->nullable()->comment('High');
            $table->decimal('minima', 16, 6)->nullable()->comment('Low');
            $table->decimal('fechamento', 16, 6)->nullable()->comment('Close (preço de tela no dia)');
            $table->decimal('fechamento_ajustado', 16, 6)->nullable()->comment('Adj Close — ajustado por desdobramento/provento; use este para rentabilidade');
            $table->unsignedBigInteger('volume')->nullable()->comment('Volume negociado no dia');

            $table->timestamps();

            // Um fechamento por ativo por dia — é o que torna o upsert idempotente
            // e permite reprocessar o backfill sem duplicar.
            $table->unique(['acao_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacoes_diarias');
    }
};

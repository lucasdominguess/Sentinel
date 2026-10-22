<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de cotações (série temporal).
     * Cada execução do coletor Python insere uma linha por ativo.
     */
    public function up(): void
    {
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('acao_id')
                ->constrained('acoes')
                ->cascadeOnDelete()
                ->comment('FK -> acoes.id');

            // Preços (decimal com casas de sobra para preservar valores históricos baixos)
            $table->decimal('preco', 16, 6)->nullable()->comment('currentPrice/regularMarketPrice');
            $table->decimal('fechamento_anterior', 16, 6)->nullable()->comment('previousClose');
            $table->decimal('abertura', 16, 6)->nullable()->comment('open');
            $table->decimal('minima_dia', 16, 6)->nullable()->comment('dayLow');
            $table->decimal('maxima_dia', 16, 6)->nullable()->comment('dayHigh');
            $table->decimal('variacao_dia_pct', 8, 4)->nullable()->comment('regularMarketChangePercent (% de variação no dia)');
            $table->decimal('minima_52s', 16, 6)->nullable()->comment('fiftyTwoWeekLow');
            $table->decimal('maxima_52s', 16, 6)->nullable()->comment('fiftyTwoWeekHigh');

            // Volume e valor de mercado
            $table->unsignedBigInteger('volume')->nullable()->comment('volume do dia');
            $table->unsignedBigInteger('volume_medio')->nullable()->comment('averageVolume');
            $table->decimal('valor_mercado', 20, 2)->nullable()->comment('marketCap');

            // Indicadores
            $table->decimal('dividend_yield', 8, 4)->nullable()->comment('dividendYield');
            $table->decimal('preco_lucro', 12, 4)->nullable()->comment('P/L (trailingPE)');

            // Contexto da coleta
            $table->string('estado_mercado')->nullable()->comment('marketState: REGULAR, PREPRE, CLOSED...');
            $table->timestamp('capturado_em')->comment('Momento em que a cotação foi coletada');

            $table->timestamps();

            // Consulta típica: últimas cotações de um ativo em ordem de tempo
            $table->index(['acao_id', 'capturado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotacoes');
    }
};

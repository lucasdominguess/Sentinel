<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de ativos monitorados (metadado estático da empresa).
     * Uma linha por ação. As cotações ficam na tabela "cotacoes".
     */
    public function up(): void
    {
        Schema::create('acoes', function (Blueprint $table) {
            $table->id();
            $table->string('ticker')->unique()->comment('Código do ativo no yfinance, ex.: ALPA3.SA');
            $table->string('nome')->nullable()->comment('Nome da empresa (longName/shortName)');
            $table->string('setor')->nullable()->comment('sector do yfinance');
            $table->string('segmento')->nullable()->comment('industry do yfinance');
            $table->string('moeda', 3)->default('BRL')->comment('currency');
            $table->boolean('ativo')->default(true)->comment('Se o ativo é monitorado pelo coletor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acoes');
    }
};

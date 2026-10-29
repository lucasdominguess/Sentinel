<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de execução do coletor.
     *
     * Existe porque a falha silenciosa é o pior defeito do coletor: dois dias
     * de pregão foram perdidos e só se descobriu consultando o banco. Uma
     * execução que morre no meio deixa `fim` nulo — isso é o sinal de alerta.
     */
    public function up(): void
    {
        Schema::create('coletas', function (Blueprint $table) {
            $table->id();

            $table->string('origem')->default('coleta')->comment('coleta (rotina diária) ou backfill (carga histórica)');

            $table->timestamp('inicio')->comment('Início da execução');
            $table->timestamp('fim')->nullable()->comment('Fim da execução; nulo = não terminou (travou ou foi interrompida)');

            $table->unsignedInteger('ativos_ok')->default(0)->comment('Ativos processados com sucesso');
            $table->unsignedInteger('ativos_falha')->default(0)->comment('Ativos que falharam');
            $table->unsignedInteger('registros')->default(0)->comment('Linhas gravadas (cotações + fechamentos diários)');

            $table->text('erro')->nullable()->comment('Mensagem do erro que abortou a execução');

            $table->timestamps();

            $table->index('inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coletas');
    }
};

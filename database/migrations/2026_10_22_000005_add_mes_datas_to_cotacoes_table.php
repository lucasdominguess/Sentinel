<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->date('minima_mes_data')->nullable()->comment('Data em que ocorreu a mínima do mês');
            $table->date('maxima_mes_data')->nullable()->comment('Data em que ocorreu a máxima do mês');
        });
    }

    public function down(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropColumn(['minima_mes_data', 'maxima_mes_data']);
        });
    }
};

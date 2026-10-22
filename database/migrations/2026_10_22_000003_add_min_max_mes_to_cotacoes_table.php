<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->decimal('minima_mes', 16, 6)->nullable()->comment('Menor Low do mês atual (via history)');
            $table->decimal('maxima_mes', 16, 6)->nullable()->comment('Maior High do mês atual (via history)');
        });
    }

    public function down(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropColumn(['minima_mes', 'maxima_mes']);
        });
    }
};

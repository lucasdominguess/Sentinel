<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->decimal('ultimo_dividendo', 16, 6)->nullable()->comment('lastDividendValue (R$ do último provento por cota)');
            $table->date('ultimo_dividendo_data')->nullable()->comment('lastDividendDate (data do último provento)');
        });
    }

    public function down(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropColumn(['ultimo_dividendo', 'ultimo_dividendo_data']);
        });
    }
};

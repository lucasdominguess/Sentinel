<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acoes', function (Blueprint $table) {
            $table->string('tipo', 10)->default('acao')->comment('Tipo do ativo: acao | fii | etf');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('acoes', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropColumn('tipo');
        });
    }
};

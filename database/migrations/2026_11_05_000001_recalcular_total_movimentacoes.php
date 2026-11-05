<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // O total é derivado (preço × quantidade, 2 casas), mas linhas vindas de `aportes`
    // e do import_sync entraram com o total como estava gravado. Um total divergente
    // distorce o preço médio, então recalcula as que não batem.
    public function up(): void
    {
        DB::statement('
            UPDATE movimentacoes
            SET total = round(preco * quantidade, 2), updated_at = now()
            WHERE total <> round(preco * quantidade, 2)
        ');
    }

    // Correção de dado: o valor antigo estava errado, não há o que restaurar
    public function down(): void {}
};

<?php

namespace App\Models;

use App\Enums\TipoMovimentacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes';

    protected $fillable = [
        'acao_id',
        'tipo',
        'preco',
        'quantidade',
        'total',
        'data',
    ];

    protected $casts = [
        'tipo' => TipoMovimentacao::class,
        'preco' => 'decimal:6',
        'total' => 'decimal:2',
        'quantidade' => 'integer',
        'data' => 'date:Y-m-d',
    ];

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aporte extends Model
{
    use HasFactory;

    protected $table = 'aportes';

    protected $fillable = [
        'acao_id',
        'preco_pago',
        'quantidade',
        'total_investido',
        'data',
    ];

    protected $casts = [
        'preco_pago' => 'decimal:6',
        'total_investido' => 'decimal:2',
        'quantidade' => 'integer',
        'data' => 'date:Y-m-d',
    ];

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class);
    }
}

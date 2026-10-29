<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotacaoDiaria extends Model
{
    use HasFactory;

    protected $table = 'cotacoes_diarias';

    protected $fillable = [
        'acao_id',
        'data',
        'abertura',
        'maxima',
        'minima',
        'fechamento',
        'fechamento_ajustado',
        'volume',
    ];

    protected $casts = [
        'data' => 'date:Y-m-d',
        'abertura' => 'decimal:6',
        'maxima' => 'decimal:6',
        'minima' => 'decimal:6',
        'fechamento' => 'decimal:6',
        'fechamento_ajustado' => 'decimal:6',
        'volume' => 'integer',
    ];

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cotacao extends Model
{
    use HasFactory;

    protected $table = 'cotacoes';

    protected $fillable = [
        'acao_id',
        'preco',
        'fechamento_anterior',
        'abertura',
        'minima_dia',
        'maxima_dia',
        'variacao_dia_pct',
        'variacao_dia',
        'minima_52s',
        'maxima_52s',
        'minima_mes',
        'maxima_mes',
        'volume',
        'volume_medio',
        'valor_mercado',
        'dividend_yield',
        'ultimo_dividendo',
        'ultimo_dividendo_data',
        'preco_lucro',
        'estado_mercado',
        'capturado_em',
    ];

    protected $casts = [
        'capturado_em' => 'datetime',
        'preco' => 'decimal:6',
        'fechamento_anterior' => 'decimal:6',
        'abertura' => 'decimal:6',
        'minima_dia' => 'decimal:6',
        'maxima_dia' => 'decimal:6',
        'variacao_dia_pct' => 'decimal:4',
        'variacao_dia' => 'decimal:6',
        'minima_52s' => 'decimal:6',
        'maxima_52s' => 'decimal:6',
        'minima_mes' => 'decimal:6',
        'maxima_mes' => 'decimal:6',
        'valor_mercado' => 'decimal:2',
        'dividend_yield' => 'decimal:4',
        'ultimo_dividendo' => 'decimal:6',
        'ultimo_dividendo_data' => 'date:Y-m-d',
        'preco_lucro' => 'decimal:4',
        'volume' => 'integer',
        'volume_medio' => 'integer',
    ];

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class);
    }
}

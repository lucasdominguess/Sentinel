<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Acao extends Model
{
    use HasFactory;

    protected $table = 'acoes';

    protected $fillable = [
        'ticker',
        'nome',
        'setor',
        'segmento',
        'moeda',
        'tipo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cotacoes(): HasMany
    {
        return $this->hasMany(Cotacao::class);
    }

    public function cotacoesDiarias(): HasMany
    {
        return $this->hasMany(CotacaoDiaria::class);
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(Aporte::class);
    }

    // Última cotação coletada — usada na listagem e no card do ativo
    public function ultimaCotacao(): HasOne
    {
        return $this->hasOne(Cotacao::class)->latestOfMany('capturado_em');
    }

    // Último fechamento diário — base estável para variação e rentabilidade
    public function ultimoFechamento(): HasOne
    {
        return $this->hasOne(CotacaoDiaria::class)->latestOfMany('data');
    }
}

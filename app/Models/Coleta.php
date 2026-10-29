<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coleta extends Model
{
    use HasFactory;

    protected $table = 'coletas';

    protected $fillable = [
        'origem',
        'inicio',
        'fim',
        'ativos_ok',
        'ativos_falha',
        'registros',
        'erro',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'ativos_ok' => 'integer',
        'ativos_falha' => 'integer',
        'registros' => 'integer',
    ];
}

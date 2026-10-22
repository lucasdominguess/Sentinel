<?php

namespace App\Enums;

enum TipoAtivo: string
{
    case ACAO = 'acao';
    case FII = 'fii';
    case ETF = 'etf';

    public function label(): string
    {
        return match ($this) {
            self::ACAO => 'Ação',
            self::FII => 'FII',
            self::ETF => 'ETF',
        };
    }
}

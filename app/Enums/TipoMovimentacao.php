<?php

namespace App\Enums;

enum TipoMovimentacao: string
{
    case COMPRA = 'compra';
    case VENDA = 'venda';

    public function label(): string
    {
        return match ($this) {
            self::COMPRA => 'Compra',
            self::VENDA => 'Venda',
        };
    }
}

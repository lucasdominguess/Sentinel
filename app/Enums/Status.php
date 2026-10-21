<?php

namespace App\Enums;

enum Status: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;

    // Esta é uma função útil que não precisa de parâmetros
    public function label(): int
    {
        return match ($this) {
            self::ACTIVE => 1,
            self::INACTIVE => 2
        };
    }
}

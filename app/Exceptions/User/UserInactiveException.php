<?php

namespace App\Exceptions\User;

use App\Exceptions\CustomException;

class UserInactiveException extends CustomException
{
    public function __construct(string $message = 'Usuário aguardando ativação pelo Administrador')
    {
        parent::__construct($message, 500);
    }
}

<?php

namespace App\Exceptions\Ldap;

use App\Exceptions\CustomException;

class LdapAuthenticationException extends CustomException
{
    public function __construct(string $message = 'Usuário ou senha inválidos.')
    {
        parent::__construct($message, 401);
    }
}

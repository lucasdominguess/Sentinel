<?php

namespace App\Exceptions\Ldap;

use App\Exceptions\CustomException;

class LdapUserNotFoundException extends CustomException
{
    public function __construct(string $message = 'Usuário não encontrado no diretório LDAP.')
    {
        parent::__construct($message, 404);
    }
}

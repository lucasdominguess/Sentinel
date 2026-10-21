<?php

namespace App\Exceptions\Ldap;

use App\Exceptions\CustomException;

class LdapConnectionException extends CustomException
{
    public function __construct(string $message = 'Falha na comunicação com o servidor LDAP.')
    {
        parent::__construct($message, 500);
    }
}

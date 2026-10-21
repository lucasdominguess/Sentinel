<?php

namespace App\Interfaces;

interface LdapInterface
{
    public function loginLdap(string $username, string $password): array;
}

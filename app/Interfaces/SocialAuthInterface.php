<?php
namespace App\Interfaces;



use App\Models\Users\User;

interface SocialAuthInterface
{
    /**
     * Autentica um usuário a partir de um token de provedor social
     * e retorna a instância do usuário local.
     *
     * @param string $token
     * @return User
     */
    public function authenticate(string $token): User;
}

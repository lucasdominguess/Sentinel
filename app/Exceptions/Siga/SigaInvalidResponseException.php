<?php

namespace App\Exceptions\Siga;

// Resposta SOAP malformada ou fora do contrato esperado.
class SigaInvalidResponseException extends SigaException
{
    protected int $statusCode = 502;

    public function __construct(string $message = 'Resposta inválida recebida do SIGA.')
    {
        parent::__construct($message);
    }
}

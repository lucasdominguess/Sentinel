<?php

namespace App\Exceptions\Siga;

// Regra de negócio / SOAP Fault retornado pelo próprio SIGA (ex.: CPF inválido).
class SigaBusinessException extends SigaException
{
    protected int $statusCode = 422;

    public function __construct(string $message = 'Não foi possível concluir a consulta no SIGA.')
    {
        parent::__construct($message);
    }
}

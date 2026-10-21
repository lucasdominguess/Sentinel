<?php

namespace App\Exceptions\Siga;

// Falha de transporte/conexão com o WebService (timeout, DNS, rede).
class SigaUnavailableException extends SigaException
{
    protected int $statusCode = 503;

    public function __construct(string $message = 'Serviço do SIGA temporariamente indisponível.')
    {
        parent::__construct($message);
    }
}

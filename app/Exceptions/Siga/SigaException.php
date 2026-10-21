<?php

namespace App\Exceptions\Siga;

use Exception;

// Classe base para todas as exceções do WebService SIGA.
// Cada subclasse define seu próprio statusCode HTTP, lido no render do bootstrap/app.php.
class SigaException extends Exception
{
    protected int $statusCode = 500;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

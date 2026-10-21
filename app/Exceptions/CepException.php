<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class CepException extends Exception
{
      public function report(): void
    {
        Log::error("Falha na integração de CEP: " . $this->getMessage(), [
            'code' => $this->getCode()
        ]);
    }
}

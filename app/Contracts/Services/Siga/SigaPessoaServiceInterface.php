<?php

namespace App\Contracts\Services\Siga;

use App\DTOs\Siga\BuscarPessoaSigaDTO;

interface SigaPessoaServiceInterface
{
    public function buscarPessoa(BuscarPessoaSigaDTO $dto): array;
}

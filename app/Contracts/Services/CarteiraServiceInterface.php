<?php

namespace App\Contracts\Services;

interface CarteiraServiceInterface
{
    public function calendario(): array;

    public function fotoDoDia(string $data): array;

    public function evolucao(string $de, string $ate): array;
}

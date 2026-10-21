<?php
namespace App\Interfaces;

interface SanitizerInterface
{
    public function clean(string|null $value): string|null;
    public function cleanArray(array $data): array;
}
